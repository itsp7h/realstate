# Playwright & visual regression

Playwright is **not installed yet** in this repo (`package.json` has only Vite +
Tailwind tooling). Everything below is the setup to use when UI work needs
browser verification. Install it in its own branch (`chore/playwright-setup`),
not bundled into a feature branch.

Ground rules first:

- Run against **local only** (`php artisan serve`). Never point a browser suite
  at production (192.168.0.48) or staging (192.168.0.50).
- Specs must not mutate the database unless the user has explicitly approved it
  in that moment (CLAUDE.md). Default to read-only navigation and screenshots;
  when a spec must create data, use a dedicated throwaway sqlite file via
  `.env.testing`, never `database/database.sqlite`.
- `php artisan test` stays the primary suite. Playwright covers what PHPUnit
  can't see: layout, theme, responsive behaviour, and visual drift.

## Install

```bash
npm i -D @playwright/test
npx playwright install chromium
```

`package.json` scripts:

```json
"test:e2e":  "playwright test",
"test:ui":   "playwright test --ui",
"test:snap": "playwright test --update-snapshots"
```

## `playwright.config.js`

```js
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    snapshotDir: './tests/Browser/__snapshots__',
    fullyParallel: true,
    reporter: [['html', { outputFolder: 'storage/playwright-report' }]],
    use: {
        baseURL: process.env.APP_TEST_URL ?? 'http://127.0.0.1:8000',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    expect: {
        // Anti-aliasing and font hinting move a few pixels; real drift moves many.
        toHaveScreenshot: { maxDiffPixelRatio: 0.01, animations: 'disabled' },
    },
    projects: [
        { name: 'desktop-light', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 }, colorScheme: 'light' } },
        { name: 'desktop-dark',  use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 }, colorScheme: 'dark' } },
        { name: 'mobile',        use: { ...devices['iPhone 13'] } },
    ],
    webServer: {
        command: 'php artisan serve --port=8000',
        url: 'http://127.0.0.1:8000',
        reuseExistingServer: true,
    },
});
```

## Theme control

The app reads `localStorage['p7-theme']` before first paint, so `colorScheme`
alone is not enough. Seed it in a fixture:

```js
// tests/Browser/fixtures.js
import { test as base } from '@playwright/test';

export const test = base.extend({
    page: async ({ page }, use, testInfo) => {
        const theme = testInfo.project.name.includes('dark') ? 'dark' : 'light';
        await page.addInitScript(t => localStorage.setItem('p7-theme', t), theme);
        await use(page);
    },
});
export { expect } from '@playwright/test';
```

## Smoke spec

```js
import { test, expect } from './fixtures.js';

const PAGES = ['/dashboard', '/payments', '/invoices', '/expenses', '/maintenance'];

for (const path of PAGES) {
    test(`${path} renders`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', e => errors.push(e.message));
        page.on('console', m => m.type() === 'error' && errors.push(m.text()));

        await page.goto(path);
        await expect(page.locator('h1.page-header-title')).toBeVisible();

        // No horizontal page scroll at any viewport.
        const overflow = await page.evaluate(() =>
            document.documentElement.scrollWidth > document.documentElement.clientWidth);
        expect(overflow, 'page scrolls horizontally').toBe(false);

        expect(errors).toEqual([]);
    });
}
```

## Visual regression

```js
test('payments index — visual', async ({ page }) => {
    await page.goto('/payments');
    await page.waitForLoadState('networkidle');
    await expect(page).toHaveScreenshot('payments-index.png', {
        fullPage: true,
        mask: [page.locator('[data-volatile]')],   // dates, "x minutes ago", ids
    });
});
```

Baselines live in `tests/Browser/__snapshots__/` and are committed. Regenerate
with `npm run test:snap` **only** when the change was intended, and say so in the
PR — a silently updated baseline is how visual regressions ship.

Mask anything genuinely volatile (relative timestamps, generated ids, random
seeded data) rather than loosening `maxDiffPixelRatio`.

## Component-level checks worth writing

- **Row click**: clicking a `<tr data-href>` navigates; clicking the Edit button
  inside `.action-btns` goes to the edit page instead, not the show page.
- **Filters are server-side**: applying a filter changes `page.url()` query
  params and issues a document request. If the row count changes without a
  navigation, filtering leaked to the client — that's a CLAUDE.md violation.
- **Pagination keeps filters**: page 2's URL still carries the filter params.
- **Table → card**: at the `mobile` project, `table` is hidden / rows stack, and
  each stacked key matches its `data-label`.
- **Modal → bottom sheet**: same markup, sheet on mobile; focus lands inside,
  `Esc` closes, focus returns to the trigger.
- **Empty state**: filtering to no results shows the empty state with a reset
  action.

## Accessibility in CI

```bash
npm i -D @axe-core/playwright
```

```js
import AxeBuilder from '@axe-core/playwright';

test(`${path} has no AA violations`, async ({ page }) => {
    await page.goto(path);
    const { violations } = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();
    expect(violations.map(v => v.id)).toEqual([]);
});
```

Run this in both theme projects — contrast failures usually appear in exactly
one of them.

## Reporting

Artifacts land in `storage/playwright-report/`; add that plus
`test-results/` to `.gitignore`. When reporting results, paste the actual
failure output — never claim a suite passed without running it.
