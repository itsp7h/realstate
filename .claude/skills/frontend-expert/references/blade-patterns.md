# Blade page patterns

## The shell

`resources/views/layouts/admin.blade.php` provides:

- `@yield('title')` (browser title) and `@section('topbar-title')` (in-app title)
- `@stack('styles')` — loaded **between** app-core.css and app-mobile.css
- `@yield('content')` inside `.main-wrap > .page-content`
- `@stack('scripts')` before the global handlers
- Theme bootstrap: `data-theme` set on `<html>` before first paint from
  `localStorage['p7-theme']`
- The global row-click handler:
  ```js
  document.addEventListener('click', e => {
      const tr = e.target.closest('tr[data-href]');
      if (tr) window.location = tr.dataset.href;
  });
  ```
- `$mobileRedesignedRoutes` — the opt-in list for the mobile app layer, and
  `$pushedScreenRoutes` for push/pop view transitions
- Body classes: `is-dashboard`, `is-mobile-screen`, `is-pushed-screen`

Anything you add to `@push('styles')` must be page-unique and token-based. If it
would be useful on a second page, it belongs in `app-core.css`.

---

## Index page skeleton

```blade
@extends('layouts.admin')

@section('title', 'Payments')
@section('topbar-title', 'Payments')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Payments</h1>
        <p class="page-header-sub">All payments received across invoices</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('payments.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Record payment
        </a>
    </div>
</div>

<div class="stats-grid">…</div>

<div class="table-card">
    <form method="GET" action="{{ route('payments.index') }}" class="filter-bar">…</form>
    <div class="table-wrap">…</div>
    <div class="table-footer">
        <div class="result-count">
            Showing {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }}
            of {{ $payments->total() }}
        </div>
        {{ $payments->withQueryString()->links() }}
    </div>
</div>

@endsection
```

Checklist for every index page:

- [ ] Row click: `<tr data-href="{{ route('x.show', $row) }}">`
- [ ] Actions cell: `onclick="event.stopPropagation()"` (or `e.stopPropagation()`
      on each button) so edit/delete still work
- [ ] `data-label` on **every** `<td>` — the mobile card view reads it
- [ ] Filters are a GET form; all values re-populated from `request()`
- [ ] Pagination is `->withQueryString()` so filters survive page changes
- [ ] Empty state distinguishes "nothing exists" from "nothing matched"
- [ ] Numeric columns right-aligned with `.amount-col` / `.num`

## Show page skeleton

Hero card (`.card.is-hero`) with the record's identity and its status badge,
then `.section-stack` of `.card`s, then related lists as `.table-card`s.
Destructive actions live at the bottom or in a `.btn-danger` in the header, and
always confirm through a modal — never a bare `confirm()`.

If the route is in `$pushedScreenRoutes`, the mobile back chevron must carry
`.pm-push-back` so the view transition runs as a "pop".

## Create / edit skeleton

`.form-card > .form-grid > .form-group`, `.form-actions` at the end with Cancel
(`.btn-outline`) before Save (`.btn-primary`).

- Backend validation lives in a dedicated `FormRequest` — never inline in the
  controller (CLAUDE.md).
- Frontend attributes must mirror the Form Request rule for rule: `required`,
  `min="0"`, `max`, `maxlength` matching the migration's column length, `step`
  for decimals, `type="date"`, `<select>` for every `in:` enum.
- Repopulate with `old('field', $model->field)` and render `@error` per field.
- A `422` from an API call renders the same field-level messages; a Blade post
  redirects back with the errors bag. Never swallow them.

## Print / PDF views (DomPDF)

DomPDF does **not** support CSS custom properties, flexbox, or grid. So:

- Do not `@extends('layouts.admin')`. Use a standalone print layout.
- Copy the token *values* (not `var(--…)`) into a small `<style>` block at the
  top of the template, and keep them in sync with §1.1 by name in a comment.
- Layout with tables and block elements; use `mm`/`pt` units; avoid web fonts
  unless registered with DomPDF.
- Always light-theme — never emit dark-mode colors into a document that gets
  printed.
- These templates are still UI: run them through the same design pass
  (CLAUDE.md requires `frontend-design` for PDF/receipt/invoice templates too).

## Module obligations (CLAUDE.md, enforced)

A CRUD module is not done until:

1. **Forms Management card** at `/form-configs?tab=forms` controlling which
   fields appear in that module's add/edit form — same pattern as the Building
   Form and Unit Form cards.
2. **Template Management card** at `/form-configs?tab=templates` (XLSX + CSV
   download buttons and an import modal) if the module supports import/export —
   same pattern as the Building / Unit / Lease Contracts template cards.
3. Feature tests for the routes and the Form Request, passing under
   `php artisan test`.

## Blade hygiene

- `{{ }}` escapes; `{!! !!}` does not — use it only for content you generated.
- `@selected()`, `@checked()`, `@disabled()`, `@class([])` instead of inline
  ternaries in attributes.
- `@forelse/@empty` rather than a manual `count()` check.
- Route names, never hardcoded URLs. `route('x.show', $model)`.
- Format money as `BHD {{ number_format($v, 3) }}` (Bahraini dinar is 3 dp) and
  wrap it in a `.num`/`.amount-col` cell.
- Extract anything used twice into `resources/views/components/` as a Blade
  component (see `import-modal`, `import-result`, `expense-sheet`).
- Keep controller/query logic out of the view: no `->where()` chains in Blade.
  Filtering and sorting happen server-side in the controller/scope.
