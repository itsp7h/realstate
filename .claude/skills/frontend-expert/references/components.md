# Component catalog

Every component below already exists in `public/css/app-core.css`. Use the class;
do not re-implement it in a page's `@push('styles')`. Section numbers point at
the CSS so you can read the full rule set.

---

## Page structure (§3)

```blade
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
```

One `<h1>` per page. `.page-header-actions` holds at most one primary button;
everything else is `.btn-outline` or `.btn-ghost`. `.section-stack` for stacked
sections, `.section-note` for a small explanatory line under a heading.

## Buttons (§4.1)

One hierarchy, one box — variants change **color only**.

| Class | Use |
|---|---|
| `.btn.btn-primary` | the single main action on the screen |
| `.btn.btn-outline` | secondary actions, and every table-row action |
| `.btn.btn-ghost` | tertiary / low-noise (toolbars, card headers) |
| `.btn.btn-danger` | destructive — soft by default, filled on hover |
| `.btn.btn-export` | every Export trigger — render it via `<x-export-button>`, never by hand |
| `.btn-sm` / `.btn-lg` / `.btn-icon` / `.btn-block` | dense rows / forms & mobile / icon-only / full width |

Icon-only buttons need `aria-label`. Icons sit before the label and never
outweigh it. A `<a>` styled as a button still needs a real `href`.

Export is not a free choice. Every export trigger — a list toolbar's menu, a
report's XLSX link, the Import/Export page — is `<x-export-button>`, which is
`.btn.btn-export` and nothing else. `partials/export-menu.blade.php` wraps it in
the two-format popover. There is no green button in the system any more; the
`.btn-success` variant was deleted so the old Export style cannot come back.

## Forms (§4.2)

```blade
<form class="form-card" method="POST" action="{{ route('units.store') }}">
    @csrf
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label" for="unit_name">Unit name <span class="required">*</span></label>
            <input class="form-control @error('unit_name') is-invalid @enderror"
                   id="unit_name" name="unit_name" type="text" maxlength="120" required
                   value="{{ old('unit_name') }}">
            <p class="field-help">Shown on invoices and the tenant portal.</p>
            @error('unit_name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div class="form-group col-span-2">…</div>
        <div class="form-group col-span-full">…</div>
    </div>
    <div class="form-actions">
        <a href="{{ route('units.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">Save unit</button>
    </div>
</form>
```

Rules:
- Every field height comes from `--h-control`; never set one locally.
- Every input has a real `<label for>`. Placeholder is not a label.
- **Frontend validation mirrors the Form Request exactly**: `required`, `min`,
  `max`, `maxlength`, `step`, `pattern`, `type`. Enum fields are `<select>`,
  never free text (CLAUDE.md).
- Errors are field-level (`.field-error` / `.invalid-feedback`). Never a generic
  "something went wrong". Mark the field `aria-invalid="true"` and point
  `aria-describedby` at the message id.
- `.form-check` for checkbox/radio rows; the label is the click target.

## Cards (§4.3)

`.card > .card-header (.card-header-icon, .card-header-actions) + .card-body`

Modifiers: `.is-interactive` (hover lift + focus ring; must be an `<a>` or
`<button>`, and gets `.card-chevron` when it navigates) · `.is-accent` (accent
border on hover, for "pick a report / pick an import target") · `.is-hero` (the
identity block at the top of a detail page — title steps up to `--fs-xl`) ·
`.has-rail` (`--card-rail` tone stripe on the leading edge for a status).

## Stats (§4.4)

```blade
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="stat-body">
            <div class="stat-val">BHD {{ number_format($stats['collected'], 3) }}</div>
            <div class="stat-lbl">Collected this month</div>
        </div>
    </div>
</div>
```

`.stat-icon` tone classes: `.gold/.accent .green/.success .blue/.info
.red/.danger .amber/.warning .gray/.neutral`.

Variants: `.stat-card.is-figure` (no icon, big number + uppercase caption — for
dense rows of counts like the audit/error logs; add `.is-danger/.is-warning/
.is-info/.is-success` to tone the figure) · `.stat-tile` (label above figure with
a tone rail via `--tile-rail`, for a few headline figures compared against each
other, e.g. the P&L's revenue / expenses / net).

Do **not** invent a ninth stat card. `.dash-stat`, `.inv-stat-*`, `.pay-stat`
are legacy aliases (§6) kept alive for old pages — never use them in new work.

## Filter bar (§4.5)

Two containers, identical internals — pick by what the filters belong to:

- `.table-card > .filter-bar` — a **list page**: filters narrow a list already on
  screen, so they live inside the table's card.
- `.filter-card > .filter-bar` — a **report page**: the filters *are* the query,
  and the result below swaps between a table and an empty state.

Nothing else is a third option (no bare `.filter-bar` on the page background, no
unlabelled controls).

```blade
<form method="GET" action="{{ route('payments.index') }}" class="filter-bar">
    <div class="filter-group is-search">
        <label for="q">Search</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Tenant, invoice, reference…">
    </div>
    <div class="filter-group">
        <label for="method">Method</label>
        <select id="method" name="method">
            <option value="">All</option>
            @foreach($methods as $val => $label)
                <option value="{{ $val }}" @selected(request('method') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-actions">
        <button class="btn btn-primary btn-sm" type="submit">Apply</button>
        <a class="btn btn-ghost btn-sm" href="{{ route('payments.index') }}">Reset</a>
    </div>
</form>
```

Server-side only — the form GETs and the backend returns filtered, paginated
rows. Every control is labelled and every value is re-populated from
`request()`.

## Tables & data grids (§4.6)

```
.table-card
  ├── .filter-bar            (optional)
  ├── .table-wrap [.is-scroll]
  │     └── table > thead/tbody
  └── .table-footer > .result-count + .pagination
```

```blade
<div class="table-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th class="amount-col">Amount</th>
                    <th class="right">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($payments as $payment)
                <tr data-href="{{ route('payments.show', $payment) }}">
                    <td data-label="Tenant">
                        <div class="cell-title">{{ $payment->tenant->name }}</div>
                        <div class="cell-sub">{{ $payment->reference }}</div>
                    </td>
                    <td data-label="Amount" class="amount-col num-strong">BHD {{ number_format($payment->amount, 3) }}</td>
                    <td data-label="Actions" class="action-btns" onclick="event.stopPropagation()">
                        <a class="btn btn-outline btn-sm" href="{{ route('payments.edit', $payment) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">
                    <div class="empty-state">…see Empty state below…</div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <div class="result-count">{{ $payments->total() }} payments</div>
        {{ $payments->withQueryString()->links() }}
    </div>
</div>
```

- Sticky header, single hover state, one Actions treatment — all from the CSS.
- Numeric columns: `.amount-col` / `.num` / `.num-strong`, right-aligned,
  tabular figures. Totals use `.total-row` / `.grand-total-row`.
- **`data-label` on every `<td>` is mandatory** — that's what the ≤768px
  table→card transformation renders as the key.
- `.table-wrap.is-scroll` only for a genuinely wide financial matrix (ageing
  report, rent schedule) where comparing across columns is the point.
- Row click via `data-href`; the global handler lives in the layout. Wrap the
  actions cell in `onclick="event.stopPropagation()"`.
- `.nowrap` / `.wrap-anywhere` for column-level wrapping control.

## Pagination (§4.6.2)

`.pagination > .page-btn`. Current page carries `aria-current="page"`; disabled
carries `aria-disabled="true"`. Always paginate server-side and preserve filters
with `->withQueryString()`.

## Empty state (§4.7)

```blade
<div class="empty-state">
    <div class="empty-icon"><i class="fa-solid fa-receipt"></i></div>
    <h4>No payments yet</h4>
    <p>Payments recorded against an invoice will appear here.</p>
    <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm">Record payment</a>
</div>
```

An empty screen is an invitation to act — include the action unless the user
genuinely can't create the thing from here. A filtered-to-nothing state says so
and offers "Reset filters", which is different from a never-had-any state.

## Badges (§4.8)

`.badge` is the neutral pill; `.status-badge` is the same box with an explicit
status meaning. Tone modifiers: `.badge-gold/.tone-accent`,
`.badge-green/.tone-success`, `.badge-blue/.tone-info`, `.badge-red/.tone-danger`,
`.badge-amber/.tone-warning`, `.badge-gray/.tone-neutral`, plus `.badge-outline`.

Domain status names are mapped onto those tones directly — `.paid`, `.unpaid`,
`.partial`, `.overdue`, `.draft`, `.issued`, `.sent`, `.cancelled`, `.active`,
`.expired`, `.expiring`, `.upcoming`, `.vacant`, `.open`, `.closed`, `.pending`,
`.approved`, `.rejected`, `.completed`, `.urgent`, `.failed`, `.created`,
`.updated`, `.deleted`, `.imported`, `.error`, `.warning`, `.info`, `.debug`.
Prefer the domain class (`class="status-badge {{ $invoice->status }}"`) over
picking a tone by hand — that's what keeps a status the same color app-wide.

Never encode meaning by color alone; the badge always carries text.

## Alerts (§4.9)

`.alert.alert-success / .alert-danger / .alert-info / .alert-warning`. Flash
messages go through these, not a bespoke div. Give a dismissible alert a real
`<button class="btn-icon" aria-label="Dismiss">`.

## Tabs (§4.10)

`.tab-bar > .tab-btn`. Tabs that change the page's data are links carrying a
query param (`?tab=forms`) so the state is shareable and server-rendered — that
is how `/form-configs` works. Only use JS tabs for panels that are already all
on the page; then wire `role="tablist"/"tab"/"tabpanel"`, `aria-selected`, and
arrow-key navigation.

## Modals (§4.11)

```blade
<div class="modal-overlay" id="deleteModal" style="--modal-w: 420px" role="dialog"
     aria-modal="true" aria-labelledby="deleteModalTitle" hidden>
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon"><i class="fa-solid fa-trash"></i></div>
                <div>
                    <h3 class="modal-header-title" id="deleteModalTitle">Delete payment</h3>
                    <p class="modal-header-sub">This cannot be undone.</p>
                </div>
            </div>
            <button class="modal-close-btn" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">…</div>
        <div class="modal-footer">
            <button class="btn btn-outline" data-close>Cancel</button>
            <button class="btn btn-danger" type="submit">Delete</button>
        </div>
    </div>
</div>
```

- Width per use via `--modal-w`, never a new class per page.
- On mobile `app-mobile.css` turns this **same markup** into a bottom sheet —
  which is why that file loads last and why you must not `!important` your way
  past it, or invent a different modal structure.
- Focus moves into the dialog on open, is trapped while open, returns to the
  trigger on close. `Esc` closes. The overlay click closes only non-destructive
  dialogs.
- Do not use `bootstrap.Modal`.

## Loading states (§4.12)

`.skeleton` / `.skeleton-text` for content that is about to arrive, `.spinner`
for an action in flight. A button doing work gets `disabled` plus a spinner and
keeps its width so the layout doesn't jump. Announce long operations with
`aria-live="polite"`.

## Misc

`.code-block` (log payloads), `.breadcrumb`, `.nav-badge`, `.sr-only`,
`.skip-link`, `.val-positive/.val-negative/.val-warning/.val-muted` (§4.4b value
tones for figures inside tables and cards).

## §6 legacy aliases

The bottom of `app-core.css` aliases old per-page class names onto the canonical
components. They exist so untouched pages keep rendering. **Never write new
markup against an alias**, and when you touch an old page, migrate its markup to
the canonical class as part of the change.
