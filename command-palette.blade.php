{{--
  resources/views/partials/command-palette.blade.php
  Appearance + open/close only. Rows are static examples; wiring real search is later work.
--}}
<div id="command-palette" class="palette" hidden role="dialog" aria-modal="true" aria-labelledby="palette-input">
    <div class="palette-box">
        <div class="palette-head">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input id="palette-input" data-palette-input type="text" autocomplete="off" spellcheck="false"
                   class="palette-input" placeholder="Jump to anything, or type a command…">
            <kbd class="shell-kbd">ESC</kbd>
        </div>

        <div class="palette-list">
            @php
                $rows = [
                    ['icon' => 'fa-building',            'title' => 'Buildings',       'sub' => 'All properties in the portfolio', 'kind' => 'GO TO',  'route' => 'buildings.index'],
                    ['icon' => 'fa-door-closed',        'title' => 'Units',           'sub' => 'Every unit, with rent and status', 'kind' => 'GO TO',  'route' => 'property-units.index'],
                    ['icon' => 'fa-users',              'title' => 'Tenants',         'sub' => 'Tenants and applicants',           'kind' => 'GO TO',  'route' => 'tenants.index'],
                    ['icon' => 'fa-file-invoice',       'title' => 'Invoices',        'sub' => 'Billed, collected and outstanding', 'kind' => 'GO TO',  'route' => 'invoices.index'],
                    ['icon' => 'fa-arrow-trend-down',   'title' => 'Record expense',  'sub' => 'Opens the expense form',            'kind' => 'ACTION', 'route' => 'expenses.create'],
                    ['icon' => 'fa-money-bill-transfer','title' => 'Record payment',  'sub' => 'Opens the payment form',            'kind' => 'ACTION', 'route' => 'payments.create'],
                    ['icon' => 'fa-chart-pie',          'title' => 'Report library',  'sub' => 'Run or export a report',            'kind' => 'ACTION', 'route' => 'reports.index'],
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

        <div class="palette-foot">
            <span>↑↓ navigate</span><span>↩ open</span><span>esc close</span>
        </div>
    </div>
</div>
