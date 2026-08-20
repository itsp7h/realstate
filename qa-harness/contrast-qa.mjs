import { chromium } from '/var/www/realstate/node_modules/playwright/index.mjs';
import fs from 'fs';
const PAGES = JSON.parse(fs.readFileSync(process.env.SP + '/routes.json', 'utf8'));
const THEME = process.env.THEME || 'dark';
const b = await chromium.launch();
const ctx = await b.newContext({viewport:{width:1440,height:1000}});
const p = await ctx.newPage();
await p.goto('http://127.0.0.1:8199/login',{waitUntil:'networkidle'});
await p.fill('#login','qa-visual@example.com'); await p.fill('#password','qa-visual-pass');
await p.click('form:has(#login) button[type="submit"]'); await p.waitForLoadState('networkidle');
await p.evaluate(t=>localStorage.setItem('p7-theme',t), THEME);

const all = [];
for (const {name,url} of PAGES) {
  await p.goto('http://127.0.0.1:8199'+url,{waitUntil:'networkidle'});
  await p.waitForTimeout(120);
  const r = await p.evaluate((theme) => {
    const lum = (c) => { const s=c.map(v=>{v/=255; return v<=0.03928?v/12.92:Math.pow((v+0.055)/1.055,2.4);}); return 0.2126*s[0]+0.7152*s[1]+0.0722*s[2]; };
    const parse = (str) => { const m=str.match(/rgba?\(([^)]+)\)/); if(!m) return null; const p=m[1].split(',').map(Number); return {rgb:[p[0],p[1],p[2]], a: p.length>3?p[3]:1}; };
    const over = (fg,bg) => fg.a>=1?fg.rgb:fg.rgb.map((v,i)=>Math.round(v*fg.a+bg[i]*(1-fg.a)));
    // Composite every semi-transparent layer over the one behind it, all the
    // way to the root. Compositing over white instead (the naive version)
    // reports a dark-theme tone chip as a pale mint and cries wolf.
    // An ancestor painted with a gradient reports backgroundColor:transparent,
    // so a colour-only walk sails past it and measures against the page. Those
    // cases are flagged for a look instead of being scored wrongly.
    const onGradient = (el) => {
      let n = el;
      while (n && n !== document.documentElement) {
        if (getComputedStyle(n).backgroundImage !== 'none') return true;
        n = n.parentElement;
      }
      return false;
    };
    const bgOf = (el) => {
      const layers = [];
      let n = el;
      while (n && n !== document.documentElement) {
        const c = parse(getComputedStyle(n).backgroundColor);
        if (c && c.a > 0.001) layers.push(c);
        n = n.parentElement;
      }
      const rootC = parse(getComputedStyle(document.documentElement).backgroundColor);
      let base = (rootC && rootC.a > 0.9) ? rootC.rgb
               : (getComputedStyle(document.documentElement).getPropertyValue('color-scheme').includes('dark') ? [13,18,32] : [255,255,255]);
      for (let i = layers.length - 1; i >= 0; i--) base = over(layers[i], base);
      return base;
    };
    const ratio = (a,b) => { const l1=lum(a),l2=lum(b); return (Math.max(l1,l2)+0.05)/(Math.min(l1,l2)+0.05); };
    const vis = (el) => { const s=getComputedStyle(el); return s.display!=='none'&&s.visibility!=='hidden'&&el.offsetParent!==null&&el.getBoundingClientRect().height>0; };

    const SEL = {
      'body text':      'p, td, .stat-sub, .shell-pagehead-sub',
      'heading':        'h1, h2, h3, .card-title, .shell-pagehead-title',
      'kpi label':      '.stat-lbl',
      'kpi figure':     '.stat-val',
      'table header':   'thead th',
      'badge':          '.badge, .status-badge',
      'button':         '.btn',
      'input':          'input:not([type=hidden]), select',
      'pagination':     '.page-btn',
      'nav item':       '.shell-navitem, .shell-subitem',
      'muted meta':     '.text-muted, .result-count',
      'tab':            '.tab-btn',
    };
    const out = [];
    for (const [kind, sel] of Object.entries(SEL)) {
      let n = 0;
      for (const el of document.querySelectorAll(sel)) {
        if (!vis(el) || n >= 3) continue;
        const cs = getComputedStyle(el);
        const fg = parse(cs.color); if (!fg) continue;
        if (onGradient(el)) { n++; continue; }   // measured by eye, not by maths
        const bg = bgOf(el);
        const fgc = over(fg, bg);
        const size = parseFloat(cs.fontSize);
        const bold = (parseInt(cs.fontWeight,10)||400) >= 700;
        const large = size >= 24 || (size >= 18.66 && bold);
        const need = large ? 3 : 4.5;
        const got = ratio(fgc, bg);
        if (got < need) {
          out.push({ kind, sel: el.tagName.toLowerCase()+'.'+String(el.className||'').trim().split(/\s+/).slice(0,2).join('.'),
                     ratio: +got.toFixed(2), need, size, fg: cs.color, bg: `rgb(${bg.join(',')})`,
                     text: (el.textContent||'').trim().slice(0,26) });
        }
        n++;
      }
    }
    // identical fg/bg — the white-on-white class of bug
    const same = [];
    for (const el of document.querySelectorAll('body *')) {
      if (!vis(el)) continue;
      const cs=getComputedStyle(el);
      if (!el.textContent || !el.textContent.trim()) continue;
      const fg=parse(cs.color); const bgc=parse(cs.backgroundColor);
      if (fg && bgc && bgc.a>0.9 && fg.rgb.join()===bgc.rgb.join()) {
        same.push(el.tagName.toLowerCase()+'.'+String(el.className||'').slice(0,30));
      }
      if (same.length>3) break;
    }
    return { fails: out, same, theme };
  }, THEME);
  if (r.fails.length || r.same.length) all.push({page:name, ...r});
}
fs.writeFileSync(`${process.env.SP}/contrast-${THEME}.json`, JSON.stringify(all,null,1));
console.log(`${THEME}: pages with contrast issues: ${all.length}`);
await b.close();
