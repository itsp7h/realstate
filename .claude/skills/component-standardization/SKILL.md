---
name: component-standardization
description: Finds repeated UI patterns and consolidates them onto the shared component catalog instead of letting pages duplicate each other.
---

# Component Standardization

Your job is reuse. Every screen in this app should draw from the same small
set of components — a page never gets a bespoke button, table, or badge when
`app-core.css` already has one. This skill is what stops "one stat card under
nine different names" from happening again (that's not hypothetical — it's
exactly what the refactor this codebase already went through had to undo).

## The catalog (all defined in `app-core.css` §4, cataloged with markup
contracts in `.claude/skills/frontend-expert/references/components.md`)

`.page-header` (+ `.page-header-actions`) · `.stats-grid > .stat-card` (with
`.stat-card-top`, and modifiers `.is-5`/`.is-triple`/`.is-double`/`.is-figure`)
· `.table-card > [.filter-bar] > .table-wrap > table` · `.form-card >
.form-grid > .form-group` · `.card` (`.card-header`/`.card-body`/
`.card-footer`, `.is-interactive`, `.is-hero`, `.has-rail`) · `.btn`
(`-primary`/`-outline`/`-ghost`/`-danger`/`-success`, `.btn-sm`/`.btn-lg`/
`.btn-icon`/`.btn-block`) · `.badge` / `.status-badge` · `.alert-*` ·
`.tab-bar > .tab-btn` · `.modal-overlay > .modal-box` · `.pagination` ·
`.empty-state` · `.skeleton` / `.spinner`.

If what you need isn't in this list, it may still exist — check `app-core.css`
§4 directly before concluding it doesn't (the catalog list above is not
exhaustive of every modifier).

## Workflow

1. **Name the component before writing markup.** "This page needs a stat
   strip" → that's `.stats-grid > .stat-card`, not a new grid. "This page
   needs a status pill" → that's `.badge`/`.status-badge` with the tone that
   matches the meaning, not a new class.
2. **Search for the pattern before assuming it's new.** Grep
   `resources/views/**/*.blade.php` for similar markup. A component that
   already appears on 2+ pages under different local class names is a
   standardization target, not two separate designs.
3. **Decide where a new pattern belongs:**
   - Fits an existing component with a different modifier → add the modifier
     to `app-core.css`, document it in the component's comment block, use it
     everywhere the pattern occurs.
   - Genuinely new, and 2+ pages need it → promote to `app-core.css` §4 as its
     own component, tokens only, both theme blocks. Update
     `components.md` to match.
   - Only one page will ever need it → it stays page-local, in that page's
     `@push('styles')`, and must not redefine any shared class name.
4. **When NOT to standardize.** Not every repeated-looking thing is a
   component. `HANDOFF.md` §5.1 has a real worked example: several one-off
   badge/pill classes across the app (`.ewa-badge`, `.mquot-pill`,
   `.type-pill`, etc.) were deliberately left alone because each is a single
   page's shim or a layout wrapper (`-badges`/`-pills` suffix), not a
   duplicate component. Only promote when the *same visual pattern with the
   same meaning* repeats on 2+ pages — don't create a component just to lower
   a count.
5. **Every CRUD module gets its config entries** (CLAUDE.md): a card at
   `/form-configs?tab=forms` matching the existing Building/Unit Form card
   pattern, and — if the module supports import/export — a card at
   `/form-configs?tab=templates` matching the existing Building/Unit/Lease
   Contracts Template card pattern. This is part of "standardization," not an
   optional extra; a module without it is an inconsistency.
6. **Verify after consolidating**: `php artisan test --filter=UiConsistencyTest`
   (no page may now redefine the class you just promoted), then the full
   suite, then look at both themes.

## Do not

- Build a page-specific button, table, card, or badge style when an existing
  class covers the need with a different label/icon/tone.
- Promote a one-off into `app-core.css` just because it's "similar enough" —
  if the meaning differs, a look-alike component is the wrong fix (see the
  chip example above).
- Change a shared component's default appearance to fix one page's need —
  that's a modifier or a page override, not a redefinition of the base class.
