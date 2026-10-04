---
name: ui-design-system
description: Owns the application's visual language — tokens, typography, spacing, components, page archetypes. The source of truth every other UI skill builds toward.
---

# UI Design System

You are the keeper of "what the design system is." You do not decide this by
inventing — you decide it by **reading what already shipped** and treating the
strongest existing pattern as the master. Every other skill in `.claude/skills/`
defers to this one when it needs to know what a button, a table, a card, or a
breakpoint is supposed to be.

## Ground truth, in priority order

When two sources disagree, the higher one wins:

1. **`public/css/app-core.css`** — the actual shipped implementation. It is
   heavily commented; every component section states its markup contract and
   the reason it exists. This is the only place a class name is authoritative.
2. **`public/css/app-mobile.css`** — the mobile app layer (`.m-*`, `.pm-*`).
   Loaded last, intentionally wins the cascade over page CSS.
3. **`DASHBOARD-SPEC.md`** — supersedes `DESKTOP-UI.md` §4 (shell) and §5A
   (dashboard archetype). Measured from an approved screenshot.
4. **`DESKTOP-UI.md`** — the written spec: type scale (§2), colour (§3), the
   five page archetypes (§5), component rules (§6), data/copy conventions
   (§7), accessibility (§8), the "do not" list (§9).
5. **`.claude/skills/frontend-expert/references/*.md`** — a day-to-day lookup
   catalog kept in sync with app-core.css (`components.md`, `design-tokens.md`,
   `blade-patterns.md`, `responsive-mobile.md`, `accessibility.md`).
6. **`APPLY-DESIGN.md` / `HANDOFF.md`** — historical process notes and the
   current punch list. Useful for intent and status, not for class names.

**Important trap:** `DESKTOP-UI.md` and `DASHBOARD-SPEC.md` were written
against a reference mock and use mock vocabulary (`.dash-card`, `.dash-table`,
`.data-hero`, `.finance-card`). Those exact classes were **not** carried into
the implementation — the real, shipped names are `.table-card`, `.stats-grid`
`.stat-card`, `.filter-bar`, `.table-wrap`, `.badge`/`.status-badge`, `.card`,
`.form-card`, `.modal-overlay`/`.modal-box`, `.pagination`, `.tab-bar`. Always
grep `app-core.css` for the real class before writing markup — use the specs
for the *visual language* (sizes, colour, type, archetype, rhythm), never for
a literal selector.

## What this skill owns

- **Tokens** (`app-core.css` §1): six breakpoints and no others (430 / 600 /
  768 / 900 / 1200 / 1400px — 768 is the desktop/mobile boundary, nothing else
  sits on it); light tokens on `:root`, dark overrides in one
  `[data-theme="dark"]` block; six semantic tones (success/danger/warning/info/
  neutral/accent), each a `-fg`/`-bg`/`-border` triple that clears WCAG AA;
  fixed chart series colours; `--h-control*` control heights; `--radius`/
  `--radius-sm`; `--shadow-sm`/`--shadow-md`.
- **Typography**: Outfit for numerals/headings/money, Plus Jakarta Sans for
  UI/body, Poppins reserved for the marketing/auth screens only. Minimum body
  size on desktop is 12px. Money is always Outfit 700, `BHD`-prefixed, and `0`
  renders as `BHD 0` — never a dash, never blank.
- **The five page archetypes** (`DESKTOP-UI.md` §5) — every page is exactly
  one, never a sixth: **A** Dashboard/overview, **B** Index/list (+
  master-detail variant), **C** Detail/record, **D** Bulk grid, **E** Form/
  wizard. `APPLY-DESIGN.md`'s page→archetype table maps every existing module
  to one of these.
- **The "do not" list** (`DESKTOP-UI.md` §9): no gradients outside buttons and
  the one hero wash; no pill radius on anything but a badge/chip/bar; no
  second accent colour; no card nested in a card (use a `--page-bg` well);
  no left-border-accent container; no duplicating `.pm-*` markup on desktop or
  vice versa; never ship a page without empty/loading/dark-theme states.

## Workflow

1. **Someone needs a pattern that doesn't obviously exist.** Search
   `app-core.css` §4 and `components.md` first — most "new" needs are an
   existing component with different data in it.
2. **It genuinely doesn't exist and 2+ pages will need it.** Add it to
   `app-core.css` §4, as a token-driven class, with both the light and
   `[data-theme="dark"]` values defined. Document the markup contract in a
   comment the way every existing section does. Update
   `.claude/skills/frontend-expert/references/components.md` to match.
3. **Only one page needs it.** It is not a system component — it is page CSS
   in that page's `@push('styles')`, built from tokens, and it must not
   redefine a shared class name (see `ui-audit`'s invariant list).
4. **A spec document and the shipped code disagree.** Trust `app-core.css`.
   File the discrepancy as a note for whoever maintains the spec docs; do not
   silently "fix" the code to match a mock that was never fully carried over
   unless asked.
5. Hand off: `ui-audit` uses this skill to know what "correct" looks like
   before touching a page; `component-standardization` and `ui-refactoring`
   build toward it; `visual-qa` checks the final result against it.

## Non-negotiables (inherited by every other skill)

- No raw hex outside `app-core.css` `:root` and the semantic-hex table in
  `DESKTOP-UI.md` §3.
- No `!important` — the cascade contract (`app-core` → `@stack('styles')` →
  `app-mobile`) removes the need, and breaking it breaks the mobile bottom
  sheets.
- No ad-hoc control heights — inputs/buttons derive from `--h-control*`.
- No new breakpoint outside the documented six.
- No third-party UI framework (Bootstrap/Tailwind/Bulma CDN, jQuery) on any
  view. Bootstrap's one legacy holdout (`buildings/show.blade.php`) was
  already migrated off — do not reintroduce it anywhere.
