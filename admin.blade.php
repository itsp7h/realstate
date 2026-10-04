{{--
  Desktop shell — DESKTOP-UI.md §4 / §4.1 / §4.2
  Appearance only. No offline queue, no state-dependent shortcuts.

  Assumptions, all guarded so nothing 500s if wrong:
  - Route names are resolved through Route::has(); a missing route renders as a dead item
    rather than throwing. Correct the $rail array below if a name differs.
  - Requires the token additions in app-core-additions.css.
  - @yield('page-title') / @yield('page-subtitle') / @yield('page-actions') are new.
    Existing pages that don't define them degrade to the group label and no actions.
--}}
@php
    // §4.1 — six module groups + gear. Edit route names here, nowhere else.
    $rail = [
        'overview' => [
            'icon'  => 'fa-gauge-high',
            'label' => 'Overview',
            'group' => 'OVERVIEW',
            'hint'  => 'Portfolio at a glance',
            'items' => [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high'],
            ],
        ],
        'property' => [
            'icon'  => 'fa-building',
            'label' => 'Property',
            'group' => 'PROPERTY MANAGEMENT',
            'hint'  => ($stats['buildings'] ?? 0) . ' buildings · ' . ($stats['floors'] ?? 0) . ' floors · ' . ($stats['units'] ?? 0) . ' units',
            'items' => [
                ['route' => 'buildings.index',       'label' => 'Buildings', 'icon' => 'fa-building',     'count' => $stats['buildings'] ?? null],
                ['route' => 'property-units.index',  'label' => 'Units',     'icon' => 'fa-door-closed',  'count' => $stats['units'] ?? null],
                ['route' => 'floors.index',          'label' => 'Floors',    'icon' => 'fa-layer-group',  'count' => $stats['floors'] ?? null],
            ],
        ],
        'leasing' => [
            'icon'  => 'fa-file-signature',
            'label' => 'Leasing',
            'group' => 'LEASING',
            'hint'  => 'Tenants and contracts',
            'items' => [
                ['route' => 'tenants.index',         'label' => 'Tenants',         'icon' => 'fa-users',           'count' => $stats['tenants'] ?? null],
                ['route' => 'lease-contracts.index', 'label' => 'Lease contracts', 'icon' => 'fa-file-signature',  'count' => $stats['contracts'] ?? null],
            ],
        ],
        'operations' => [
            'icon'  => 'fa-screwdriver-wrench',
            'label' => 'Operations',
            'group' => 'OPERATIONS',
            'hint'  => 'Requests and vendors',
            'items' => [
                ['route' => 'maintenance.index', 'label' => 'Maintenance', 'icon' => 'fa-screwdriver-wrench', 'count' => $stats['open_requests'] ?? null],
            ],
        ],
        'accounting' => [
            'icon'  => 'fa-receipt',
            'label' => 'Accounting',
            'group' => 'ACCOUNTING',
            'hint'  => now()->format('F Y') . ' · BHD',
            'items' => [
                ['route' => 'invoices.index',  'label' => 'Invoices',   'icon' => 'fa-file-invoice',        'count' => $stats['open_invoices'] ?? null],
                ['route' => 'payments.index',  'label' => 'Payments',   'icon' => 'fa-money-bill-transfer'],
                ['route' => 'expenses.index',  'label' => 'Expenses',   'icon' => 'fa-arrow-trend-down'],
                ['route' => 'ewa-bills.index', 'label' => 'EWA meters', 'icon' => 'fa-bolt'],
                ['route' => 'revenues.index',  'label' => 'Revenues',   'icon' => 'fa-arrow-trend-up'],
            ],
        ],
        'analytics' => [
            'icon'  => 'fa-chart-pie',
            'label' => 'Analytics',
            'group' => 'ANALYTICS',
            'hint'  => 'Reports and exports',
            'items' => [
                ['route' => 'reports.index', 'label' => 'Report library', 'icon' => 'fa-chart-pie'],
            ],
        ],
        'admin' => [
            'icon'    => 'fa-gear',
            'label'   => 'Administration',
            'group'   => 'ADMINISTRATION',
            'hint'    => 'Access and system',
            'bottom'  => true,
            'items' => [
                ['route' => 'users.index',        'label' => 'Users & roles',   'icon' => 'fa-user-shield', 'count' => $stats['users'] ?? null],
                ['route' => 'form-configs.index', 'label' => 'Forms & templates', 'icon' => 'fa-file-lines'],
                ['route' => 'audit-log.index',    'label' => 'Audit log',       'icon' => 'fa-clipboard-list'],
                ['route' => 'error-log.index',    'label' => 'Error log',       'icon' => 'fa-triangle-exclamation'],
                ['route' => 'azure-mail.edit',    'label' => 'Mail settings',   'icon' => 'fa-envelope'],
            ],
        ],
    ];

    // Active group: whichever group owns a route matching the current one.
    $current    = Route::currentRouteName() ?? '';
    $activeKey  = 'overview';
    foreach ($rail as $key => $g) {
        foreach ($g['items'] as $item) {
            $base = Str::beforeLast($item['route'], '.');
            if ($current === $item['route'] || ($base && Str::startsWith($current, $base . '.'))) {
                $activeKey = $key;
                break 2;
            }
        }
    }
    $active = $rail[$activeKey];

    $href = fn ($name) => Route::has($name) ? route($name) : null;
    $isOn = function ($name) use ($current) {
        $base = Str::beforeLast($name, '.');
        return $current === $name || ($base && Str::startsWith($current, $base . '.'));
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', $active['label']) — Promoseven RE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app-core.css') }}">
    @stack('styles')
</head>
<body class="app-shell">

{{-- ══ 29px menu bar ══ §4.2 ══ --}}
<div class="shell-menubar">
    <nav class="shell-menubar-items" aria-label="Application menu">
        @foreach (['File', 'Edit', 'View', 'Records', 'Accounting', 'Window', 'Help'] as $menu)
            <button type="button" class="shell-menubar-item">{{ $menu }}</button>
        @endforeach
    </nav>
    <div class="shell-sync" title="Last synchronised {{ now()->format('H:i') }}">
        <span class="shell-sync-dot"></span>SYNCED {{ now()->format('H:i') }}
    </div>
</div>

{{-- ══ 44px toolbar ══ §4.2 ══ --}}
<div class="shell-toolbar">
    <div class="shell-nav-btns">
        <button type="button" class="shell-iconbtn" onclick="history.back()" title="Back" aria-label="Back">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button type="button" class="shell-iconbtn" onclick="history.forward()" title="Forward" aria-label="Forward">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <button type="button" class="shell-search" data-palette-open aria-label="Search — Command K">
        <i class="fa-solid fa-magnifying-glass"></i>
        <span class="shell-search-label">Search buildings, units, tenants, invoices…</span>
        <kbd class="shell-kbd">⌘K</kbd>
    </button>

    <div class="shell-toolbar-spacer"></div>

    <button type="button" class="shell-pill" data-density-toggle>
        <i class="fa-solid fa-bars-staggered"></i>
        <span data-density-label>Comfortable</span>
    </button>

    <button type="button" class="shell-iconbtn shell-iconbtn-sm" data-theme-toggle title="Toggle theme" aria-label="Toggle light and dark theme">
        <i class="fa-solid fa-moon" data-theme-icon></i>
    </button>

    <button type="button" class="shell-iconbtn shell-iconbtn-sm shell-bell" title="Notifications" aria-label="Notifications">
        <i class="fa-regular fa-bell"></i>
        @if (($stats['notifications'] ?? 0) > 0)
            <span class="shell-badge">{{ $stats['notifications'] }}</span>
        @endif
    </button>

    <div class="shell-avatar" title="{{ auth()->user()->email ?? '' }}">
        {{ Str::upper(Str::substr(auth()->user()->name ?? 'U', 0, 1)) }}
    </div>
</div>

<div class="shell-body">

    {{-- ══ 54px icon rail ══ §4.1 ══ --}}
    <nav class="shell-rail" aria-label="Modules">
        @foreach ($rail as $key => $group)
            @continue(! empty($group['bottom']))
            @php $first = $href($group['items'][0]['route']); @endphp
            <a href="{{ $first ?? '#' }}"
               class="shell-rail-btn @if($activeKey === $key) is-active @endif"
               title="{{ $group['label'] }}"
               aria-label="{{ $group['label'] }}"
               @if($activeKey === $key) aria-current="true" @endif>
                <i class="fa-solid {{ $group['icon'] }}"></i>
            </a>
        @endforeach

        <div class="shell-rail-spacer"></div>

        @php $adminFirst = $href($rail['admin']['items'][0]['route']); @endphp
        <a href="{{ $adminFirst ?? '#' }}"
           class="shell-rail-btn @if($activeKey === 'admin') is-active @endif"
           title="Administration" aria-label="Administration">
            <i class="fa-solid fa-gear"></i>
        </a>
    </nav>

    {{-- ══ 212px contextual sidebar ══ §4.1 ══ --}}
    <aside class="shell-sidebar" aria-label="{{ $active['label'] }} pages">
        <div class="shell-sidebar-head">
            <div class="shell-sidebar-group">{{ $active['group'] }}</div>
            <div class="shell-sidebar-hint">{{ $active['hint'] }}</div>
        </div>

        <div class="shell-sidebar-items">
            @foreach ($active['items'] as $item)
                @php $url = $href($item['route']); @endphp
                <a href="{{ $url ?? '#' }}"
                   class="shell-navitem @if($isOn($item['route'])) is-active @endif @if(! $url) is-missing @endif"
                   @if($isOn($item['route'])) aria-current="page" @endif>
                    <i class="fa-solid {{ $item['icon'] }}"></i>
                    <span class="shell-navitem-label">{{ $item['label'] }}</span>
                    @isset($item['count'])
                        <span class="shell-navitem-count">{{ $item['count'] }}</span>
                    @endisset
                </a>
            @endforeach
        </div>

        <div class="shell-sidebar-foot">
            <div class="shell-sidebar-avatar">
                {{ Str::upper(Str::substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="shell-sidebar-user">
                <div class="shell-sidebar-name">{{ auth()->user()->name ?? 'Guest' }}</div>
                <div class="shell-sidebar-email">{{ auth()->user()->email ?? '' }}</div>
            </div>
            <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
                @csrf
                <button type="submit" class="shell-logout" title="Sign out" aria-label="Sign out">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </aside>

    {{-- ══ main ══ --}}
    <main class="shell-main">
        <header class="shell-pagehead">
            <div class="shell-pagehead-text">
                <h1 class="shell-pagehead-title">@yield('page-title', $active['label'])</h1>
                <div class="shell-pagehead-sub">@yield('page-subtitle')</div>
            </div>
            <div class="shell-pagehead-actions">
                @yield('page-actions')
            </div>
        </header>

        <div class="shell-content">
            @includeWhen(session('status') || $errors->any(), 'partials.alerts')
            @yield('content')
        </div>
    </main>
</div>

@include('partials.command-palette')

<script>
(function () {
    var root = document.documentElement;

    // theme — persisted, matches whatever key app-core.css already reads
    var THEME = 'p7-theme', DENSITY = 'p7-density';
    function paintTheme(t) {
        root.setAttribute('data-theme', t);
        var i = document.querySelector('[data-theme-icon]');
        if (i) i.className = t === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
    function paintDensity(d) {
        root.setAttribute('data-density', d);
        var l = document.querySelector('[data-density-label]');
        if (l) l.textContent = d === 'compact' ? 'Compact' : 'Comfortable';
    }
    try {
        paintTheme(localStorage.getItem(THEME) || 'light');
        paintDensity(localStorage.getItem(DENSITY) || 'comfortable');
    } catch (e) {}

    var tb = document.querySelector('[data-theme-toggle]');
    if (tb) tb.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        paintTheme(next);
        try { localStorage.setItem(THEME, next); } catch (e) {}
    });

    var db = document.querySelector('[data-density-toggle]');
    if (db) db.addEventListener('click', function () {
        var next = root.getAttribute('data-density') === 'compact' ? 'comfortable' : 'compact';
        paintDensity(next);
        try { localStorage.setItem(DENSITY, next); } catch (e) {}
    });

    // command palette — open/close only, per §6 scope note
    var palette = document.getElementById('command-palette');
    var input   = palette && palette.querySelector('[data-palette-input]');
    var opener  = null;

    function openPalette() {
        if (!palette) return;
        opener = document.activeElement;
        palette.classList.add('is-open');
        palette.removeAttribute('hidden');
        if (input) { input.value = ''; input.focus(); filter(''); }
    }
    function closePalette() {
        if (!palette) return;
        palette.classList.remove('is-open');
        palette.setAttribute('hidden', '');
        if (opener && opener.focus) opener.focus();
    }
    function filter(q) {
        q = (q || '').toLowerCase();
        var any = false;
        palette.querySelectorAll('[data-palette-row]').forEach(function (row) {
            var hit = row.dataset.search.indexOf(q) !== -1;
            row.hidden = !hit;
            if (hit) any = true;
        });
        var empty = palette.querySelector('[data-palette-empty]');
        if (empty) empty.hidden = any;
    }

    document.querySelectorAll('[data-palette-open]').forEach(function (b) {
        b.addEventListener('click', openPalette);
    });
    if (palette) {
        palette.addEventListener('click', function (e) {
            if (e.target === palette) closePalette();
        });
        if (input) input.addEventListener('input', function () { filter(input.value); });
    }

    document.addEventListener('keydown', function (e) {
        var k = (e.key || '').toLowerCase();
        if ((e.metaKey || e.ctrlKey) && k === 'k') {
            e.preventDefault();
            palette && palette.classList.contains('is-open') ? closePalette() : openPalette();
            return;
        }
        if (e.key === 'Escape' && palette && palette.classList.contains('is-open')) closePalette();
    });
})();
</script>

@stack('scripts')
</body>
</html>
