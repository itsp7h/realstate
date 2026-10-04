# Handoff — UI/UX visual QA pass (continue from here)

You are continuing a **visual QA and polish pass** on a Laravel 12 + Blade real-estate
admin app at `/var/www/realstate`. A large design-system refactor is **already finished**.
**Do not start another architectural refactor.** Your job is the remaining polish items,
each verified in a real browser.

---

## 1. Read first

- `CLAUDE.md` — project rules. The ones that bite:
  - **Never touch the database** (no migrate/seed/delete) without asking in the moment.
  - **Never touch production (192.168.0.48)** or SSH to staging (192.168.0.50).
  - Work locally; staging deploys from `development` via PR.
- `DESKTOP-UI.md` — the design system spec (tokens, archetypes, §8 accessibility).
- `DASHBOARD-SPEC.md` — supersedes DESKTOP-UI §4/§5A for the shell + dashboard.
- `public/css/app-core.css` — the design system. §1 tokens, §1.0 breakpoint contract,
  §4 components, §7 desktop shell. Read the section for any component you touch; each one
  documents its markup contract and why it exists.

## 2. State of the work

**Tests: 574 passing.** Run `php artisan test`. Keep it green.

Three test files are the guard rails — do not weaken them, extend them:
- `tests/Feature/UiConsistencyTest.php` — 10 static invariants (one page header, one KPI
  anatomy, every table scrollable, no page CSS redefining a shared component, no raw hex in
  page CSS, no section margins in page CSS, filter bars inside cards, documented breakpoints
  only, no third-party UI framework in any view).
- `tests/Feature/AllPagesRenderTest.php` — renders every page route, asserts the shell.
- `tests/Feature/DesktopShellTest.php` — sidebar structure, submenu, disabled routes,
  no page missing from the sidebar.

Metrics now vs before the refactor:

| | before | now |
|---|---|---|
| page-local CSS lines | 3,600 | 3,420 |
| raw hex in app views | 493 | 14 (all in the mobile `.m-*` layer or a `<meta theme-color>`) |
| hardcoded font-size | 400 (27 distinct) | 35 (17 distinct) |
| distinct breakpoints | 11 | 5 |
| Bootstrap CDN pages | 1 | 0 |
| contrast failures (both themes, 40 pages) | — | **0** |
| horizontal overflow (40 pages × 7 widths) | — | **0** |

## 3. Already verified in a real browser — don't redo

Playwright + Chromium are installed. 40 pages measured at **320 / 375 / 390 / 430 / 768 /
1024 / 1440**, plus `buildings/show` and `tenants/show`.

Fixed and confirmed: sticky form action bar overflow (2 pages), dashboard desktop block
leaking at 320px, `.stats-grid.is-triple` not collapsing, an unbreakable tenant name pushing
the page wide, closed modals extending page width, the mobile `.pm-push-header` rendering on
desktop, the sidebar's last nav item clipped, `.req` asterisk never defined (3 forms),
`buildings/show` migrated off Bootstrap entirely (tabs, modal, lightbox, tables, KPIs, grid),
KPI row now lands at the same y (203px) on all 14 pages that have one, gold record numbers
promoted to a `.cell-id` component with AA-compliant ink.

## 4. How to run the browser QA

The harness is committed at `qa-harness/`. It runs against a **copy** of the SQLite DB so the
project database is never written to.

```bash
cd /var/www/realstate
SP=/tmp/qa && mkdir -p $SP/shots

# 1. copy the DB and add a QA user TO THE COPY ONLY
cp database/database.sqlite $SP/qa.sqlite
DB_DATABASE=$SP/qa.sqlite php artisan tinker --execute='
  $u = App\Models\User::firstOrNew(["email" => "qa-visual@example.com"]);
  $u->name="QA Visual"; $u->role="admin"; $u->password=bcrypt("qa-visual-pass"); $u->save();'

# 2. serve it. NOTE: `php artisan serve` does NOT forward DB_DATABASE to its child,
#    so use the built-in server with the router directly.
cp qa-harness/router.php $SP/router.php
DB_DATABASE=$SP/qa.sqlite APP_ENV=local APP_DEBUG=true APP_URL=http://127.0.0.1:8199 \
  php -S 127.0.0.1:8199 -t /var/www/realstate/public $SP/router.php &

# 3. run the sweeps (login is at desktop width: the login page has a separate mobile form)
cp qa-harness/*.mjs $SP/
sed -i "s#from 'playwright'#from '/var/www/realstate/node_modules/playwright/index.mjs'#" $SP/*.mjs
SP=$SP SHOTS=375,1440 THEME=light node $SP/responsive-qa.mjs   # → $SP/findings-light.json
SP=$SP THEME=dark  node $SP/contrast-qa.mjs                    # → $SP/contrast-dark.json
SP=$SP URLS=/dashboard,/invoices W=375 FULL=1 OUT=$SP/shots THEME=dark node $SP/screenshot.mjs
```

Then **look at the PNGs**. Measurement finds overflow and contrast; only your eyes find
"this looks unfinished". Two harness traps already hit and fixed — keep them in mind if you
extend it: composite semi-transparent backgrounds over the real parent (not white), and skip
elements whose ancestor is painted with a **gradient** (`backgroundColor` reports
`transparent` there).

Verify with the QA placeholder images already in `storage/app/public/buildings/` and the
`public/storage` symlink (created locally; harmless).

## 5. What is left to do

### 5.1 Remaining page-local chips (15) — consolidate only where genuinely reusable
```
.badge-custom .field-section-tag        form-configs/edit
.cap-source-badge .unit-tag            ewa-bills/create
.ewa-badge                             ewa-bills/show
.mquot-pill                            maintenance/index
.quot-file-pill                        maintenance/create
.type-pill .type-pills                 invoices/create
.smart-detect-badge(es)                dashboard
.photo-count-badge .bldg-card-badges   buildings/index
.m-lock-badge                          property-units/index   (mobile layer — leave)
.is-pill                               buildings/show         (page shim — fine)
```
`.type-pill` and `.quot-file-pill`/`.mquot-pill` are **interactive chips** (selectable /
file chips), not badges — if the pattern repeats on 2+ pages, promote ONE chip component to
`app-core.css`; otherwise leave them. Wrappers ending in `-badges`/`-pills` are layout
containers, not components. Do **not** create a component just to lower the count.

### 5.2 Typography review (spec vs rendered)
356 page font-sizes were snapped onto app-core's 9-step scale. `DASHBOARD-SPEC.md` pins
some exact values (11.5 / 12.5 / 13.5 / 19 / 30 / 34px) for the shell and dashboard.
Compare the rendered dashboard and shell against §1–§5 of that spec and **restore the exact
values where the spec clearly requires them**; keep the scale everywhere else. 35 hardcoded
sizes remain (17 distinct) — mostly deliberate display figures (22/26/28/34/36/38/40px).
Don't make arbitrary changes.

### 5.3 Mobile tables
`data-label` was added to ~100 cells across 11 views so tables stack as labelled cards
≤768px. **Verify in the browser at 320/375px**: labels clear, values readable, cards not
absurdly tall, action buttons still reachable, nothing important hidden. The 10 report pages
use `.table-wrap.is-scroll` on purpose (column comparison is the point) — leave them
scrolling. Do not force card mode on them.

### 5.4 Touch targets
At ≤768px these measured under 40px high: `a.inline-flex` (19px) on admin/audit-log and
buildings/create, `a`/`button` (31px) and `.modal-close-btn` (32px) on buildings/index,
`.dash-legend-btn` (33px) on dashboard. app-core already lifts `.btn`, `.page-btn`,
`.topbar-icon-btn`, `.tab-btn` to `--h-control-lg` on mobile — extend that to these, or give
them an invisible hit area (the pattern is already used on `.dash-legend-btn::after`).

### 5.5 Marginal / observational
- `td` clipped without ellipsis at 320px on admin/audit-log, invoices/create, users/index —
  check whether those cells are in card mode or scroll mode before touching.
- `ewa-bills/index` header→KPI gap is 210px because a module tab bar sits between them, and
  `reports/profit-loss` puts filters before its KPIs. Both are intentional structure. Leave
  them; just don't let a third variant appear.
- **Deploy gap (not UI):** `.github/workflows/deploy.yml` never runs `php artisan
  storage:link`, so building photos 403 on a fresh deploy. Report it; don't change workflows
  in a UI pass.

## 6. Constraints

Do NOT change: routes, controllers, database logic, permissions, business rules, form
behaviour, API behaviour, existing workflows. Presentation only.

Do NOT: introduce a second visual language, add a global utility layer, re-add a CDN
framework, hardcode a colour outside `:root`/`[data-theme]`, invent a breakpoint outside the
six in app-core §1.0, or set a section margin in page CSS (`.shell-content`'s `gap` owns the
vertical rhythm).

## 7. ⚠ Concurrency warning

**Another Claude session has been editing this repo during this work.** Evidence: a test
method appeared in `UiConsistencyTest.php` that this session did not write, and a
`dashboard.blade.php` token change was reverted to `#fff` inside a block rewritten with
`'Poppins', system-ui` and `rem` values.

Before you start: check `git status`, and `find resources public/css tests -newermt '-5
minutes'`. If another session is active, work in an isolated git worktree or coordinate —
otherwise you will silently overwrite each other. Nothing is committed yet (**77 changed
paths in the working tree**), and some of those changes belong to the other session, so
review `git diff` before committing anything.

## 8. Definition of done

40 pages → one design system → consistent desktop → consistent mobile → no visual outliers.
Finish with: `php artisan test` green, both browser sweeps at 0 findings, screenshots
actually looked at, and a report listing pages inspected, widths tested, issues found,
issues fixed, remaining exceptions, test count, metrics, and known limitations. Do not claim
responsive QA unless you opened the pages in a browser.
