---
name: responsive-design
description: Makes every layout correct at mobile, tablet, and desktop widths without ever regressing another breakpoint.
---

# Responsive Design

You make layouts work at every width the app promises to support, using the
app's existing two-tier mobile strategy — never by inventing a third one, and
never by patching a small screen at the expense of a wider one.

## The contract you're working inside

- **Breakpoints, six only** (`app-core.css` §1.0): `430px` small phone,
  `600px` large phone/small tablet, `768px` **the** mobile/desktop boundary,
  `900px` aside stacking, `1200px` wide-desktop reflow, `1400px` KPI-strip
  reflow. A page that invents `620px` or `820px` puts a fold in the layout
  that exists on that page and nowhere else — don't.
- **Two mobile systems, pick the one the route already uses:**
  - Routes listed in `$mobileRedesignedRoutes` (`layouts/admin.blade.php`) get
    the dedicated mobile app layer — `app-mobile.css`'s `.m-*`/`.pm-*` classes,
    authored as their own design, not a squeezed desktop. `app-mobile.css`
    loads **last** in the cascade and is meant to win over page CSS — that's
    how it turns every `.modal-overlay` into a bottom sheet.
  - Everything else falls back to `app-core.css`'s generic responsive
    behaviour: tables convert to stacked label/value cards ≤768px, which
    requires every `<td>` to carry `data-label="…"`.
- **Report/comparison tables are the deliberate exception**: pages doing
  column comparison (`reports/*`, financial matrices) use
  `.table-wrap.is-scroll` and keep scrolling instead of stacking to cards.
  That's intentional — don't force card mode onto them.

## Widths to test

`320, 375, 390, 414/430, 768, 1024, 1440` — in both light and dark theme. The
committed harness (`qa-harness/responsive-qa.mjs`) already sweeps
`320/375/390/430/768/1024/1440` against every route in `qa-harness/routes.json`
and reports horizontal overflow; use it rather than eyeballing a resize.

## Rule: never fix mobile by breaking desktop

A mobile symptom gets fixed inside `@media (max-width: 768px)` (or the
`.m-*`/`.pm-*` layer, or that route's mobile partial) — never by changing a
shared component's unqualified (desktop-applying) rule. If a fix to a shared
class in `app-core.css` §4 needs to change at all, verify the change at 1440px
and 1024px before calling it done, not just at the width where you found the
bug.

## Workflow

1. Identify the route's mobile treatment: check `$mobileRedesignedRoutes` in
   `layouts/admin.blade.php`. That decides which system you're extending.
2. Reproduce the issue at the specific width(s) it occurs — don't guess from
   a screenshot at one size.
3. Fix inside the correct layer (page's `@media (max-width:768px)` block, or
   the `.m-*`/`.pm-*` partial, or — only if the underlying component itself
   is wrong at that breakpoint everywhere — `app-core.css`'s own media query
   for that component).
4. Check horizontal overflow specifically: nothing should force `<body>` to
   scroll sideways at any tested width.
5. Check touch targets ≥44px on any control reachable ≤768px (buttons, icon
   buttons, tab buttons, modal close) — `app-core.css` already lifts `.btn`,
   `.page-btn`, `.topbar-icon-btn`, `.tab-btn` to `--h-control-lg` on mobile;
   extend the same pattern to anything new rather than a one-off height.
6. Re-run the full width sweep (not just the width you fixed) plus the
   guard-rail tests (`php artisan test --filter=UiConsistencyTest`,
   `--filter=AllPagesRenderTest`) to confirm nothing else moved.
7. Look at the actual screenshots the harness produces
   (`qa-harness/screenshot.mjs`). Overflow/contrast measurement catches
   what's broken; only eyes catch "this looks cramped/unfinished."

## Do not

- Invent a breakpoint. `UiConsistencyTest::test_pages_only_use_the_documented_breakpoints`
  will fail the build if you do.
- Duplicate `.pm-*` mobile markup into the desktop template, or vice versa —
  they are two designs, not one design at two sizes.
- Force a deliberately-scrolling report table into card mode "for
  consistency."
- Touch `.m-*`/`.pm-*` CSS while fixing a desktop issue, or vice versa, unless
  the bug is proven to exist in both.

For the deep reference (exact class names, safe-area handling, view
transitions), read `.claude/skills/frontend-expert/references/responsive-mobile.md` —
this skill is the workflow; that file is the lookup table.
