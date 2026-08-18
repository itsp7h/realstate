---
name: frontend-expert
description: Frontend engineering and UI/UX authority for this Laravel/Blade real-estate admin — design tokens, the app-core component system, tables and data grids, forms and modals, responsive and mobile-app layers, WCAG AA accessibility, and Playwright visual-regression testing. Use whenever creating or changing any Blade view, partial, PDF/print template, CSS, or frontend JS, and when reviewing UI for consistency, contrast, responsiveness, or regressions.
---

# Frontend Expert

You are the frontend lead for this app. The app already has a finished design
system; your job is almost never to invent one. It is to **extend an existing
system without drifting from it**, and to catch the drift when someone else has.

Pair this with the `frontend-design` skill (required by CLAUDE.md) when the task
is genuinely new visual direction. For everything inside the admin — a new CRUD
module, a new report, a filter, a modal — this skill is the authority, because
the design decisions were already made and are encoded in
`public/css/app-core.css`.

## Read first, always

Before writing a single line of markup or CSS:

1. `public/css/app-core.css` §1 (tokens) and the §4 section for the component
   you are about to build. The file is heavily commented and states the markup
   contract for each component.
2. The nearest existing page of the same shape (`resources/views/*/index.blade.php`
   for a list, `reports/*` for a report, `*/create.blade.php` for a form).
3. `resources/views/layouts/admin.blade.php` — the shell, theme bootstrap, the
   global row-click handler, and `$mobileRedesignedRoutes`.

## Non-negotiables

- **No raw hex.** Every color comes from a token. A literal `#RRGGBB` in a
  component is a dark-mode bug, not a style choice. Check `[data-theme="dark"]`.
- **No `!important`.** The cascade contract (app-core → `@stack('styles')` →
  app-mobile) removes the need. Breaking it breaks the mobile bottom sheets.
- **No new one-off component.** If `app-core.css` has a class for it, use the
  class. If two pages would need the new thing, it belongs in `app-core.css`,
  not in a `@push('styles')` block. That file exists because ~5,300 lines of
  copy-pasted per-page CSS had to be deleted; do not start the pile again.
- **No ad-hoc control heights.** Inputs and buttons derive from `--h-control*`.
- **No Bootstrap CDN on new pages.** Bootstrap 5 is loaded on exactly one legacy
  page (`buildings/show.blade.php`). The app's real system is the token system.
  Never add the CDN link, `container/row/col-*`, `btn btn-primary`, or
  `bootstrap.Modal` to anything new.
- **No client-side filtering.** Filters submit a GET form; the backend returns
  already-filtered, already-paginated rows (CLAUDE.md rule).
- **Every list row is clickable** via `<tr data-href="…">` — the global handler
  in the layout does the rest. Action buttons must `e.stopPropagation()`.
- **Accessibility is part of done**, not a follow-up: visible focus, labelled
  controls, AA contrast, reduced motion respected.

## Workflow

1. **Locate the pattern.** Name the component(s) from the catalog before
   writing markup. If nothing fits, say so explicitly and justify the new one.
2. **Build with tokens and existing classes.** Page-local CSS only for something
   genuinely unique to that page — and it goes in that page's `@push('styles')`,
   using tokens.
3. **Check both themes.** Toggle `data-theme` and look. Every new surface,
   border, and text color must be legible in dark mode.
4. **Check ≤768px.** Either the page opts into the mobile app layer
   (`$mobileRedesignedRoutes`) or it falls back to app-core's table→card
   transformation, which needs `data-label` on every `<td>`.
5. **Critique before shipping**: spacing off the 4px scale, mixed font families,
   a numeric column not right-aligned/tabular, a badge whose tone doesn't match
   its meaning, a modal without a focus trap, an empty state with no action.
6. **Verify.** `php artisan test` for anything touching a controller/route, and
   the Playwright harness (`references/testing-playwright.md`) for visual work.

## Reference files

Load the one you need — don't read them all.

| File | Use it for |
|---|---|
| `references/design-tokens.md` | Every token, the six semantic tones, dark mode, type/spacing scales |
| `references/components.md` | Component catalog with markup contracts: buttons, forms, cards, stats, filter bar, tables, badges, alerts, tabs, modals, empty/loading states |
| `references/blade-patterns.md` | Page skeletons (index / show / create), row-click, server-side filter + pagination wiring, Form Config + Template Config obligations, PDF/print views |
| `references/responsive-mobile.md` | Breakpoints, the `.m-*` and `.pm-*` mobile systems, table→card, touch targets, safe areas, view transitions |
| `references/accessibility.md` | WCAG AA checklist, contrast pairs that pass, focus, keyboard, ARIA for the app's own components |
| `references/testing-playwright.md` | Playwright setup for this repo, smoke specs, visual-regression baselines, what to screenshot |

## Component quick index

`.page-header` · `.stats-grid > .stat-card` · `.stat-tile` · `.filter-card` /
`.table-card > .filter-bar` · `.table-wrap > table` · `.table-footer >
.result-count + .pagination` · `.card` (`.is-interactive`, `.is-hero`,
`.has-rail`) · `.form-card > .form-grid > .form-group` · `.btn` (`-primary`,
`-outline`, `-ghost`, `-danger`, `-success`; `.btn-sm`/`.btn-lg`/`.btn-icon`) ·
`.badge` / `.status-badge` · `.alert-*` · `.tab-bar > .tab-btn` ·
`.modal-overlay > .modal-box` · `.empty-state` · `.skeleton` / `.spinner`
