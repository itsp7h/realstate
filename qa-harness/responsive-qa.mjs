import { chromium } from 'playwright';
import fs from 'fs';

const BASE = 'http://127.0.0.1:8199';
const WIDTHS = [320, 375, 390, 430, 768, 1024, 1440];
const SHOTS = process.env.SHOTS ? process.env.SHOTS.split(',').map(Number) : [375, 1440];
const OUT = process.env.OUT || `${process.env.SP}/shots`;
const THEME = process.env.THEME || 'light';
const ONLY = process.env.ONLY ? process.env.ONLY.split(',') : null;

const PAGES = JSON.parse(fs.readFileSync(`${process.env.SP}/routes.json`, 'utf8'));

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });
const page = await ctx.newPage();

// ── log in through the real form ────────────────────────────────────────────
await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
await page.fill('#login', 'qa-visual@example.com');
await page.fill('#password', 'qa-visual-pass');
await page.click('form:has(#login) button[type="submit"]');
await page.waitForLoadState('domcontentloaded');
if (page.url().includes('/login')) {
  console.error('LOGIN FAILED — still at', page.url());
  await browser.close(); process.exit(1);
}

// theme is stored in localStorage and applied before paint
await page.evaluate((t) => localStorage.setItem('p7-theme', t), THEME);

const findings = [];
const add = (o) => findings.push(o);

for (const { name, url } of PAGES) {
  if (ONLY && !ONLY.includes(name)) continue;

  const consoleErrors = [];
  const failedReqs = [];
  const onMsg = (m) => { if (m.type() === 'error') consoleErrors.push(m.text().slice(0, 200)); };
  const onFail = (r) => failedReqs.push(`${r.request().method()} ${r.url().slice(0, 120)}`);
  page.on('console', onMsg);
  page.on('requestfailed', onFail);
  page.on('response', (r) => { if (r.status() >= 400) failedReqs.push(`${r.status()} ${r.url().slice(0, 120)}`); });

  for (const w of WIDTHS) {
    await page.setViewportSize({ width: w, height: w < 500 ? 780 : 900 });
    await page.goto(BASE + url, { waitUntil: 'networkidle' }).catch(() => {});
    await page.waitForTimeout(120);

    const m = await page.evaluate(() => {
      const vw = window.innerWidth;
      const r = (el) => el.getBoundingClientRect();
      const vis = (el) => {
        const s = getComputedStyle(el);
        return s.display !== 'none' && s.visibility !== 'hidden' && el.offsetParent !== null;
      };

      // horizontal overflow
      const docOverflow = document.documentElement.scrollWidth - vw;
      const bodyOverflow = document.body.scrollWidth - vw;

      // elements poking past the right edge
      const wide = [];
      for (const el of document.querySelectorAll('body *')) {
        if (!vis(el)) continue;
        const b = r(el);
        if (b.width === 0) continue;
        if (b.right > vw + 1.5) {
          const s = getComputedStyle(el);
          // a scroll container at ANY depth is allowed to hold wider content
          if (s.overflowX === 'auto' || s.overflowX === 'scroll' || s.overflowX === 'hidden') continue;
          let anc = el.parentElement, scrollable = false;
          while (anc && anc !== document.body) {
            const as = getComputedStyle(anc);
            if (as.overflowX === 'auto' || as.overflowX === 'scroll' || as.overflowX === 'hidden') { scrollable = true; break; }
            anc = anc.parentElement;
          }
          if (scrollable) continue;
          wide.push({ sel: el.tagName.toLowerCase() + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0,2).join('.') : ''), right: Math.round(b.right) });
        }
        if (wide.length > 6) break;
      }

      // the layout spine
      const q = (s) => document.querySelector(s);
      const box = (s) => { const e = q(s); return e && vis(e) ? r(e) : null; };
      const head = box('.shell-pagehead');
      const kpi = box('.stats-grid');
      const filt = box('.filter-bar');
      const card = box('.table-card, .filter-card, .card');
      const foot = box('.table-footer');
      const content = box('.shell-content');

      // touch targets on mobile
      const small = [];
      if (vw <= 768) {
        for (const el of document.querySelectorAll('a[href], button, [role="button"], input[type="submit"]')) {
          if (!vis(el)) continue;
          const b = r(el);
          if (b.width === 0 || b.height === 0) continue;
          if (b.height < 40 || b.width < 24) {
            small.push({ sel: el.tagName.toLowerCase() + '.' + String(el.className || '').trim().split(/\s+/)[0], h: Math.round(b.height), w: Math.round(b.width) });
          }
          if (small.length > 8) break;
        }
      }

      // clipped text (an ellipsis is fine; a hard clip is not)
      const clipped = [];
      for (const el of document.querySelectorAll('h1, h2, h3, .shell-pagehead-title, .stat-val, .stat-lbl, .card-title, td, th')) {
        if (!vis(el)) continue;
        const s = getComputedStyle(el);
        if (el.scrollWidth > el.clientWidth + 2 && s.textOverflow !== 'ellipsis' && s.overflowX !== 'auto' && s.overflow !== 'auto') {
          clipped.push(el.tagName.toLowerCase() + '.' + String(el.className || '').trim().split(/\s+/)[0]);
        }
        if (clipped.length > 6) break;
      }

      // sidebar / mobile chrome
      const sb = box('.shell-sidebar');
      const tb = box('.shell-topbar');
      const tab = box('.bottom-tabbar');
      const mtop = box('.topbar');

      return {
        vw, docOverflow, bodyOverflow, wide, small, clipped,
        head: head && { top: Math.round(head.top), h: Math.round(head.height), left: Math.round(head.left) },
        kpiTop: kpi && Math.round(kpi.top),
        gapHeadKpi: head && kpi ? Math.round(kpi.top - head.bottom) : null,
        gapKpiFilter: kpi && filt ? Math.round(filt.top - kpi.bottom) : null,
        contentPadLeft: content ? Math.round(content.left) : null,
        footVisible: !!foot,
        sidebar: sb ? Math.round(sb.width) : 0,
        topbar: tb ? Math.round(tb.height) : 0,
        tabbar: tab ? Math.round(tab.height) : 0,
        mobileTopbar: mtop ? Math.round(mtop.height) : 0,
      };
    });

    add({ page: name, w, ...m });

    if (SHOTS.includes(w)) {
      await page.screenshot({ path: `${OUT}/${name}__${w}__${THEME}.png`, fullPage: w <= 768 ? false : true });
    }
  }

  page.off('console', onMsg);
  page.off('requestfailed', onFail);
  if (consoleErrors.length || failedReqs.length) {
    add({ page: name, w: 'any', consoleErrors: [...new Set(consoleErrors)].slice(0, 4), failedReqs: [...new Set(failedReqs)].slice(0, 4) });
  }
}

fs.writeFileSync(`${process.env.SP}/findings-${THEME}.json`, JSON.stringify(findings, null, 1));
console.log('pages:', new Set(findings.map(f => f.page)).size, 'measurements:', findings.length);
await browser.close();
