# DASHBOARD-SPEC.md — measured from the approved screenshot

Supersedes `DESKTOP-UI.md` §4 (the icon-rail shell) and §5A. Where this file and
`DESKTOP-UI.md` disagree, **this file wins**. §2 (type), §3 (colour), §6 (components),
§7 (data), §8 (accessibility) still apply unchanged.

---

## 1. Shell

No icon rail. One sidebar, one top bar, page header inside the content column.

| Region | Size |
|---|---|
| Sidebar | **228px**, full height, dark navy `#0f1b3d`, no border |
| Top bar | **80px**, `--card-bg`, 1px bottom border |
| Content | padding `28px 32px 36px`, on `--page-bg` |
| Page gutter | outer app has an 8px navy margin, content area radius 12px |

### Sidebar

- Logo: 40px rounded-square gold-ringed mark, top-left, 24px padding.
- Three groups, uppercase label 10.5px/700, `letter-spacing:.14em`,
  colour `rgba(255,255,255,.45)`, 24px top margin:

| Group | Items |
|---|---|
| OVERVIEW | Dashboard · Activity feed |
| PORTFOLIO | Buildings · Units · Tenants · Leases · Bills & Payments · Maintenance · Reports |
| CONFIGURATION | Users · Roles & Permissions · Settings |

- Nav item: 44px tall, 13.5px/500, `rgba(255,255,255,.7)`, icon 17px at 20px column,
  gap 14px, padding-left 24px.
- Active item: fill `rgba(255,255,255,.08)`, ink `#fff`, weight 600, and a **3px gold
  `#e0a935` left bar** flush to the sidebar edge, full item height.
- Count badge right-aligned, 11.5px/500, `rgba(255,255,255,.5)`.
- Bottom "Need help?" card: `rgba(255,255,255,.06)`, radius 12px, 20px margin, icon tile
  + "Need help?" 12.5px/600 + "Visit our help center" 11.5px `rgba(255,255,255,.5)`.

### Top bar

Left: hamburger, 18px, `--text-secondary`, 32px from the sidebar.
Right, in order, `gap: 20px`:

| Element | Spec |
|---|---|
| Search | 330px × 40px, radius 10px, `--page-bg` fill, 1px border, magnifier + "Search anything…" 13.5px `--text-muted` + `⌘K` kbd right |
| Bell | 20px icon; badge 18px circle `#e0a935`, navy digit, offset -4px |
| Help | 20px circle-question icon, `--text-muted` |
| User | 36px gold-gradient circle w/ initial, then name 13.5px/600 over role 12px `--text-muted`, then 14px chevron |

---

## 2. Page header

Inside the content column, not a fixed bar. 28px below the top bar.

- Title: **30px/700**, `-0.02em`, `--text-primary`.
- Subtitle: 13.5px, `--text-muted`, 6px below — dot-separated scope
  ("Portfolio overview · August 2026 · All buildings").
- Actions right, `gap: 12px`, all 44px tall, radius 10px:
  - `Export` and `Filter` — `--card-bg`, 1px border, icon + 13.5px/500 label
  - `Quick action` — gold `#e0a935` fill, navy ink, 13.5px/600, leading `+`

---

## 3. KPI strip

`grid-template-columns: repeat(5, 1fr)`, `gap: 20px`, 24px below the header.

Card: `--card-bg`, 1px `--card-border`, radius 14px, padding 22px.

1. Icon tile 40px, radius 11px, pastel fill + saturated ink — one per metric:
   buildings `#EFF6FF`/`#1D4ED8` · occupancy `#EFF6FF`/`#1D4ED8` · billed `#FFFBEB`/`#D97706` ·
   collected `#ECFDF5`/`#047857` · expenses `#FEF2F2`/`#DC2626`.
2. Label right of the tile: 11.5px/700, uppercase, `.06em`, `--text-muted`.
3. Figure: 30px/700, 16px below. `BHD` prefix at 20px/600 where the value is money.
4. Footer row, 14px below, `space-between`: sub-line 12.5px `--text-muted`, then the trend
   chip — caret + percentage, 12.5px/600, green `#047857` up / red `#DC2626` down.

**Trend chips render only where a real prior-period comparison exists.** No comparison, no
chip — do not invent one. Cards 1 and 3 in the screenshot have no chip; that is correct.

---

## 4. Main band

`grid-template-columns: 1fr 376px`, `gap: 20px`.

### Chart card (left)

`--card-bg`, radius 14px, padding 24px.

- Title 17px/700; subtitle 12.5px `--text-muted` below.
- Legend right of the title: pill per series, `--card-bg`, 1px border, radius 8px, 8px 12px,
  6px colour square + 12.5px/500 label. Series colours per `DESKTOP-UI.md` §3.
- Period select below the legend, right-aligned: 36px, radius 9px, 1px border, "This year".
- Chart.js, height 260px. Bars grouped, `borderRadius: 3`, `barThickness: 9`.
  Profit is a line, `tension: .4`, 2.5px, white-filled points.
- Y axis 0–1,000 in 250 steps, gridlines `--card-border` dashed, labels 11px `--text-muted`.
  All twelve months on X, always — future months simply have no bars.

### Right column, `gap: 20px`

**Collected card** — navy `#0f1b3d`, radius 14px, padding 24px.
Label 11px/700 `.14em` `rgba(255,255,255,.55)`; figure 34px/700 white; "of BHD 3,930 billed"
13px `rgba(255,255,255,.6)`. Progress bar 5px, track `rgba(255,255,255,.12)`, fill gold, with
the percentage right-aligned above it. Vault icon 44px circle `rgba(224,169,53,.14)` top-right.
Three-up split below, 1px `rgba(255,255,255,.12)` dividers: UNITS · OCCUPANCY (gold value) ·
OVERDUE, labels 10.5px/700, values 19px/600.

**Needs your attention** — `--card-bg`, radius 14px, padding 20px.
Section label 11px/700 `.14em` `--text-muted`. Rows 56px, `gap: 10px`, radius 10px,
hover `--page-bg`: 36px pastel icon tile, title 13px/600, sub 12px `--text-muted`,
chevron right `--text-muted`. Each row is a link to the record it names.

---

## 5. Property performance

One outer card, `--card-bg`, radius 14px, padding 24px.

- Header row: title 17px/700 "Property performance — {Month Year}", `View all` button right
  (36px, radius 9px, 1px border, 12.5px/500).
- Inner grid `repeat(auto-fill, minmax(300px, 1fr))`, `gap: 16px`, 20px below the header.
- Building card: 1px `--card-border`, radius 12px, no shadow.
  - Top row, padding 16px: **80×64px photo**, radius 8px, `object-fit: cover`; then a 34px
    navy initials tile, radius 8px, gold ink; then name 14px/600 over `CODE · Type`
    12px `--text-muted`.
  - Photo source: the building's own image if the model has one; otherwise a neutral
    `--page-bg` tile with a 16px `fa-building` glyph at 35% opacity. **Never a stock photo of
    a building that isn't theirs.**
  - Stats row, 1px top border, padding 14px 16px, three equal columns with 1px dividers:
    INCOME · NET · OCC. Labels 10.5px/700 `--text-muted`, values 15px/700. Negative net in
    `#DC2626`.
  - Footer, 1px top border, padding 12px 16px: `View details →` 12.5px/600, gold on hover.
- **Every** building renders. Four buildings means four cards, including the ones at zero.

---

## 6. Data — read this before writing any number

The screenshot's figures are **placeholders and several are wrong**. Every value comes from
the database. For the record, the correct August 2026 figures are:

| Building | Income | Net | Occupancy |
|---|---|---|---|
| Combo Test Tower (CTT1) | BHD 770 | BHD 682 | 8% |
| Miknas Plaza 1 (MP1) | BHD 0 | BHD −52 | 0% |
| Miknas Plaza 2 (MP2) | BHD 0 | BHD 0 | 0% |
| PL Unit Filter Tower (PLUF1) | BHD 0 | BHD −36 | 0% |

Also correct in passing: the occupancy sub-line reads "1 of 65 units **let**", not "left".

Zero is a real value — render `BHD 0`. A card at zero is information, not an empty state.

---

## 7. Empty and dark

- No buildings at all: the performance card holds one centred empty state — 26px
  `fa-building` at 45% opacity, "No buildings yet" 13px `--text-muted`, and a
  `btn-outline btn-sm` to create one. KPI strip still renders, all zeros.
- Dark theme: sidebar and collected card keep `#0f1b3d`. Everything else moves on tokens.
  Pastel KPI tiles need dark equivalents — use `--accent-dim`-style 12%-alpha fills of the
  same saturated inks rather than the light pastels.
