{{--
  resources/views/partials/command-palette.blade.php
  Two modes in one list. With the input empty (or one character in) it is a
  jump list: the static destinations below, filtered client-side, which is what
  makes ⌘K feel instant for "take me to Invoices".

  From two characters it becomes a record search — results arrive from
  GET /search (server-side, role-gated, LIMITed) and replace the jump list. See
  App\Http\Controllers\SearchController.
--}}
<div id="command-palette" class="palette" hidden role="dialog" aria-modal="true" aria-labelledby="palette-input">
    <div class="palette-box">
        <div class="palette-head">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input id="palette-input" data-palette-input type="text" autocomplete="off" spellcheck="false"
                   class="palette-input" placeholder="Jump to anything, or type a command…">
            <kbd class="shell-kbd">ESC</kbd>
        </div>

        <div class="palette-list" data-palette-jump>
            @php
                /* Floors, Payments, EWA bills, Expenses and Revenue have no
                   sidebar entry under DASHBOARD-SPEC.md §1, so the palette is
                   how they are reached. Keep them listed. */
                $rows = [
                    ['icon' => 'fa-building',            'title' => 'Buildings',       'sub' => 'All properties in the portfolio',   'kind' => 'GO TO',  'route' => 'buildings.index'],
                    ['icon' => 'fa-layer-group',        'title' => 'Floors',          'sub' => 'Every floor across all buildings',   'kind' => 'GO TO',  'route' => 'floors.global'],
                    ['icon' => 'fa-door-open',          'title' => 'Units',           'sub' => 'Every unit, with rent and status',   'kind' => 'GO TO',  'route' => 'property-units.index'],
                    ['icon' => 'fa-users',              'title' => 'Tenants',         'sub' => 'Tenants and applicants',             'kind' => 'GO TO',  'route' => 'tenants.index'],
                    ['icon' => 'fa-file-contract',      'title' => 'Leases',          'sub' => 'Lease contracts and renewals',       'kind' => 'GO TO',  'route' => 'lease-contracts.index'],
                    ['icon' => 'fa-file-invoice',       'title' => 'Invoices',        'sub' => 'Billed, collected and outstanding',  'kind' => 'GO TO',  'route' => 'invoices.index'],
                    ['icon' => 'fa-money-bill-transfer','title' => 'Payments',        'sub' => 'Received payments and receipts',     'kind' => 'GO TO',  'route' => 'payments.index'],
                    ['icon' => 'fa-droplet',            'title' => 'EWA bills',       'sub' => 'Electricity and water meters',       'kind' => 'GO TO',  'route' => 'ewa-bills.index'],
                    ['icon' => 'fa-receipt',            'title' => 'Expenses',        'sub' => 'Recorded portfolio expenses',        'kind' => 'GO TO',  'route' => 'expenses.index'],
                    ['icon' => 'fa-sack-dollar',        'title' => 'Revenue',         'sub' => 'Recorded portfolio revenue',         'kind' => 'GO TO',  'route' => 'revenues.index'],
                    ['icon' => 'fa-screwdriver-wrench', 'title' => 'Maintenance',     'sub' => 'Requests and vendor quotations',     'kind' => 'GO TO',  'route' => 'maintenance.index'],
                    ['icon' => 'fa-arrow-trend-down',   'title' => 'Record expense',  'sub' => 'Opens the expense form',             'kind' => 'ACTION', 'route' => 'expenses.create'],
                    ['icon' => 'fa-arrow-trend-up',     'title' => 'Record revenue',  'sub' => 'Opens the revenue form',             'kind' => 'ACTION', 'route' => 'revenues.create'],
                    ['icon' => 'fa-chart-pie',          'title' => 'Report library',  'sub' => 'Run or export a report',             'kind' => 'ACTION', 'route' => 'reports.index'],
                ];
            @endphp

            @foreach ($rows as $row)
                @php $url = Route::has($row['route']) ? route($row['route']) : null; @endphp
                @continue(! $url)
                <a href="{{ $url }}" class="palette-row" data-palette-row
                   data-search="{{ Str::lower($row['title'] . ' ' . $row['sub']) }}">
                    <span class="palette-row-icon"><i class="fa-solid {{ $row['icon'] }}"></i></span>
                    <span class="palette-row-text">
                        <span class="palette-row-title">{{ $row['title'] }}</span>
                        <span class="palette-row-sub">{{ $row['sub'] }}</span>
                    </span>
                    <span class="palette-row-kind">{{ $row['kind'] }}</span>
                </a>
            @endforeach

            <div class="palette-empty" data-palette-empty hidden>No matches.</div>
        </div>

        {{-- Record results live here; the jump list above hides while they show. --}}
        <div class="palette-list" data-palette-results hidden></div>

        <div class="palette-hint" data-palette-hint hidden>
            <i class="fa-solid fa-circle-notch fa-spin"></i> Searching…
        </div>

        <div class="palette-foot">
            <span>↑↓ navigate</span><span>↩ open</span><span>esc close</span>
        </div>
    </div>
</div>
