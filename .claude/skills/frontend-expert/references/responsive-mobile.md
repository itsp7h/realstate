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
| `.m-*` | The list-screen system: `.m-screen`, `.m-action-row`, `.ps-stat-strip > .ps-stat`, `.m-search`, `.m-chip-row > .m-chip` (`.m-chip-count`, `.m-chip-sep`), `.m-row-list > .m-row-card` (`.m-row-thumb/.m-row-title/.m-row-sub/.m-row-occ/.m-row-amount/.m-row-chevron`), `.m-empty`. Assembled by `components/mobile-list` — see below. `.m-mini-stat`, `.m-search-input`, `.m-row-icon` and `.m-row-badge` were retired into these. | `app-mobile.css` |
| fallback | No opt-in: app-core §5 responsive rules apply, including the table→card transformation | `app-core.css` |

Opt-in is per route via `$mobileRedesignedRoutes` in the layout, which adds
`is-mobile-screen` to `<body>`. **Prefer `.pm-*` for new screens**; `.m-*` is
maintained, not extended.

A mobile screen is authored as a separate block in the same Blade file
(`<div class="m-screen">…</div>` / the `.pm-*` markup), hidden on desktop by the
mobile stylesheet — not as a second route or a duplicated controller.

## List screens: one order, from the component

Every filtered list on the phone comes in this order, and no page writes it:

    header → actions (Add + More) → stats → search → chips → rows

`components/mobile-list.blade.php` draws it. A page says what it *has* and the
component decides where it goes:

```blade
<x-mobile-list :actions="[...]" :stats="[...]" :search="[...]" :chips="$chips">
    @forelse($rows as $row) … .m-row-card … @empty … .m-empty … @endforelse
</x-mobile-list>
```

- Every prop is optional — Payments has no create verb, Maintenance no figures,
  Floors no search term. An omitted section closes the gap; it does not leave
  one.
- The component also owns the **reveal queue**: each section takes the next
  `--ps-step` and hands the rows the one after as `--ps-row-step`, which the
  `.m-row-list > :nth-child()` rules turn into each row's delay. Never write
  `--ps-step` on a row by hand — the number depends on how many sections are
  above it, which is not a page's business.
- Sections sit **12px** apart, declared once on `.m-screen`. The `16px` above
  the first one is the page inset, not a section gap.
- Two facets in one chip row (Buildings filters by type *and* ownership) are
  separated by `['sep' => true]` → `.m-chip-sep`, and compose in the URL: a
  chip carries the other facet through and clears itself when it is the
  active one.
- `tests/Feature/MobileListOrderTest.php` pins all of the above, per route.

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
