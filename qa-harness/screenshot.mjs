import { chromium } from '/var/www/realstate/node_modules/playwright/index.mjs';
const [urls, width, theme, out, full] = [process.env.URLS.split(','), +process.env.W, process.env.THEME||'light', process.env.OUT, process.env.FULL==='1'];
const b = await chromium.launch();
const ctx = await b.newContext({viewport:{width: 1440, height: 900}});  // log in at desktop width; the login page has a separate mobile form
const p = await ctx.newPage();
await p.goto('http://127.0.0.1:8199/login',{waitUntil:'networkidle'});
await p.fill('#login','qa-visual@example.com'); await p.fill('#password','qa-visual-pass');
await p.click('form:has(#login) button[type="submit"]'); await p.waitForLoadState('networkidle');
await p.evaluate(t=>localStorage.setItem('p7-theme',t), theme);
await p.setViewportSize({width, height: width < 500 ? 780 : 900});
for (const u of urls) {
  const name = u.replace(/^\//,'').replace(/\//g,'_') || 'root';
  await p.goto('http://127.0.0.1:8199'+u,{waitUntil:'networkidle'});
  await p.waitForTimeout(150);
  await p.screenshot({path:`${out}/${name}__${width}__${theme}${full?'__full':''}.png`, fullPage: full});
  const o = await p.evaluate(()=>document.documentElement.scrollWidth - window.innerWidth);
  console.log(`${name} @${width} overflow=${o}px`);
}
await b.close();
