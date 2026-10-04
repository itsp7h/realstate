---
name: laravel-blade-ui
description: Keeps every UI change inside the Blade/server-rendered architecture and provably presentation-only — routes, controllers, validation, permissions, and business logic never move.
---

# Laravel Blade UI

You are the guardrail between "improve the UI" and "change what the app
does." Every other skill in this system produces markup and CSS changes; this
skill is what makes those changes safe to ship without a controller review.

## Architecture facts, not options

- This is a Laravel 12 + Blade application. Server-rendered first, Blade +
  progressive enhancement. **No SPA framework. No client-side routing.** The
  only JS charting dependency is Chart.js — do not add another.
- `resources/views/layouts/admin.blade.php` is the app's **only** layout. It
  owns: the theme bootstrap, the global row-click handler, and
  `$mobileRedesignedRoutes` (which routes get the dedicated mobile app layer
  vs. the generic responsive fallback).
- Asset load order is fixed and load-bearing:
  `app-core.css` → `@stack('styles')` → `app-mobile.css`. The mobile layer
  loading last is deliberate (it turns every modal into a bottom sheet);
  never reorder these includes to fix a specificity problem — fix the
  specificity instead.

## Absolute rules

- **Never convert a Blade view to React, Vue, or any client framework.** Not
  "for this one page," not "just this component." If a page genuinely needs
  interactivity, it's vanilla JS/Alpine-style progressive enhancement inside
  the existing Blade file, consistent with how the rest of the app does it.
- **Never change**, as a side effect of a UI pass: routes, controller logic,
  Form Request validation rules, `@can`/policy checks, authentication,
  business calculations, or database structure/migrations. If a requested UI
  improvement seems to require one of these, **stop and ask** rather than
  doing it — this mirrors the explicit rule in `APPLY-DESIGN.md`'s standard
  prompt ("If a change requires touching a controller or query, stop and tell
  me instead of doing it").
- **Preserve every dynamic piece of a Blade file exactly**: `route(...)`
  calls, `old(...)` values, `@error`/`@csrf`/`@can` directives, model
  attribute references, `{{ }}`/`{!! !!}` output. A refactor changes the
  wrapper markup and classes around these, never their content or presence.
- **Server-side filtering, search, sorting, and pagination only** (CLAUDE.md).
  A UI change to a filter bar must still submit a GET form the backend
  processes — never move filtering logic into client-side JS.
- **Row-click rule**: every index/listing `<tr>` gets `data-href="…"` for the
  global handler in the layout to pick up; action buttons/cells call
  `e.stopPropagation()` so they still work independently.
- **Testing is not optional.** Every controller/model/service touched needs
  `tests/Feature` or `tests/Unit` coverage, run with `php artisan test` before
  calling anything done. For UI-only work, the three guard-rail suites matter
  most: `UiConsistencyTest`, `AllPagesRenderTest` (every page route still
  renders the shell), `DesktopShellTest` (sidebar structure, submenu, no page
  missing from navigation).

## Workflow for editing a view

1. Read the full Blade file before changing anything — not just the section
   you think you're touching.
2. List every dynamic/functional element in it (forms, routes, validation
   error slots, permission checks, model bindings).
3. Make the presentation change: markup structure, classes, page-local CSS in
   `@push('styles')`. Confirm every item from step 2 is still present,
   unchanged, in the new markup.
4. Run `php artisan test`. If anything outside the three UI guard-rail suites
   fails, you changed behavior — revert and redo without touching it.
5. If the task genuinely can't be done as presentation-only (e.g., a filter
   needs a backend field that doesn't exist yet), stop and say so instead of
   quietly adding backend logic under a "UI task."

## Handoff

This skill doesn't decide *what* the UI should look like — that's
`ui-design-system`. It decides whether a proposed change is safe to make in a
Blade file without breaking the application underneath it. Every skill that
edits a `.blade.php` file (`component-standardization`, `responsive-design`,
`ui-refactoring`) operates under these constraints.
