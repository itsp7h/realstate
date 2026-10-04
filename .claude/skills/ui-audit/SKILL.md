---
name: ui-audit
description: Read-only inspection pass that runs before any UI change — finds inconsistency and duplication, and reports it before a single line is edited.
---

# UI Audit

You inspect. You do not fix. This skill exists so that "implementation" never
starts from a guess about what's wrong — it starts from a written list. Every
other skill that modifies files (`ui-refactoring`, `component-standardization`,
`responsive-design`, `laravel-blade-ui`) consumes this skill's output; none of
them should be scanning ad hoc.

## When to run this

- Before touching any page or component that wasn't just built from scratch.
- Before starting the application-wide standardization workflow (see
  `.claude/skills/README.md`), as the reconnaissance phase.
- Whenever the user says a page "feels off" and wants to know why before
  deciding whether to change it.

## What "correct" means

Defined by `ui-design-system`. Load that skill's ground-truth hierarchy before
judging anything. Don't invent your own opinion about what a card should look
like — check it against `app-core.css` and the archetype table.

## What to detect

Walk the target page(s) and check each of these, citing `file:line`:

1. **Page structure** — exactly one `.page-header` per page, not a page-local
   header (`class="page-header` should never appear inside a page's own
   `@push('styles')`, and there should be exactly one on the rendered page).
2. **KPI/stat cards** — every `.stat-card` leads with `.stat-card-top`
   (icon + label above the figure). A card missing it is drifted anatomy.
3. **Tables** — every `<table>` sits inside `.table-wrap`, `.table-responsive`,
   or an explicit `overflow-x` container. One that doesn't will overflow on
   mobile.
4. **Shared-component redefinition** — a page's `@push('styles')` block must
   never set appearance properties (`background`, `color`, `border*`,
   `box-shadow`, `font*`, `padding`, `height`, `text-transform`,
   `letter-spacing`) on a shared class: `stats-grid`, `stat-card`, `stat-icon`,
   `stat-val`, `stat-lbl`, `page-header`, `filter-bar`, `filter-group`,
   `table-card`, `table-wrap`, `table-footer`, `result-count`, `pagination`,
   `page-btn`, `card-header`, `card-body`, `card-title`, `card-footer`, `btn`,
   `badge`, `status-badge`, `alert`, `empty-state`, `empty-icon`,
   `form-actions`, `form-grid`, `tab-bar`, `tab-btn`.
5. **Raw colour** — no `#RRGGBB`/`#RGB` literal in a page's pushed CSS. Every
   colour must resolve through a token.
6. **Section margins** — `stats-grid`, `table-card`, `filter-card`,
   `page-header`, `card-grid`, `table-footer` must not carry their own
   `margin-top`/`margin-bottom` in page CSS; vertical rhythm belongs to
   `.shell-content`'s `gap` (app-core §7.5).
7. **Filter bar placement** — a `.filter-bar` must sit inside a `.table-card`
   or `.filter-card`, never floating on the page background.
8. **Card-row stacking margin** — a container that lays out multiple `.card`
   children with `gap` must also reset `.card + .card { margin-top: 0 }`, or
   the row misaligns.
9. **Breakpoints** — only `430px 600px 768px 769px 900px 1200px 1400px` may
   appear in a page's media queries.
10. **Third-party frameworks** — no Bootstrap/Tailwind/Bulma/Foundation/
    Semantic `<link>`, no jQuery/Bootstrap `<script>`.
11. **Duplicated patterns** — grep across `resources/views/**/*.blade.php` for
    near-identical page-local CSS blocks (same property sets repeated per
    page) and near-identical markup structures that aren't using the shared
    component. A badge/chip/pill defined once per page under a different
    class name in 2+ places is a duplication finding, not a style nit.
12. **Row-click rule** (CLAUDE.md) — every index/listing `<tr>` has
    `data-href`, and action buttons/cells call `e.stopPropagation()`.
13. **Form Config / Template Config obligations** (CLAUDE.md) — every CRUD
    module has its card at `/form-configs?tab=forms`, and every
    import/export-capable module has its card at `/form-configs?tab=templates`.

## Method

1. Read the target Blade file(s) in full, plus their `@push('styles')` blocks.
2. Run the mechanical checks above by grep, or by running the relevant guard
   rail directly: `php artisan test --filter=UiConsistencyTest` catches items
   2–10 automatically across the whole app — run it first, it's seconds, and
   it tells you which pages to look at by hand.
3. For anything the automated test can't see (duplication across pages,
   visual drift, archetype mismatch, missing empty/loading state), read the
   nearest sibling page of the same archetype and diff by eye.
4. Classify each finding: **breaks the system** (fails a guard-rail test or
   uses a banned pattern) / **drift** (works, but doesn't match the master
   pattern) / **functional risk** (touches something `laravel-blade-ui` needs
   to protect — a route, a validation rule, a permission check).
5. Write the findings as a list — file, line, what's wrong, which category —
   before any edit happens. `HANDOFF.md` §5 is a real example of this output
   format (a categorized punch list with file references and a reason for
   each item, including items explicitly marked "leave as is").

## Rules

- **Never edit a file during an audit.** If you notice something trivially
  fixable, still just report it — mixing "look" and "fix" is how partial,
  unreviewed changes creep in.
- **Don't pad the list.** A container using `gap`/`grid-column` layout-only
  CSS on a shared class name is fine; only appearance properties count as a
  violation. Over-reporting trains people to ignore the list.
- **Note deliberate exceptions, don't relitigate them.** Some "inconsistency"
  is intentional (e.g. report pages that scroll instead of stacking to cards
  ≤768px). If a prior audit or `HANDOFF.md` already recorded a reason, cite it
  instead of re-flagging it as new.
