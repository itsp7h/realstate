# DESKTOP-UI.md — System-wide desktop UI specification

**Purpose:** this file is the single source of truth for the desktop (≥769px) appearance and
behaviour of the RealEstate Management Suite (Laravel + Blade, `app-core.css`).
Give this file to the AI assistant on **every** page you ask it to build or refactor.

**Prompt to paste alongside it:**

> Read `DESKTOP-UI.md` in full before writing any code. Apply it to `<page>`.
> Do not invent colours, fonts, radii, spacing or components that are not in this file.
> Do not touch the mobile (`@media max-width:768px`) `.pm-*` layer unless I ask.
> Reuse existing classes from `app-core.css` before writing new CSS. If you must add CSS,
> add it in the page's own `@push('styles')` block, namespaced to that page.
> End your reply with the "Acceptance checklist" at the bottom of this file, ticked.

---

## 1. Non-negotiables

1. **Tokens only.** Every colour, radius and shadow comes from a CSS custom property
   defined in `app-core.css`. No raw hex in page markup or page CSS — except the fixed
   status/semantic hexes listed in §3.
2. **Both themes.** Everything must be legible under `data-theme="light"` and
   `data-theme="dark"`. Never hardcode `#fff`, `#0B1120`, `#F8FAFC` as a surface.
3. **Mobile is a separate design, not a narrow desktop.** The `.pm-*` mobile screens are
   authored independently and are hidden ≥769px. Desktop work must not change them.
4. **No new fonts.** Outfit (numerals, headings), Plus Jakarta Sans (UI/body), Poppins
   (marketing/auth only). Font Awesome 6.5 solid/regular for icons.
5. **No emoji in UI.** Icons only.
6. **Server-rendered first.** Blade + progressive enhancement. No SPA framework, no
   client-side routing. Chart.js is the only charting dependency.

---

## 2. Type scale

| Role | Font | Size / weight |
|---|---|---|
| Page title (`.page-header-title`) | Outfit | 26px / 800 |
| Page subtitle (`.page-header-sub`) | Plus Jakarta Sans | 13px / 400, `--text-muted` |
| Card title | Outfit | 14–15px / 700 |
| Section title | Outfit | 16px / 700 |
| Body / table cell | Plus Jakarta Sans | 13px / 400–500 |
| Table cell, muted/secondary (`.cell-muted`) | Plus Jakarta Sans | 12px / 400, `--text-muted` — a whole de-emphasized cell (reference number, unit, "—" placeholder), not a second line under a title |
| Table header | Plus Jakarta Sans | 11px / 700, uppercase, `letter-spacing:.05em`, `--text-muted` |
| Big figure / KPI | Outfit | 26px / 800, `line-height:1` |
| Micro label | Plus Jakarta Sans | 10–11px / 700, uppercase, `letter-spacing:.05em` |

Minimum body size on desktop: **12px**. Never below.
All money is Outfit 700 and always prefixed `BHD` (3 decimals on inputs, 0 in summaries).

---

## 3. Colour

Use tokens. Reference values (light theme) for judgement only:

- Ink: `--text-primary` (navy `#1e2c4f`-family), `--text-secondary`, `--text-muted`
- Surfaces: `--card-bg`, `--page-bg`, `--input-bg`
- Lines: `--card-border`, `--input-border`
- Accent (gold): `--accent`, `--accent-dim` (tint), `--accent-glow` (shadow)
- Elevation: `--shadow-sm`, `--shadow-md`
- Radii: `--radius` (cards), `--radius-sm` (inner wells, icon tiles)

**Semantic hexes** — the only literals allowed, used for status only, never as a page or
card background:

| Meaning | Fill | Ink | Border |
|---|---|---|---|
| Positive / paid / income | `#ECFDF5` | `#047857` | `#A7F3D0` |
| Info / net / credits | `#EFF6FF` | `#1D4ED8` | `#BFDBFE` |
| Warning / partial | `#FFFBEB` | `#D97706` | `rgba(234,179,8,.4)` |
| Negative / overdue / expense | `#FEF2F2` | `#DC2626` | `#FCA5A5` |
| Neutral | `--page-bg` | `--text-secondary` | `--card-border` |

Chart series are fixed across the whole system so a colour always means the same thing:
income `#10b981`, expenses `#ef4444`, credits `#0ea5e9`, debits `#f59e0b`,
profit `#8b5cf6` (line, `tension:.4`, white points).

Accent gold is for **one** thing per screen: the primary action. Do not gold-wash cards.

---

## 4. Desktop shell (unchanged on every page)

**These numbers are final. Where any other document or the current code disagrees, this
table wins.**

| Region | Size | Notes |
|---|---|---|
| Window menu bar row | 29px | File · Edit · View · Records · Accounting · Window · Help; sync chip right |
| Icon rail | **54px** | navy `#1e2c4f`, 6 module groups, 35px icon buttons |
| Contextual sidebar | **212px** | changes with the active rail group |
| Toolbar | **44px** | back/forward, ⌘K field, density, theme, bell, avatar |
| Page header | 54px | title + subtitle left, Filter / Export / primary action right |
| Content padding | 20px 22px | on `--page-bg` |

```
┌ 29px  File Edit View Records Accounting Window Help ······· SYNCED 14:02 ┐
├ 44px  ‹ ›  [ ⌘K search ]           density · theme · bell · avatar        ┤
├──────┬────────┬──────────────────────────────────────────────────────────┤
│ rail │ side   │ 54px page header — title + sub ····· Filter Export [+]   │
│ 54px │ 212px  ├──────────────────────────────────────────────────────────┤
│ 6    │ group  │ content · padding 20px 22px · --page-bg                  │
│ icons│ label  │                                                          │
│      │ items  │                                                          │
│      │ user   │                                                          │
└──────┴────────┴──────────────────────────────────────────────────────────┘
```

**Rail groups and sidebar contents** — fixed; this is the whole navigation model:

| Rail group | Icon | Sidebar label · hint | Items |
|---|---|---|---|
| Overview | `fa-gauge-high` | OVERVIEW · Portfolio at a glance | Dashboard · Activity feed |
| Property | `fa-building` | PROPERTY MANAGEMENT · 4 buildings · 18 floors · 65 units | Buildings · Units · Floors · Smart import |
| Leasing | `fa-file-signature` | LEASING · Tenants and contracts | Tenants · Lease contracts · Renewals due |
| Accounting | `fa-receipt` | ACCOUNTING · August 2026 · BHD | Invoices · Payments · Expenses · EWA meters |
| Operations | `fa-screwdriver-wrench` | OPERATIONS · Requests and vendors | Maintenance · Vendors |
| Analytics | `fa-chart-pie` | ANALYTICS · Reports and exports | Report library · Revenue analysis |
| Gear, rail bottom | `fa-gear` | ADMINISTRATION · Access and system | Users & roles · Audit log |

Sidebar group label: 9.5px/600, `letter-spacing:.19em`, gold `#caa14f`, uppercase.
Hint line under it: 11px, `--text-muted`. Nav item: 12px, padding 8px 9px, radius 8px.
Active nav item: `--accent-dim` fill, gold ink, `box-shadow: inset 2px 0 0 #caa14f`.
Count badge right-aligned, 9.5px/600, monospace, `--text-muted`.
Active rail icon: `background: rgba(216,178,95,.16)`, ink `#d8b25f`; inactive `rgba(255,255,255,.5)`.

### 4.1 Migrating the current seven sidebar groups onto the rail

Six rail icons plus the gear at the rail bottom. "Six groups" and "seven groups" were both
right and are reconciled here — the gear is a rail destination but is not one of the six
module icons. This table is authoritative; it uses your real route names.

| Current sidebar group | Goes to | Rail icon | Notes |
|---|---|---|---|
| Main | Overview | `fa-gauge-high` | `dashboard` |
| Property Management | Property | `fa-building` | `buildings` · `floors` · `property-units` |
| Management → leasing half | Leasing | `fa-file-signature` | `tenants` · `lease-contracts` |
| Management → ops half | Operations | `fa-screwdriver-wrench` | `maintenance` |
| Accounting | Accounting | `fa-receipt` | `invoices` · `payments` · `ewa-bills` · `expenses` · `revenues` |
| Analytics | Analytics | `fa-chart-pie` | `reports` |
| Form / Template Management | Admin | — | fold in; `form-configs` is one destination with tab views |
| Admin | Admin (gear, rail bottom) | `fa-gear` | `users` · `audit-log` · `error-log` · `azure-mail` |

Two changes to note: **Management splits** — tenants and lease-contracts are leasing work,
maintenance is operations work, and they behave differently enough to warrant separate
modules. And **Form / Template Management folds into Admin**, as proposed: one destination,
configuration work, which is what Admin already is.

Sidebar hint lines (the 11px line under the gold group label) are counts or scope, drawn
from live data, not decoration. Use the real query: "4 buildings · 18 floors · 65 units",
"August 2026 · BHD", "Tenants and contracts", "Requests and vendors", "Reports and exports",
"Access and system", "Portfolio at a glance".

### 4.2 Toolbar and menu bar composition

Menu bar row, 29px, left to right: File · Edit · View · Records · Accounting · Window · Help,
each 11.5px `--text-secondary`, padding 0 11px, no hover fill needed for this pass. Sync chip
right-aligned, 8px from the edge.

Toolbar row, 44px, `--page-bg`, 1px bottom border, padding 0 13px, `gap: 10px`:

| Order | Element | Size |
|---|---|---|
| 1 | back / forward buttons | 25×25px, radius 6px, 1px border, 3px gap between them |
| 2 | ⌘K search field | flex, max-width 480px, height 27px, radius 7px, 1px border; icon + placeholder + ⌘K kbd hint right |
| 3 | *spacer* | `flex: 1` |
| 4 | density toggle | height 26px, padding 0 11px, radius 7px, 1px border, icon + label |
| 5 | theme toggle | 26×26px, radius 7px, 1px border |
| 6 | notification bell | 26×26px, radius 7px, 1px border; badge 14px circle, `#b4483c`, offset -4px top/right |
| 7 | avatar | 26px circle, gold gradient, navy initial |

All five right-hand controls share the same 26px height and 7px radius. The ⌘K field is 27px
— one pixel taller — because it is an input, not a button.

- The rail carries the module; the sidebar carries that module's pages. Nothing else lives
  in either. This replaces the current flat 260px sidebar with every section expanded.
- Toolbar: ⌘K search field centre-left (max-width 480px); density toggle, theme toggle,
  notification bell with badge, avatar right. The page title is **not** in the toolbar —
  it lives in the 54px page header with the subtitle under it.
- `main.page-content`: `--page-bg`, one column, `gap: 28px` between sections.
- Page header block first, always: `<h1 class="page-header-title">` + `.page-header-sub`,
  primary action button right-aligned in the same row.

**Command palette (⌘K)** — one partial, included in the layout, on every page. Fuzzy search
over buildings, units, tenants, contracts, invoices; plus verb rows ("Record expense",
"Smart import", "New contract"). Arrow keys + Enter, Esc closes.

**Keyboard shortcuts** — global, documented in the Help sheet:
`⌘K` palette · `g d/b/u/t/i` go to Dashboard/Buildings/Units/Tenants/Invoices ·
`n` new record on any index page · `/` focus list search · `⌘↩` submit the open form ·
`Esc` close sheet/modal.

---

## 5. Page archetypes

Every page in the system is one of five. Pick one; do not invent a sixth.

### A. Dashboard / overview
KPI strip (`.dash-stats`, `repeat(auto-fill,minmax(180px,1fr))`, gap 16px) → one hero
utility band (`.data-hero`) → one full-width chart card (`.finance-card`, canvas 320px) →
entity performance grid (`repeat(auto-fit,minmax(420px,1fr))`) → two-up recent tables
(`.dash-grid`). Nothing else.

### B. Index / list (Buildings, Units, Tenants, Contracts, Invoices, Payments, Expenses, EWA, Revenue, Users, Audit/Error log)
Page header → filter bar (one row: search input, 2–4 selects, Reset link, result count on
the right) → `.dash-card` containing a `.dash-table`. Row click navigates via
`data-href`. Sticky `thead`. Zebra off; hover = `--page-bg`. Pagination bottom-right,
"Showing X–Y of Z" bottom-left.

**Master–detail variant** (Buildings, Tenants, Contracts): 376px list pane left, detail
pane right, list selection persists in the URL (`?selected=`). Detail pane opens in its own
window with "Open in new window" (`window.open`, same route, `?chrome=bare`).

### C. Detail / record (Building, Unit, Tenant, Contract, Invoice)
Identity card (avatar tile + name + status badge + 4-up stat wells) → tab bar
(Overview · Ledger · Documents · Activity) → content cards → right column with
"Summary" and "Activity" cards. Actions live in the identity card, never floating.

### D. Bulk grid (Units bulk edit, Floors bulk edit, rent roll)
Spreadsheet grid: fixed header row, editable cells (click → inline input, Tab/Shift-Tab
moves, Enter commits the cell), row checkboxes, and a **dirty bar** above the grid:
"N unsaved edits · Commit changes · Revert". Nothing saves until Commit. `⌘↩` commits.
Editable cells get a dotted `--input-border` underline so they read as editable at rest.

### E. Form / wizard (Create & edit, Smart import, Report builder)
Single column, max-width 680px, inside a `.dash-card`. Labels are micro labels above the
field. Group with `.form-section` + 1px `--card-border` dividers. Destructive actions are
outline-red, bottom-left, separated from the primary submit bottom-right.
Multi-step (Smart import, report builder) uses a numbered step rail at the top:
Upload → Detect → Map columns → Preview → Import. Every step is cancellable.

---

## 6. Component rules

- **Buttons**: `.btn` + `.btn-primary` (gold gradient, navy ink) / `.btn-success` /
  `.btn-outline` / `.btn-sm`. One primary per screen region. Icon + label, icon first, 10px gap.
- **Badges**: `.badge` + `.badge-gold|green|blue|red|neutral`. 11px/700, uppercase off,
  pill radius. Status only.
- **Cards**: `--card-bg`, 1px `--card-border`, `--radius`, `--shadow-sm`. Hover lift
  (`translateY(-2px)` + `--shadow-md`) **only** if the whole card is clickable.
- **Tables**: `.dash-table`. `th` background `--page-bg`; last row no border; numeric
  columns right-aligned and Outfit 700; `data-href` on `tr` for navigation.
- **Modals**: `.modal-overlay` + `.modal-box` (max-width 480–680px), 16px radius, blur
  backdrop, `translateY(20px) scale(.98)` → `none` on `.open`. Esc and overlay click close.
- **Empty states**: centred, 30px padding, icon at 26px/45% opacity, one line of 13px
  `--text-muted` copy, and — when the user can act — one `.btn-outline btn-sm`.
- **Loading**: skeleton blocks in `--page-bg` at the final dimensions. No spinners over content.
- **Toasts**: bottom-right stack, 4s, one line, icon + text + close. Never a modal for a
  success confirmation.
- **Offline / sync**: the menu-bar chip shows `SYNCED HH:MM` (green dot) or
  `OFFLINE — N QUEUED` (amber dot), 10px/600, `letter-spacing:.07em`, `--text-muted`.
  **Presentation only for now** — render the chip in its synced state and stop there. The
  queue-and-replay behaviour needs client-side persistence and request replay, which is out
  of scope for a Blade refactor. Same for the shortcut layer: build the palette markup and
  ⌘K open/close, defer anything that needs real mutation state.

---

## 7. Data & copy

- Use real system data and real labels: Combo Test Tower (CTT1), Miknas Plaza 1 (MP1),
  Miknas Plaza 2 (MP2), PL Unit Filter Tower (PLUF1); 4 buildings, 18 floors, 65 units,
  38 furnished, 25 fitted.
- Zero is a real value: render `BHD 0`, never `—` or blank, and never fake a number.
- Dates: `18 Aug 2026`. Months: `August 2026`. Never `08/18/26`.
- Sentence case for everything except micro labels (uppercase) and page titles.
- Copy is plain and declarative: "No expenses recorded this month", not "Oops! Nothing
  here yet 🎉".

---

## 8. Accessibility

- Body text ≥ 4.5:1. `--text-muted` and gold are **decorative-grade**: never use them for
  the only copy that carries meaning. Compliant swaps: `#6b7689` muted text, `#8a6d20`
  gold text.
- Focus is always visible: 2px `--accent` ring, 2px offset. Never `outline:none` without a
  replacement.
- Every icon-only button has `title` + `aria-label`. Every table has a caption or an
  `aria-label`. Modals get `role="dialog" aria-modal="true" aria-labelledby`.
- Full keyboard reachability, logical tab order, no focus traps outside modals.
- Hit targets ≥ 32px on desktop, ≥ 44px on touch.

---

## 9. Do not

- Add gradients to page or card backgrounds (the one gold gradient is buttons + the
  `.data-hero::before` wash).
- Round anything to a pill unless it is a badge, chip or bar.
- Use a second accent colour.
- Put more than one chart on a screen without a heading that explains each.
- Nest cards inside cards. Use `--page-bg` wells (`.property-expense-list`) instead.
- Add a left-border-accent coloured container.
- Duplicate the mobile `.pm-*` markup on desktop, or vice versa.
- Ship a page without an empty state, a loading state and a dark-theme pass.

---

## 10. Rollout order

1. Layout shell (`resources/views/layouts/admin.blade.php` — the project's only layout):
   menu bar row, rail, contextual sidebar, toolbar, page header, palette markup, sync chip.
   Appearance only; no offline queue, no state-dependent shortcuts.
2. Index pages (archetype B) — Buildings, Units, Tenants, Contracts, Invoices, Payments.
3. Detail pages (C) — Building, Unit, Tenant, Contract, Invoice.
4. Bulk grid (D) — Units, Floors.
5. Forms & wizards (E) — create/edit, Smart import, Report builder.
6. Dashboard (A) last, once the KPI sources are settled.
7. Admin (Users, Audit log, Error log, Mail settings) — archetype B, no charts.

---

## Acceptance checklist

Tick every line before calling a page done.

- [ ] Page uses exactly one archetype from §5
- [ ] Zero raw hex outside the §3 semantic table
- [ ] Verified in light **and** dark theme
- [ ] Mobile `.pm-*` layer untouched and still correct ≤768px
- [ ] Page header + one primary action, gold used once
- [ ] Empty state, loading state, error state all present
- [ ] Keyboard: Tab order sane, focus visible, `n` / `/` / `⌘K` work
- [ ] Icon-only buttons have `title` + `aria-label`
- [ ] Numbers are Outfit 700, right-aligned, `BHD`-prefixed, `0` rendered as `BHD 0`
- [ ] No new CSS outside the page's namespaced `@push('styles')`
- [ ] No new dependency
