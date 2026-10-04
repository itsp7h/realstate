---
name: ui-refactoring
description: Cleans up duplicated and conflicting CSS, obsolete styles, inline styles, and !important — presentation-only, verified after every change.
---

# UI Refactoring

You take `ui-audit`'s findings and clean them up. This skill is where actual
edits happen for cleanup work — as opposed to `component-standardization`,
which decides what the shared vocabulary should be, this skill removes the
mess that accumulates around it (dead CSS, inline styles, `!important`,
duplicate rules). Functionality does not change; only presentation code does.

## Inputs

Don't start from scratch — start from `ui-audit`'s findings list, or from a
known punch list like `HANDOFF.md` §5. Refactoring without a findings list
first is how "cleanup" turns into an unreviewed rewrite.

## Priority order for any given finding

1. **An existing `app-core.css` class already covers this** — delete the
   page-local duplicate, use the class.
2. **The same page-local pattern appears on 2+ pages** — promote it into
   `app-core.css` §4 (tokens, both theme blocks), then delete it from every
   page that had its own copy. This is a `component-standardization` decision
   executed as a `ui-refactoring` edit — coordinate with that skill's catalog
   before inventing the promoted class's name.
3. **Genuinely page-unique** — keep it, but clean it: tokens only (no raw
   hex), no `!important`, no ad-hoc control height, scoped inside that page's
   own `@push('styles')`, and it must not redefine a shared class name (see
   `ui-audit`'s list of protected class names).
4. **Inline `style="…"` attributes** — move to a namespaced class in the
   page's `@push('styles')` block. An inline style can't respond to dark mode
   or be caught by the guard-rail tests, which is exactly why they keep
   reappearing; don't leave them in "because it's small."

## Removing obsolete styles

- Before deleting a CSS rule or class, grep for its usage across
  `resources/views/**/*.blade.php` **and** `public/js/**`. Don't delete
  something you haven't confirmed is unreferenced.
- Don't delete functionality to simplify CSS. If a rule looks unused but
  you're not certain, leave it and flag it in your report rather than
  guessing.
- Removing dead CSS is a separate, reviewable step from adding/renaming a
  component — don't bundle "delete 200 unused lines" into the same diff as
  "add a new modifier," so a regression is easy to bisect.

## `!important` and the cascade contract

There is a specific reason `!important` is never needed here: the cascade is
`app-core.css` → `@stack('styles')` → `app-mobile.css`, in that fixed order,
and that ordering alone gives page CSS enough specificity headroom over the
base layer, while `app-mobile.css` loading last is what lets it win over page
CSS for the mobile bottom-sheet conversion. An `!important` you're tempted to
add is almost always working around a selector that's not specific enough, or
a component boundary that's being violated (see rule 3 above) — fix the
selector or move the rule, don't force it.

## Incrementality — do not blindly mass-edit

- Work one page, or one component family, at a time. Don't open a PR that
  touches 40 files in one uninterrupted pass.
- After each unit of work: run `php artisan test` (the three guard-rail
  suites at minimum: `UiConsistencyTest`, `AllPagesRenderTest`,
  `DesktopShellTest`), and `git diff` the change to confirm it's
  presentation-only — no route, controller, migration, or validation rule
  should appear in the diff.
- Track the same metrics `HANDOFF.md` already tracks, before/after, so
  progress is measurable rather than asserted: page-local CSS line count, raw
  hex count in page CSS, hardcoded `font-size` occurrences, distinct
  breakpoints in use, pages still loading a third-party framework, contrast
  failures, horizontal-overflow findings.
- Do not stop after one page. If the audit found the same issue on 12 pages,
  fix representative pages, confirm the pattern, then sweep the rest — but
  verify each batch, don't fix all 12 blind and test once at the end.

## Do not

- Change anything a controller, route, migration, or Form Request would
  notice.
- Add `!important` to make a fix land faster.
- Leave a raw hex "just for now."
- Delete a CSS rule you haven't confirmed is dead.
