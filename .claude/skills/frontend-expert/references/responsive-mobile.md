# Responsive & mobile

The app is a real installable PWA (manifest, service worker, apple touch icons,
`viewport-fit=cover`). Mobile is not a shrunk desktop — below 768px the app is
expected to read as a native app.

## Cascade contract (do not break)

`layouts/admin.blade.php` loads, in this exact order:

1. `app-core.css`
2. `@stack('styles')` — per-page overrides
3. `app-mobile.css` — **intentionally last, so it wins**

Step 3 is why every `.modal-overlay` in the app becomes a bottom sheet on mobile
without a per-page copy. Never reorder, and never use `!important` to fight it.

## Breakpoints

- `≤768px` — the mobile app boundary. Sidebar collapses, tab bar appears, tables
  transform, modals become sheets.
- `≤480px` — extra tightening only. Don't introduce new component behaviour here.
- Desktop is the default; write mobile rules inside `@media (max-width: 768px)`.

## Three mobile systems

A screen is on exactly one of these:

| System | What it is | Where |
|---|---|---|
| `.pm-*` | Current "Property Manager" pass: headers, KPI cards, property cards, action rows, FAB, hero, floors/units lists, plus the opt-in Depth & Motion layer (ripple, collapsing large title, sliding segmented control, parallax, pull-to-refresh) | `app-mobile.css` |
| `.m-*` | First mobile pass: `.m-screen`, `.m-action-row`, `.m-mini-stat`, `.m-search-input`, `.m-chip-row > .m-chip`, `.m-row-list > .m-row-card` (`.m-row-icon/.m-row-title/.m-row-sub/.m-row-badge/.m-row-chevron`), `.m-empty` | `app-mobile.css` |
| fallback | No opt-in: app-core §5 responsive rules apply, including the table→card transformation | `app-core.css` |

Opt-in is per route via `$mobileRedesignedRoutes` in the layout, which adds
`is-mobile-screen` to `<body>`. **Prefer `.pm-*` for new screens**; `.m-*` is
maintained, not extended.

A mobile screen is authored as a separate block in the same Blade file
(`<div class="m-screen">…</div>` / the `.pm-*` markup), hidden on desktop by the
mobile stylesheet — not as a second route or a duplicated controller.

## Table → card (fallback path)

Below 768px each row becomes a stacked key/value card built from each cell's
`data-label`. So:

- `data-label` on every `<td>` is not optional.
- A wide financial matrix that must stay a grid gets `.table-wrap.is-scroll` —
  ageing reports, rent schedules, anything where comparing across columns *is*
  the task. Everything else stacks.

## Touch & ergonomics

- Minimum touch target 44×44px (`--h-control-lg`). `.btn-sm` in a table row is
  fine on desktop, but on a mobile card it must grow.
- Primary actions sit within thumb reach: bottom sheet footer, tab bar, FAB —
  not pinned to the top-right.
- Respect safe areas: `env(safe-area-inset-bottom)` on the tab bar and sheet
  footers; the layout already sets `viewport-fit=cover`.
- No hover-only affordances. Anything revealed on `:hover` needs a tap path.
- Inputs: correct `inputmode`/`type` (`inputmode="decimal"` for money,
  `type="date"`, `type="search"`) so the right keyboard appears. Font-size ≥16px
  on focusable inputs where iOS would otherwise zoom.

## Motion & view transitions

`app-mobile.css` opts every same-origin navigation into the View Transitions API
(`@view-transition { navigation: auto }`). Pushed screens
(`$pushedScreenRoutes`) get a directional slide: the back chevron must carry
`.pm-push-back` so the layout's script flags the navigation as a "pop"; anything
else defaults to "push".

Everything is wrapped by `@media (prefers-reduced-motion: reduce)`, which
disables view transitions and the Depth & Motion helpers. Any new animation you
add must be too.

The Depth & Motion helpers are declared right after `<body>` opens and are all
opt-in — they only touch elements carrying the marker class, so a page that
doesn't use them is unaffected. Call them from the page's `@push('scripts')`.

## Checklist before calling a screen responsive

- [ ] 360px wide: nothing clipped, no horizontal page scroll
- [ ] Tables either stack correctly (`data-label`) or are deliberately
      `.is-scroll`
- [ ] Modal opens as a bottom sheet and is dismissible by drag and by button
- [ ] Filters usable one-handed (chips or a sheet, not a 5-column bar)
- [ ] Tap targets ≥44px, spacing off the `--sp-*` scale
- [ ] Both themes checked at mobile width
- [ ] `prefers-reduced-motion` honoured
