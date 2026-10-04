<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $branding = \App\Models\BrandingSetting::current();
    @endphp
    <title>@yield('title', 'Dashboard') — {{ $branding->displaySiteName() }}</title>

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#1E2C4F">
    <link rel="icon" type="image/png" href="{{ $branding->faviconUrl() ?: asset('icons/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $branding->displaySiteName() }}">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }
    </script>
    <script>
        (function () {
            // Applied before first paint to avoid a flash of the wrong theme.
            var saved = localStorage.getItem('p7-theme');
            var theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            // The sidebar's collapsed state, same before-paint treatment as
            // the theme so the frame does not jump on load.
            if (localStorage.getItem('p7-nav') === 'collapsed') {
                document.documentElement.classList.add('nav-collapsed-boot');
            }
        })();
    </script>
    <script>
        (function () {
            // The back-chevron on a pushed screen is the only place a
            // "pop" (backwards) navigation is initiated from; every other
            // navigation defaults to "push" (see check below), so we only
            // need to flag the back case before the browser unloads.
            document.addEventListener('click', function (e) {
                if (e.target.closest('.pm-push-back')) {
                    sessionStorage.setItem('pm-nav-dir', 'pop');
                }
            }, true);

            if (typeof PageRevealEvent === 'undefined') return;
            window.addEventListener('pagereveal', function (e) {
                if (!e.viewTransition) return;
                var wasBack = sessionStorage.getItem('pm-nav-dir') === 'pop';
                sessionStorage.removeItem('pm-nav-dir');
                if (wasBack) {
                    e.viewTransition.types.add('pop');
                } else if (document.body.classList.contains('is-pushed-screen')) {
                    e.viewTransition.types.add('push');
                }
            });
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- ── Vendor CSS ────────────────────────────────────────────────────────
         Third-party stylesheets go here, BEFORE the design system, so the
         design system always wins on any class they share. Only one page uses
         this (buildings/show pushes Bootstrap 5 for its grid, tabs and modal
         JS); nothing new should. Loading a vendor sheet after app-core.css
         means its .card/.btn/.badge/.table beat ours and that page stops
         looking like the rest of the app. --}}
    @stack('vendor-styles')

    {{-- ── Design system ────────────────────────────────────────────────────
         Four slots, and the order between them matters:

           0. @stack('vendor-styles')  third-party CSS the design system overrides
           1. app-core.css     tokens, app shell, every shared component
           2. @stack('styles') per-page overrides
           3. app-mobile.css   mobile app layer — intentionally loaded last

         Step 3 is why the mobile layer can turn every .modal-overlay in the
         app into a bottom sheet: at equal specificity the later rule wins, so
         it beats a page's own modal CSS without needing an edit per page.
         The full contract is documented at the top of app-core.css.

         Both files are static and cache-busted on their own mtime, so a
         deploy invalidates them without a build step. --}}
    @php
        $css = fn (string $path) => asset($path).'?v='.(@filemtime(public_path($path)) ?: 0);
    @endphp
    <link rel="stylesheet" href="{{ $css('css/app-core.css') }}">

    @stack('styles')

    <link rel="stylesheet" href="{{ $css('css/app-mobile.css') }}">
</head>
@php
    /* ── Desktop shell nav model — DASHBOARD-SPEC.md §1 ───────────────────
       The spec's three groups, superseding DESKTOP-UI.md §4.1's six-group
       icon rail. Two deviations from its item list, both deliberate: every
       page in the app has an entry here — a page reachable only by ⌘K or a
       typed URL is a page users cannot find — and the spec's single
       'Bills & Payments' item is the five accounting pages it stands for
       (Invoices, Payments, EWA bills, Expenses, Revenue), since collapsing
       them into one label leaves four of them with no way in.

       Route names appear here and nowhere else, and every one is resolved
       through Route::has(). Two items — Roles & Permissions, Settings — have
       no route in this build; they render dimmed and inert (.is-missing) so
       the gap is visible instead of silently absent.

       'when' gates an item on the signed-in user's role, mirroring the
       ≤768px drawer below. ── */
    $railUser  = auth()->user();
    $railAdmin = (bool) $railUser?->isAdmin();
    /* Gates named after the area, not the role — see the capability methods on
       App\Models\User. Two roles are now confined to a slice of the app
       (Maintenance, Accountant), so "not Maintenance" is no longer the same
       question as "may reach the portfolio". */
    $railPortfolio   = (bool) $railUser?->canAccessPortfolio();
    $railAccounting  = (bool) $railUser?->canAccessAccounting();
    $railMaintenance = (bool) $railUser?->canAccessMaintenance();
    $railConfig      = (bool) $railUser?->canOpenConfiguration();

    $navGroups = [
        'OVERVIEW' => [
            ['route' => 'dashboard',       'label' => 'Dashboard',     'icon' => 'fa-gauge-high'],
            ['route' => 'admin.audit-log', 'label' => 'Activity Feed', 'icon' => 'fa-clock-rotate-left', 'when' => $railAdmin],
        ],
        'PORTFOLIO' => [
            ['route' => 'buildings.index',       'label' => 'Buildings',   'icon' => 'fa-building',      'when' => $railPortfolio, 'count' => $stats['buildings'] ?? null],
            ['route' => 'floors.global',         'label' => 'Floors',      'icon' => 'fa-layer-group',   'when' => $railPortfolio, 'count' => $stats['floors'] ?? null],
            ['route' => 'property-units.index',  'label' => 'Units',       'icon' => 'fa-door-open',     'when' => $railPortfolio, 'count' => $stats['units'] ?? null],
            ['route' => 'tenants.index',         'label' => 'Tenants',     'icon' => 'fa-users',         'when' => $railPortfolio],
            ['route' => 'lease-contracts.index', 'label' => 'Leases',      'icon' => 'fa-file-contract', 'when' => $railPortfolio],
            /* One item, five destinations. The five accounting pages are one
               piece of work to the user, so they are one row that opens —
               not five top-level rows competing with Buildings. */
            ['label' => 'Bills & Payments', 'icon' => 'fa-file-invoice-dollar', 'when' => $railAccounting, 'children' => [
                ['route' => 'invoices.index',  'label' => 'Invoices'],
                ['route' => 'payments.index',  'label' => 'Payments'],
                ['route' => 'ewa-bills.index', 'label' => 'EWA Bills'],
                ['route' => 'expenses.index',  'label' => 'Expenses'],
                ['route' => 'revenues.index',  'label' => 'Revenue'],
            ]],
            ['route' => 'maintenance.index',     'label' => 'Maintenance', 'icon' => 'fa-screwdriver-wrench', 'when' => $railMaintenance],
            ['route' => 'reports.index',         'label' => 'Reports',     'icon' => 'fa-chart-pie',     'when' => (bool) $railUser?->canViewReports()],
        ],
        'CONFIGURATION' => [
            ['route' => 'users.index',              'label' => 'Users',               'icon' => 'fa-user-shield', 'when' => $railAdmin],
            ['route' => 'roles.index',              'label' => 'Roles & Permissions', 'icon' => 'fa-user-lock',   'when' => $railAdmin],
            ['label' => 'Settings', 'icon' => 'fa-gear', 'when' => $railConfig, 'children' => [
                ['route' => 'form-configs.index',       'label' => 'Forms & Templates'],
                ['route' => 'data.index',               'label' => 'Import & Export'],
                ['route' => 'settings.branding.edit',   'label' => 'Branding',       'when' => $railAdmin],
                ['route' => 'settings.azure-mail.edit', 'label' => 'Mail Settings',  'when' => $railAdmin],
                ['route' => 'admin.error-log',          'label' => 'Error Log',      'when' => $railAdmin],
            ]],
        ],
    ];

    foreach ($navGroups as $group => $items) {
        $items = array_values(array_filter($items, fn ($i) => $i['when'] ?? true));
        foreach ($items as $k => $item) {
            if (isset($item['children'])) {
                $items[$k]['children'] = array_values(array_filter(
                    $item['children'], fn ($c) => $c['when'] ?? true
                ));
                if ($items[$k]['children'] === []) {
                    unset($items[$k]);
                }
            }
        }
        $navGroups[$group] = array_values($items);
    }
    $navGroups = array_filter($navGroups, fn ($items) => $items !== []);

    /* Same resource family counts as active, so buildings.create still lights
       up Buildings. */
    $railCurrent = Route::currentRouteName() ?? '';
    $railIsOn = function (string $name) use ($railCurrent) {
        $base = Str::beforeLast($name, '.');
        return $railCurrent === $name || ($base !== '' && Str::startsWith($railCurrent, $base.'.'));
    };
    $railHref = fn (string $name) => Route::has($name) ? route($name) : null;

    $mobileRedesignedRoutes = [
        'buildings.index', 'floors.global', 'property-units.index', 'tenants.index',
        'maintenance.index', 'invoices.index', 'reports.index', 'tenants.show', 'buildings.show',
        'lease-contracts.index', 'payments.index',
        'ewa-bills.index', 'expenses.index', 'revenues.index',
    ];
    $isMobileScreen = request()->routeIs($mobileRedesignedRoutes);
    $pushedScreenRoutes = ['tenants.show', 'buildings.show'];
    $isPushedScreen = request()->routeIs($pushedScreenRoutes);
@endphp
<body class="app-shell {{ request()->routeIs('dashboard') ? 'is-dashboard' : '' }} {{ $isMobileScreen ? 'is-mobile-screen' : '' }} {{ $isPushedScreen ? 'is-pushed-screen' : '' }}">

{{-- ── EXPERIMENT: "Depth & Motion" shared helpers ─────────────────────
     Declared immediately after <body> opens (before @yield('content')
     renders) so any per-page script calling these — e.g. buildings/show
     wiring up pmInitHeroParallax — always finds them already defined.
     All opt-in: each helper only touches elements that carry the
     relevant marker class/attribute, so pages that don't use them are
     unaffected. ── --}}
<script>
(function () {
    /* The ripple is gone: every tappable element already takes the
       0.98 press scale from app-mobile.css, and two feedback systems
       firing on one tap read as a glitch rather than as depth. */

    /* Reduced motion is checked once, here, and every helper below
       obeys it — an animation that respects the setting in CSS but not
       in JS still moves. */
    const psStill = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Figures count up on arrival: 600ms, phone only, zeros skipped
       (there is nothing to count to, and a "0" that ticks reads as a
       loading state). The prefix and suffix around the number are kept
       verbatim, so "BHD 24,850" and "63%" both survive. */
    window.psInitCountUp = function (root) {
        if (psStill || window.innerWidth > 768) return;
        (root || document).querySelectorAll('.ps-stat-value, [data-countup]').forEach(function (el) {
            if (el.dataset.countupDone) return;
            const raw = el.textContent.trim();
            const m = raw.match(/^(\D*?)([\d,]+(?:\.\d+)?)(\D*)$/);
            if (!m) return;
            const target = parseFloat(m[2].replace(/,/g, ''));
            if (!isFinite(target) || target === 0) return;
            const decimals = (m[2].split('.')[1] || '').length;
            const grouped = m[2].includes(',');
            el.dataset.countupDone = '1';
            const start = performance.now();
            (function step(now) {
                const t = Math.min(1, (now - start) / 600);
                /* ease-out cubic, so it decelerates into the real figure */
                const v = target * (1 - Math.pow(1 - t, 3));
                const shown = decimals ? v.toFixed(decimals) : String(Math.round(v));
                el.textContent = m[1] + (grouped ? Number(shown).toLocaleString('en-US', {
                    minimumFractionDigits: decimals, maximumFractionDigits: decimals,
                }) : shown) + m[3];
                if (t < 1) requestAnimationFrame(step);
                else el.textContent = raw;
            })(start);
        });
    };

    /* The bell's count pops once, on the load where it went up — not on
       every page view that happens to have a non-zero count. The last
       seen number lives in sessionStorage, so it is per tab and never
       leaves the device. */
    window.psInitBellPop = function () {
        if (psStill) return;
        const badges = document.querySelectorAll('[data-bell-count]');
        const count = badges.length ? parseInt(badges[0].dataset.bellCount, 10) || 0 : 0;
        let seen = 0;
        try { seen = parseInt(sessionStorage.getItem('psBellSeen'), 10) || 0; } catch (e) { seen = 0; }
        if (count > seen) {
            badges.forEach(function (b) {
                b.classList.add('is-pop');
                b.addEventListener('animationend', () => b.classList.remove('is-pop'), { once: true });
            });
        }
        try { sessionStorage.setItem('psBellSeen', String(count)); } catch (e) { /* private mode */ }
    };

    /* Collapsing large-title header: pass the header element, its
       scroll container, and the pixel threshold to shrink at.

       Toggles two classes off one listener. `.is-scrolled` is the bottom
       hairline, and appears as soon as anything has moved under the header —
       that is the whole signal it carries, so its threshold is 0, not the
       caller's. `.is-collapsed` is the title shrink at the caller's
       threshold, and only .is-collapsible headers style it. */
    window.pmInitCollapsingHeader = function (header, scroller, threshold) {
        if (!header || !scroller) return;
        threshold = threshold || 36;
        const read = () => (scroller === window ? window.scrollY : scroller.scrollTop);
        let ticking = false;
        function update() {
            const y = read();
            header.classList.toggle('is-scrolled', y > 0);
            header.classList.toggle('is-collapsed', y > threshold);
            ticking = false;
        }
        (scroller === window ? window : scroller).addEventListener('scroll', function () {
            if (!ticking) { requestAnimationFrame(update); ticking = true; }
        }, { passive: true });
        update();
    };

    /* Hero parallax: pass the photo element + its scroll source (window
       for normal-flow "pushed" screens like Building detail). */
    window.pmInitHeroParallax = function (photo, scroller) {
        if (!photo) return;
        scroller = scroller || window;
        let ticking = false;
        function update() {
            const y = scroller === window ? window.scrollY : scroller.scrollTop;
            const clamped = Math.max(0, Math.min(y, 160));
            photo.style.setProperty('--pm-parallax', (clamped * 0.35).toFixed(1));
            ticking = false;
        }
        (scroller === window ? window : scroller).addEventListener('scroll', function () {
            if (!ticking) { requestAnimationFrame(update); ticking = true; }
        }, { passive: true });
        update();
    };

    /* True sliding-pill segmented control. Pass the `.pm-segment`
       wrapper; positions/sizes a `.pm-segment-thumb` under whichever
       button carries `.active`, and keeps it in sync on click/resize. */
    window.pmInitSegmentThumb = function (segment) {
        if (!segment) return;
        let thumb = segment.querySelector('.pm-segment-thumb');
        if (!thumb) {
            thumb = document.createElement('div');
            thumb.className = 'pm-segment-thumb';
            segment.prepend(thumb);
        }
        function place() {
            const active = segment.querySelector('.pm-seg-btn.active');
            if (!active) return;
            thumb.style.width = active.offsetWidth + 'px';
            thumb.style.transform = 'translateX(' + active.offsetLeft + 'px)';
        }
        segment.querySelectorAll('.pm-seg-btn').forEach((btn) => {
            btn.addEventListener('click', () => requestAnimationFrame(place));
        });
        window.addEventListener('resize', place);
        requestAnimationFrame(place);
    };

    /* Pull-to-refresh: attach to a `.pm-scroll` container. Only arms
       when the container is already scrolled to the very top, so it
       never fights normal scrolling. */
    window.pmInitPullToRefresh = function (container) {
        if (!container) return;
        const indicator = document.createElement('div');
        indicator.className = 'pm-ptr-indicator';
        indicator.innerHTML = '<div class="pm-ptr-spinner"></div>';
        container.prepend(indicator);
        const spinner = indicator.querySelector('.pm-ptr-spinner');

        let startY = null, pulling = false;
        const threshold = 68;

        container.addEventListener('touchstart', (e) => {
            if (container.scrollTop > 0) { startY = null; return; }
            startY = e.touches[0].clientY;
            pulling = true;
        }, { passive: true });

        container.addEventListener('touchmove', (e) => {
            if (!pulling || startY === null) return;
            const dy = e.touches[0].clientY - startY;
            if (dy <= 0) return;
            const progress = Math.min(1, dy / threshold);
            indicator.style.opacity = progress;
            indicator.style.transform = 'translateY(' + (progress * 64 - 64) + '%)';
            spinner.style.transform = 'rotate(' + (progress * 280) + 'deg)';
        }, { passive: true });

        container.addEventListener('touchend', (e) => {
            if (!pulling || startY === null) return;
            pulling = false;
            const dy = (e.changedTouches[0].clientY - startY);
            if (dy > threshold) {
                spinner.classList.add('is-loading');
                indicator.style.opacity = 1;
                indicator.style.transform = 'translateY(0)';
                window.location.reload();
            } else {
                indicator.style.opacity = 0;
                indicator.style.transform = 'translateY(-100%)';
            }
            startY = null;
        });
    };
})();
</script>

{{-- ══════════════════════ DESKTOP SHELL ══════════════════════
     DASHBOARD-SPEC.md §1. Rendered on every page, hidden ≤768px where the
     mobile app layer owns the screen. The palette opens and closes but does
     not search real records yet (DESKTOP-UI.md §6). ── --}}
<aside class="shell-sidebar" aria-label="Main navigation">
    <a href="{{ url('/dashboard') }}" class="shell-brand">
        <span class="shell-brand-mark">
            @if($branding->logoUrl())
                <img src="{{ $branding->logoUrl() }}" alt="{{ $branding->displaySiteName() }}">
            @else
                {{ $branding->initials() }}
            @endif
        </span>
        <span class="shell-brand-text">
            <span class="shell-brand-name">{{ $branding->displaySiteName() }}</span>
            <span class="shell-brand-sub">{{ $branding->tagline ?: 'Management Suite' }}</span>
        </span>
    </a>

    <nav class="shell-nav">
        @foreach ($navGroups as $group => $items)
            <div class="shell-nav-group">{{ $group }}</div>

            @foreach ($items as $item)
                @if (isset($item['children']))
                    @php
                        /* Open if the current page is one of its children, so
                           landing on Payments from anywhere shows where you
                           are without a click. */
                        $childOn = collect($item['children'])->contains(fn ($c) => $railIsOn($c['route']));
                    @endphp
                    <details class="shell-navgroup"{{ $childOn ? ' open' : '' }}>
                        <summary class="shell-navitem {{ $childOn ? 'is-open' : '' }}">
                            <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span class="shell-navitem-label">{{ $item['label'] }}</span>
                            <i class="fa-solid fa-chevron-down shell-navitem-caret" aria-hidden="true"></i>
                        </summary>
                        <div class="shell-subnav">
                            @foreach ($item['children'] as $child)
                                @php $curl = $railHref($child['route']); @endphp
                                <a href="{{ $curl ?? '#' }}"
                                   class="shell-subitem {{ $railIsOn($child['route']) ? 'is-active' : '' }} {{ $curl ? '' : 'is-disabled' }}"
                                   @unless($curl) tabindex="-1" aria-disabled="true" title="Coming soon" @endunless
                                   @if($railIsOn($child['route'])) aria-current="page" @endif>
                                    {{ $child['label'] }}
                                    @unless($curl)<span class="shell-soon">Soon</span>@endunless
                                </a>
                            @endforeach
                        </div>
                    </details>
                @else
                    @php $url = $railHref($item['route']); @endphp
                    <a href="{{ $url ?? '#' }}"
                       class="shell-navitem {{ $railIsOn($item['route']) ? 'is-active' : '' }} {{ $url ? '' : 'is-disabled' }}"
                       @unless($url) tabindex="-1" aria-disabled="true" title="Coming soon" @endunless
                       @if($railIsOn($item['route'])) aria-current="page" @endif>
                        <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span class="shell-navitem-label">{{ $item['label'] }}</span>
                        @if(! $url)
                            <span class="shell-soon">Soon</span>
                        @elseif(isset($item['count']))
                            <span class="shell-navitem-count">{{ $item['count'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        @endforeach
    </nav>

    <a href="{{ route('reports.index') }}" class="shell-help">
        <span class="shell-help-icon"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></span>
        <span>
            <span class="shell-help-title">Need help?</span>
            <span class="shell-help-sub">Visit our help center</span>
        </span>
    </a>
</aside>

<!-- SIDEBAR (≤768px drawer; the shell rail + contextual sidebar replace it above) -->
<aside class="sidebar" id="sidebar">
    <a href="{{ url('/') }}" class="sidebar-logo logo-desktop">
        <div class="sidebar-logo-icon">
            @if($branding->logoUrl())
                <img src="{{ $branding->logoUrl() }}" alt="{{ $branding->displaySiteName() }}">
            @else
                <i class="fa-solid fa-building-columns"></i>
            @endif
        </div>
        <div class="sidebar-logo-text">
            <strong>{{ $branding->displaySiteName() }}</strong>
            <span>{{ $branding->tagline ?: 'Management Suite' }}</span>
        </div>
    </a>
    <a href="{{ url('/') }}" class="logo-mobile" style="text-decoration:none;">
        <div class="logo-mobile-tile">
            @if($branding->logoUrl())
                <img src="{{ $branding->logoUrl() }}" alt="{{ $branding->displaySiteName() }}">
            @else
                {{ $branding->initials() }}
            @endif
        </div>
        <div class="logo-mobile-text">
            <strong>{{ $branding->displaySiteName() }}</strong>
            <span>{{ Str::upper($branding->tagline ?: 'Management Suite') }}</span>
        </div>
    </a>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Main</div>
        <a href="{{ url('/dashboard') }}" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-gauge-high nav-icon"></i> Dashboard
        </a>
    </div>

    @if(auth()->user()?->canAccessPortfolio())
    <div class="sidebar-section">
        <div class="sidebar-section-label">Property Management</div>
        <a href="{{ route('buildings.index') }}" class="nav-item {{ request()->is('buildings*') && !request()->is('floors') ? 'active' : '' }}">
            <i class="fa-solid fa-building nav-icon"></i> Buildings
        </a>
        <a href="{{ route('floors.global') }}" class="nav-item {{ request()->is('floors') ? 'active' : '' }}">
            <i class="fa-solid fa-layer-group nav-icon"></i> Floors
        </a>
        <a href="{{ route('property-units.index') }}" class="nav-item {{ request()->is('property-units*') ? 'active' : '' }}">
            <i class="fa-solid fa-door-open nav-icon"></i> Units
        </a>
    </div>
    @endif

    @if(auth()->user()?->canAccessPortfolio() || auth()->user()?->canAccessMaintenance())
    <div class="sidebar-section">
        <div class="sidebar-section-label">Management</div>
        @if(auth()->user()?->canAccessPortfolio())
        <a href="{{ route('tenants.index') }}" class="nav-item {{ request()->is('tenants*') ? 'active' : '' }}">
            <i class="fa-solid fa-users nav-icon"></i> Tenants
        </a>
        <a href="{{ route('lease-contracts.index') }}" class="nav-item {{ request()->is('lease-contracts*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-contract nav-icon"></i> Lease Contracts
        </a>
        @endif
        @if(auth()->user()?->canAccessMaintenance())
        <a href="{{ route('maintenance.index') }}" class="nav-item {{ request()->is('maintenance*') ? 'active' : '' }}">
            <i class="fa-solid fa-wrench nav-icon"></i> Maintenance
        </a>
        @endif
    </div>
    @endif

    @if(auth()->user()?->canAccessAccounting())
    <div class="sidebar-section">
        <div class="sidebar-section-label">Accounting</div>
        <a href="{{ route('invoices.index') }}" class="nav-item {{ request()->is('invoices*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-invoice-dollar nav-icon"></i> Invoices
        </a>
        <a href="{{ route('payments.index') }}" class="nav-item {{ request()->is('payments*') ? 'active' : '' }}">
            <i class="fa-solid fa-money-bill-transfer nav-icon"></i> Payments
        </a>
        <a href="{{ route('ewa-bills.index') }}" class="nav-item {{ request()->is('ewa-bills*') ? 'active' : '' }}">
            <i class="fa-solid fa-droplet nav-icon"></i> EWA Bills
        </a>
        <a href="{{ route('expenses.index') }}" class="nav-item {{ request()->is('expenses*') ? 'active' : '' }}">
            <i class="fa-solid fa-receipt nav-icon"></i> Expenses
        </a>
        <a href="{{ route('revenues.index') }}" class="nav-item {{ request()->is('revenues*') ? 'active' : '' }}">
            <i class="fa-solid fa-sack-dollar nav-icon"></i> Revenue
        </a>
    </div>
    @endif

    @if(auth()->user()?->canViewReports())
    <div class="sidebar-section">
        <div class="sidebar-section-label">Analytics</div>
        <a href="{{ route('reports.index') }}" class="nav-item {{ request()->is('reports*') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-bar nav-icon"></i> Reports
        </a>
    </div>
    @endif

    @if(auth()->user()?->canOpenConfiguration())
    <div class="sidebar-section">
        <div class="sidebar-section-label">Form / Template Management</div>
        <a href="{{ route('form-configs.index') }}?tab=forms"
           class="nav-item {{ request()->is('form-configs*') && request('tab', 'forms') === 'forms' ? 'active' : '' }}">
            <i class="fa-solid fa-wpforms nav-icon"></i> Forms Management
        </a>
        <a href="{{ route('form-configs.index') }}?tab=templates"
           class="nav-item {{ request()->is('form-configs*') && request('tab') === 'templates' ? 'active' : '' }}">
            <i class="fa-solid fa-layer-group nav-icon"></i> Template Management
        </a>
    </div>
    @endif

    @if(auth()->user()?->isAdmin())
    <div class="sidebar-section">
        <div class="sidebar-section-label">Admin</div>
        <a href="{{ route('users.index') }}" class="nav-item {{ request()->is('users*') ? 'active' : '' }}">
            <i class="fa-solid fa-user-shield nav-icon"></i> Users
        </a>
        <a href="{{ route('admin.audit-log') }}" class="nav-item {{ request()->is('admin/audit-log*') ? 'active' : '' }}">
            <i class="fa-solid fa-clock-rotate-left nav-icon"></i> Audit Log
        </a>
        <a href="{{ route('admin.error-log') }}" class="nav-item {{ request()->is('admin/error-log*') ? 'active' : '' }}">
            <i class="fa-solid fa-triangle-exclamation nav-icon"></i> Error Log
        </a>
        <a href="{{ route('settings.branding.edit') }}" class="nav-item {{ request()->is('settings/branding*') ? 'active' : '' }}">
            <i class="fa-solid fa-palette nav-icon"></i> Branding
        </a>
        <a href="{{ route('settings.azure-mail.edit') }}" class="nav-item {{ request()->is('settings/azure-mail*') ? 'active' : '' }}">
            <i class="fa-solid fa-envelope nav-icon"></i> Mail Settings
        </a>
    </div>
    @endif

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <x-avatar class="user-avatar" tag="div" />
            <div class="user-info">
                <strong>{{ auth()->user()->name ?? 'Unknown' }}</strong>
                <span>{{ auth()->user()->role_label ?? '' }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin-left:auto" data-signout-form>
                @csrf
                <button type="submit" class="topbar-icon-btn" style="width:28px;height:28px;font-size:12px"
                        title="Sign out" aria-label="Sign out of {{ auth()->user()->email ?? 'this account' }}">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- MORE SHEET (mobile bottom tab bar "More" destination) -->
<div class="modal-overlay" id="moreSheet">
    <div class="modal-box" style="max-width:100%;padding:14px 10px 20px;">
        @if(auth()->user()?->canAccessPortfolio())
        <a href="{{ route('floors.global') }}" class="more-sheet-item">
            <div class="more-sheet-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></div>
            <div><div class="more-sheet-label">Floors</div><div class="more-sheet-desc">Browse all floors</div></div>
        </a>
        <a href="{{ route('property-units.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon"><i class="fa-solid fa-door-open" aria-hidden="true"></i></div>
            <div><div class="more-sheet-label">Property Units</div><div class="more-sheet-desc">Browse property units</div></div>
        </a>
        <a href="{{ route('lease-contracts.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon"><i class="fa-solid fa-file-contract" aria-hidden="true"></i></div>
            <div><div class="more-sheet-label">Lease Contracts</div><div class="more-sheet-desc">Browse lease agreements</div></div>
        </a>
        @endif
        @if(auth()->user()?->canAccessAccounting())
        <a href="{{ route('invoices.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i></div>
            <div><div class="more-sheet-label">Invoices</div><div class="more-sheet-desc">View and manage invoices</div></div>
        </a>
        <a href="{{ route('payments.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon"><i class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i></div>
            <div><div class="more-sheet-label">Payments</div><div class="more-sheet-desc">Track received payments</div></div>
        </a>
        @endif
        @if(auth()->user()?->canViewReports())
        <a href="{{ route('reports.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon"><i class="fa-solid fa-chart-bar" aria-hidden="true"></i></div>
            <div><div class="more-sheet-label">Reports</div><div class="more-sheet-desc">Export portfolio reports</div></div>
        </a>
        @endif
        {{-- Help used to be a "?" popover in the top bar. The header now has
             three fixed slots and no room for a fourth, and this sheet is
             where the phone keeps everything that isn't a tab. --}}
        <button type="button" class="more-sheet-item" id="moreHelpBtn"
                aria-haspopup="dialog" aria-controls="helpSheet" aria-expanded="false">
            <div class="more-sheet-icon"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></div>
            <div><div class="more-sheet-label">Help</div><div class="more-sheet-desc">How this app works</div></div>
        </button>
        {{-- Theme lives here now. It was a third 44px disc in the Home
             header, beside two controls you reach for constantly, and it
             pushed the title into an ellipsis at 320px. Same .theme-toggle-btn
             class, so the one handler in this layout still drives it. --}}
        <button type="button" class="more-sheet-item theme-toggle-btn" title="Switch theme" aria-label="Switch to dark mode">
            <div class="more-sheet-icon"><i class="fa-solid fa-moon" aria-hidden="true"></i></div>
            <div><div class="more-sheet-label">Theme</div><div class="more-sheet-desc">Switch between light and dark</div></div>
        </button>
        <button type="button" class="more-sheet-item" id="moreMenuBtn">
            <div class="more-sheet-icon"><i class="fa-solid fa-bars" aria-hidden="true"></i></div>
            <div><div class="more-sheet-label">Full menu</div><div class="more-sheet-desc">All sections</div></div>
        </button>
        {{-- Asks first. A tap here used to end the session outright, and this
             row sits directly under "Full menu" in a sheet reached by the tab
             bar — the easiest thing on the phone to hit by mistake. It now
             hands off to #signOutDialog; the form stays a real form so the
             row still works with JS off. --}}
        <form method="POST" action="{{ route('logout') }}" data-signout-form>
            @csrf
            <button type="submit" class="more-sheet-item danger">
                <div class="more-sheet-icon is-danger"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></div>
                <div><div class="more-sheet-label">Sign out</div><div class="more-sheet-desc">{{ auth()->user()->email ?? '' }}</div></div>
            </button>
        </form>
    </div>
</div>

{{-- ═══════════════════════════ HELP SHEET ═══════════════════════════
     Where the top bar's "?" popover went. Same rows as
     partials/help-panel (which the desktop shell still uses), redrawn in
     the sheet language the phone already speaks — a .modal-overlay
     becomes a bottom sheet under 768px. No keyboard block: there is no
     keyboard here, and the command palette isn't rendered below 769px. --}}
<div class="modal-overlay" id="helpSheet" role="dialog" aria-modal="true"
     aria-labelledby="helpSheetTitle">
    <div class="modal-box" style="--modal-w:440px;">
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></div>
                <div class="modal-header-text">
                    <div class="modal-header-title" id="helpSheetTitle">Help</div>
                    <div class="modal-header-sub">How this app works</div>
                </div>
                <button type="button" class="modal-close-btn" data-help-close aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="modal-body">
            <p class="shell-helptip">
                <i class="fa-regular fa-lightbulb" aria-hidden="true"></i>
                <span>Tap any row in a list to open that record — the edit and delete buttons still work on their own.</span>
            </p>

            <a href="{{ route('dashboard') }}" class="more-sheet-item">
                <div class="more-sheet-icon"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i></div>
                <div><div class="more-sheet-label">Start from the dashboard</div><div class="more-sheet-desc">Today, cash flow and the portfolio</div></div>
            </a>

            @if(auth()->user()?->canViewReports())
                <a href="{{ route('reports.index') }}" class="more-sheet-item">
                    <div class="more-sheet-icon"><i class="fa-regular fa-file-lines" aria-hidden="true"></i></div>
                    <div><div class="more-sheet-label">Reports &amp; statements</div><div class="more-sheet-desc">Export what you need to send</div></div>
                </a>
            @endif

            @if(auth()->user()?->isAdmin())
                <a href="{{ route('roles.index') }}" class="more-sheet-item">
                    <div class="more-sheet-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></div>
                    <div><div class="more-sheet-label">Who can see what</div><div class="more-sheet-desc">Roles and permissions</div></div>
                </a>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════ SIGN-OUT CONFIRMATION ═══════════════════
     Replaces window.confirm() on every sign-out path. The native dialog was
     the wrong layout for this question on a phone: it docks to the TOP of the
     screen, a full hand's reach from the avatar the thumb just tapped; it
     prefixes the question with the bare host ("192.168.0.50 says"); it can't
     show which account is leaving, only spell the address into a sentence;
     and it offers OK / Cancel, neither of which names the outcome.

     This is the app's own dialog, so it inherits the theme, centres on
     desktop, and becomes a bottom sheet under 768px via app-mobile.css —
     the same conversion every other modal in the app gets. --}}
<div class="modal-overlay" id="signOutDialog" role="dialog" aria-modal="true"
     aria-labelledby="signOutTitle" aria-describedby="signOutIdentity">
    <div class="modal-box" style="--modal-w:420px;">
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon" style="background:var(--tone-danger-bg);color:var(--tone-danger-fg);border-color:var(--tone-danger-border)">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                </div>
                <div class="modal-header-text">
                    <div class="modal-header-title" id="signOutTitle">Sign out?</div>
                    <div class="modal-header-sub">You'll need your password to get back in.</div>
                </div>
                <button type="button" class="modal-close-btn" data-signout-cancel aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        {{-- The account, shown rather than described. On a shared phone the
             mistake worth preventing is signing the wrong person out. --}}
        <div class="modal-body">
            <div class="signout-identity" id="signOutIdentity">
                <x-avatar class="signout-avatar" />
                <span class="signout-identity-text">
                    <strong>{{ auth()->user()->name ?? 'Guest' }}</strong>
                    <span>{{ auth()->user()->email ?? '' }}</span>
                </span>
            </div>
        </div>

        {{-- Buttons say what they do. Under 768px they stack full-width and
             reverse, putting "Stay signed in" nearest the thumb and making
             the destructive one the deliberate reach. --}}
        <div class="modal-footer signout-actions">
            <button type="button" class="btn btn-outline" data-signout-cancel>Stay signed in</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Sign out
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════ MOBILE NOTIFICATIONS (the topbar bell) ═══════════════
     The phone's counterpart to the shell bell, and the bell for every route
     that draws the ≤768px .topbar — the dashboard and the pushed detail
     screens hide that bar and carry their own. Same AttentionFeed behind it,
     and the same rule: every row deep-links into the already-filtered list it
     describes, so this never becomes a fifth place where invoices live.

     A .modal-overlay rather than a .shell-pop dropdown, because app-mobile.css
     converts one into a bottom sheet under 768px — which also hands it the
     drag-to-dismiss handle and the safe-area padding every other sheet in the
     app already has. --}}
<div class="modal-overlay" id="topbarAlertsSheet" role="dialog" aria-modal="true"
     aria-labelledby="topbarAlertsTitle">
    <div class="modal-box" style="--modal-w:440px;">
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon"><i class="fa-regular fa-bell" aria-hidden="true"></i></div>
                <div class="modal-header-text">
                    <div class="modal-header-title" id="topbarAlertsTitle">Needs attention</div>
                    <div class="modal-header-sub">
                        @if($attentionCount)
                            {{ $attentionCount }} {{ \Illuminate\Support\Str::plural('thing', $attentionCount) }} {{ $attentionCount === 1 ? 'needs' : 'need' }} you today
                        @else
                            {{ now()->format('l, j F') }}
                        @endif
                    </div>
                </div>
                <button type="button" class="modal-close-btn" data-sheet-close aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="modal-body">
            @if($attentionCount)
                <div class="pm-action-list">
                    @foreach($attentionItems as $item)
                        <a href="{{ $item['url'] }}" class="pm-action-row">
                            <div class="pm-action-icon" style="background:var(--tone-{{ $item['tone'] }}-bg);color:var(--tone-{{ $item['tone'] }}-fg);">
                                <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="pm-action-title">{{ $item['title'] }}</div>
                                <div class="pm-action-sub">{{ $item['sub'] }}</div>
                            </div>
                            <i class="fa-solid fa-chevron-right pm-action-chevron" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            @else
                {{-- An empty bell still has to say something. Naming what it
                     checked is what makes "nothing" trustworthy. --}}
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-regular fa-circle-check" aria-hidden="true"></i></div>
                    <h4>You're all clear</h4>
                    <p>No overdue rent, no open requests, and no leases ending in the next 30 days.</p>
                </div>
            @endif
        </div>

        <div class="modal-footer alerts-footer">
            <a class="btn btn-outline" href="{{ route('dashboard') }}">Open the dashboard</a>
        </div>
    </div>
</div>

<div class="shell-frame">

    {{-- 60px top bar. Search leads the bar rather than sitting in the icon
         cluster: it is the bar's primary affordance, and the account block is
         the only thing that belongs on the trailing edge. --}}
    <div class="shell-topbar">
        <button class="shell-hamburger" type="button" data-sidebar-toggle
                title="Hide navigation" aria-label="Hide navigation" aria-expanded="true">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>

        <button type="button" class="shell-search" data-palette-open aria-label="Search anything — Command K">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <span class="shell-search-label">Search buildings, tenants, units…</span>
            <kbd class="shell-kbd">⌘K</kbd>
        </button>

        <div class="shell-topbar-spacer"></div>

        <button type="button" class="shell-iconbtn theme-toggle-btn" title="Switch theme" aria-label="Switch to dark mode">
            <i class="fa-solid fa-moon" aria-hidden="true"></i>
        </button>

        {{-- Bell + panel. Items come from App\Services\AttentionFeed via a view
             composer, and every one deep-links into the already-filtered list
             it describes. --}}
        <div class="shell-bell" data-pop>
            <button type="button" class="shell-iconbtn" data-pop-toggle
                    aria-expanded="false" aria-controls="shell-notif"
                    title="Notifications"
                    aria-label="Notifications{{ $attentionCount ? ' — '.$attentionCount.' need attention' : '' }}">
                <i class="fa-regular fa-bell" aria-hidden="true"></i>
                @include('partials.bell-badge', ['count' => $attentionCount])
            </button>

            <div class="shell-pop shell-notif" id="shell-notif" hidden role="dialog" aria-label="Needs attention">
                <div class="shell-pop-head">
                    <span>Needs attention</span>
                    @if($attentionCount)<span class="shell-pop-count">{{ $attentionCount }}</span>@endif
                </div>

                @forelse($attentionItems as $item)
                    <a class="shell-notif-item" href="{{ $item['url'] }}">
                        <span class="shell-notif-icon is-{{ $item['tone'] }}"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                        <span class="shell-notif-text">
                            <span class="shell-notif-title">{{ $item['title'] }}</span>
                            <span class="shell-notif-sub">{{ $item['sub'] }}</span>
                        </span>
                        <i class="fa-solid fa-chevron-right shell-notif-chev" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="shell-notif-empty">
                        <i class="fa-regular fa-circle-check"></i>
                        Nothing needs your attention.
                    </div>
                @endforelse

                <a class="shell-notif-foot" href="{{ route('dashboard') }}">Open the dashboard</a>
            </div>
        </div>

        {{-- Help. It was a button with nothing behind it; it opens the shortcuts
             panel now, reusing the same [data-pop] machinery as the bell so
             click-away, Esc and focus return come for free. --}}
        <div class="shell-helpbtn" data-pop>
            <button type="button" class="shell-iconbtn" data-pop-toggle
                    aria-expanded="false" aria-controls="shell-help"
                    aria-haspopup="true" title="Help" aria-label="Help">
                <i class="fa-regular fa-circle-question" aria-hidden="true"></i>
            </button>

            @include('partials.help-panel', ['id' => 'shell-help', 'shortcuts' => true])
        </div>

        <span class="shell-divider" aria-hidden="true"></span>

        {{-- The chevron said "menu" and the click said "goodbye": the whole chip
             was a submit button for the logout form, so one stray click ended
             the session with nothing asked. It opens a menu now, and signing
             out is a deliberate second click inside it. --}}
        <div class="shell-account" data-pop>
            <button type="button" class="shell-user" data-pop-toggle
                    aria-expanded="false" aria-controls="shell-account-menu"
                    aria-haspopup="true" title="Account">
                <x-avatar class="shell-avatar" />
                <span>
                    <span class="shell-user-name">{{ auth()->user()->name ?? 'Guest' }}</span>
                    <span class="shell-user-role">{{ auth()->user()->role_label ?? '' }}</span>
                </span>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>

            <div class="shell-pop shell-account-menu" id="shell-account-menu" hidden>
                <div class="shell-pop-identity">
                    <strong>{{ auth()->user()->name ?? 'Guest' }}</strong>
                    <span>{{ auth()->user()->email ?? '' }}</span>
                </div>
                <a class="shell-menu-item" href="{{ route('profile.edit') }}">
                    <i class="fa-regular fa-id-card" aria-hidden="true"></i>
                    Your profile
                </a>
                <form method="POST" action="{{ route('logout') }}" id="shellLogout">
                    @csrf
                    <button type="submit" class="shell-menu-item is-danger">
                        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>

        <!-- TOPBAR (≤768px only; the shell toolbar and page header replace it above) -->
        {{-- The compact header variant: one row, title left, the same three
             controls right. There is no hamburger — the bottom tab bar is the
             only navigation on a phone, and the drawer it used to open is
             reached from More → Full menu. The help "?" left for the same
             reason (More → Help): the header carries exactly two slots, in
             one order, on every screen — notifications · avatar — so
             switching tabs never moves them.

             `topbar-count` is the optional number beside the title ("Buildings
             4"). A screen that doesn't set the section renders no span. --}}
        <header class="topbar">
            <div class="topbar-title">
                <span class="topbar-title-text">@yield('topbar-title', 'Dashboard')</span>
                @hasSection('topbar-count')
                    <span class="topbar-count">@yield('topbar-count')</span>
                @endif
            </div>
            <div class="topbar-actions">
                {{-- Two controls, and the same two on every screen: bell then
                     avatar. Theme moved to More — see the note on the Home
                     header, which lost the same third disc. --}}
                {{-- The bell was a bare <button> with nothing behind it: no
                     type (so it submitted any form it landed in), no label for
                     a screen reader, no count, and no handler. It is the same
                     control as the shell bell now, reading the same
                     AttentionFeed — it just answers with #topbarAlertsSheet,
                     because under 768px app-mobile.css turns a .modal-overlay
                     into a bottom sheet, which is the phone's answer to a
                     dropdown. --}}
                <button type="button" class="pm-icon-btn" id="topbarAlertsBtn"
                        data-sheet-open="topbarAlertsSheet"
                        aria-haspopup="dialog" aria-controls="topbarAlertsSheet"
                        aria-expanded="false" title="Notifications"
                        aria-label="Notifications{{ $attentionCount ? ' — '.$attentionCount.' need attention' : ' — nothing needs your attention' }}">
                    <i class="fa-regular fa-bell" aria-hidden="true"></i>
                    @include('partials.bell-badge', ['count' => $attentionCount])
                </button>
                {{-- Was an inert <div> with cursor:pointer on it — it looked
                     tappable and did nothing. It is the dashboard header's
                     control now, down to the confirmation sheet, so the third
                     slot means the same thing on every screen. --}}
                <form method="POST" action="{{ route('logout') }}" data-signout-form>
                    @csrf
                    <x-avatar class="pm-avatar" tag="button" type="submit" title="Sign out"
                              aria-label="Sign out of {{ auth()->user()->email ?? 'this account' }}" />
                </form>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="page-content shell-content">
@hasSection('page-title')
            {{-- Page header — §2: inside the content column, not a fixed bar.
                 Rendered only for pages that have moved onto it. A page still
                 drawing its own .page-header would otherwise show two titles,
                 and its primary action lives in that block, so migrating a
                 page means defining these three sections and deleting its
                 .page-header.

                 Four slots, in DOM order back · text · actions · overflow:

                   page-back      the way up. First in the DOM so it is the
                                  first thing after the landmark for a screen
                                  reader and the first tab stop; CSS `order`
                                  puts it back at the head of the action group
                                  on desktop, and under 600px app-core lifts
                                  it beside the title as a 44px icon button.
                                  Every detail page defines this rather than
                                  opening its action row with its own Back —
                                  that is what makes the phone header the same
                                  object on all of them.
                   page-actions   the page's own actions.
                   page-overflow  a phone-only trigger (a ⋯ opening a sheet)
                                  for actions that do not fit that header.

                 The has-* classes tell the CSS which slots are filled, so the
                 phone grid can drop a row or a column instead of leaving a
                 gap where an empty one would be. --}}
            @php
                /* Built in PHP rather than as three inline @hasSection blocks:
                   Blade leaves a directive uncompiled when it sits flush
                   against the @endif before it, which put a raw
                   `@hasSection(...)` into the compiled class attribute and
                   made the whole layout a parse error. */
                $headSlots = collect([
                    'page-back'     => 'has-back',
                    'page-actions'  => 'has-actions',
                    'page-overflow' => 'has-overflow',
                ])->filter(fn ($class, $section) => trim($__env->yieldContent($section)) !== '')
                  ->values()->implode(' ');
            @endphp
            <header class="shell-pagehead {{ $headSlots }}">
@hasSection('page-back')
                <div class="shell-pagehead-back">@yield('page-back')</div>
@endif
                <div class="shell-pagehead-text">
@hasSection('page-breadcrumb')
                    <nav class="breadcrumb" aria-label="Breadcrumb">@yield('page-breadcrumb')</nav>
@endif
                    <h1 class="shell-pagehead-title">@yield('page-title')</h1>
                    <div class="shell-pagehead-sub">@yield('page-subtitle')</div>
                </div>
                <div class="shell-pagehead-actions">@yield('page-actions')</div>
@hasSection('page-overflow')
                <div class="shell-pagehead-more">@yield('page-overflow')</div>
@endif
            </header>
@endif

            @if(session('success'))

                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

<!-- BOTTOM TAB BAR -->
@php
    $tabbarIsMain = request()->is('dashboard') || request()->is('/');
    $tabbarIsBuildings = request()->is('buildings*') && !request()->is('floors');
    $tabbarIsTenants = request()->is('tenants*');
    $tabbarIsMaintenance = request()->is('maintenance*');
    $tabbarIsInvoices = request()->is('invoices*');
    $tabbarIsPayments = request()->is('payments*');
    $tabbarUser = auth()->user();
    /* A role without the portfolio gets its own two tabs rather than an empty
       bar: Accountant lands on Invoices and Payments, which is its work. */
    $tabbarPortfolio  = (bool) $tabbarUser?->canAccessPortfolio();
    $tabbarAccounting = ! $tabbarPortfolio && (bool) $tabbarUser?->canAccessAccounting();
    $tabbarMaintenance = (bool) $tabbarUser?->canAccessMaintenance();
    $tabbarIsMore = !$tabbarIsMain
        && !($tabbarPortfolio && ($tabbarIsBuildings || $tabbarIsTenants))
        && !($tabbarAccounting && ($tabbarIsInvoices || $tabbarIsPayments))
        && !($tabbarMaintenance && $tabbarIsMaintenance);
@endphp
{{-- The label is wrapped, not dropped, on inactive tabs: only the active tab
     shows it, and the inactive ones clip theirs to zero width (see
     .tabbar-label) rather than removing it, so the tab keeps its accessible
     name and the expand has something to animate. --}}
{{-- The fixed row that holds the tab pill and, when a screen asks for one,
     the FAB. Both are static children of it, so the FAB sits beside the pill
     instead of over it, and no screen has to position either. --}}
<div class="ps-bottom-bar">
<nav class="bottom-tabbar" id="bottomTabbar">
    <a href="{{ url('/dashboard') }}" class="tabbar-item {{ $tabbarIsMain ? 'active' : '' }}">
        <i class="fa-solid fa-house"></i><span class="tabbar-label">Home</span>
    </a>
    @if($tabbarPortfolio)
    <a href="{{ route('buildings.index') }}" class="tabbar-item {{ $tabbarIsBuildings ? 'active' : '' }}">
        <i class="fa-solid fa-building"></i><span class="tabbar-label">Properties</span>
    </a>
    <a href="{{ route('tenants.index') }}" class="tabbar-item {{ $tabbarIsTenants ? 'active' : '' }}">
        <i class="fa-solid fa-users"></i><span class="tabbar-label">Tenants</span>
    </a>
    @elseif($tabbarAccounting)
    <a href="{{ route('invoices.index') }}" class="tabbar-item {{ $tabbarIsInvoices ? 'active' : '' }}">
        <i class="fa-solid fa-file-invoice-dollar"></i><span class="tabbar-label">Invoices</span>
    </a>
    <a href="{{ route('payments.index') }}" class="tabbar-item {{ $tabbarIsPayments ? 'active' : '' }}">
        <i class="fa-solid fa-money-bill-transfer"></i><span class="tabbar-label">Payments</span>
    </a>
    @endif
    @if($tabbarMaintenance)
    <a href="{{ route('maintenance.index') }}" class="tabbar-item {{ $tabbarIsMaintenance ? 'active' : '' }}">
        <i class="fa-solid fa-screwdriver-wrench"></i><span class="tabbar-label">Requests</span>
    </a>
    @endif
    <button type="button" class="tabbar-item {{ $tabbarIsMore ? 'active' : '' }}" id="moreTabBtn">
        <i class="fa-solid fa-ellipsis"></i><span class="tabbar-label">More</span>
    </button>
</nav>
{{-- The FAB, rendered here or not at all. A screen opts in by defining
     `mobile-fab`, and a screen whose own actions row already carries a
     primary create button must not — two "+" buttons on one screen is the
     duplicate-add-button the sweep is meant to end. --}}
@hasSection('mobile-fab')
    @yield('mobile-fab')
@endif
</div>

<script>
let mDebounceTimer;
function mDebounceSubmit(el) {
    clearTimeout(mDebounceTimer);
    mDebounceTimer = setTimeout(() => el.form.submit(), 500);
}
/* A chip row scrolls, and the chip that is doing the filtering is not always
   in the first screenful of it — Buildings carries ten, Maintenance seven. A
   filtered list whose row reads "All" because the active chip is 300px off
   the right edge is a screen lying about its own state, so bring it into
   view on arrival. Instant, not smooth: this is the page's starting
   position, not a movement the reader should watch. */
document.querySelectorAll('.m-chip-row').forEach(function (row) {
    const on = row.querySelector('.m-chip.active');
    if (! on || row.scrollWidth <= row.clientWidth) return;
    row.scrollLeft = Math.max(0, on.offsetLeft - (row.clientWidth - on.offsetWidth) / 2);
});
(function () {
    const MOBILE = 768;
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const menuBtn = document.getElementById('menuBtn');
    const isMobile = () => window.innerWidth <= MOBILE;

    // ── Side drawer ──────────────────────────────────────────────
    function openDrawer() {
        sidebar.classList.add('open');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
    /* Optional since the header hamburger was removed: the drawer is opened
       from More → Full menu now. The lookup stays so a future header that
       wants one keeps working, and so this IIFE doesn't die on a null. */
    menuBtn?.addEventListener('click', () => {
        sidebar.classList.contains('open') ? closeDrawer() : openDrawer();
    });
    backdrop.addEventListener('click', closeDrawer);

    /* ── Compact header hairline (every tab except Home) ──────────
       Home's header lives inside .m-dash and watches #pmDashScroll; this one
       is sticky in normal flow, so the window is its scroller. Same helper,
       so both variants gain and lose the hairline at the same moment. The
       threshold is only for `.is-collapsed`, which this header doesn't
       style — it is not .is-collapsible. */
    /* All three header variants — compact, pushed-detail and Home — take the
       hairline from the same helper, so it appears at the same moment on
       each. (Home's lives inside .m-dash and is wired in dashboard.blade.php,
       which owns its scroller.) */
    ['header.topbar', '.pm-push-header'].forEach(function (sel) {
        const header = document.querySelector(sel);
        if (header && window.pmInitCollapsingHeader) {
            window.pmInitCollapsingHeader(header, window, 24);
        }
    });

    /* The two polish helpers every screen gets: figures count up, and the
       bell's count pops on the load where it went up. */
    if (window.psInitCountUp) window.psInitCountUp(document);
    if (window.psInitBellPop) window.psInitBellPop();

    /* ── Sheets, generically ──────────────────────────────────────
       Any .modal-overlay opens from `[data-sheet-open="<its id>"]` and
       closes from `[data-sheet-close]`, click-away or Escape. Four
       screens had a hand-written copy of exactly this — open, aria-expanded,
       scroll lock, focus the first row, hand focus back on close — and the
       copies had drifted. The scroll-lock release checks for another open
       overlay or drawer so closing one sheet cannot unlock the page under
       a second. */
    let psSheet = null;        /* the sheet this handler opened, if any */
    let psSheetOpener = null;  /* the control that opened it, for focus return */
    function psOpenSheet(sheet, opener) {
        if (!sheet) return;
        sheet.classList.add('open');
        psSheet = sheet;
        psSheetOpener = opener || null;
        opener?.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        sheet.querySelector('.more-sheet-item, .modal-close-btn')?.focus();
    }
    function psCloseSheet(sheet) {
        if (!sheet) return;
        sheet.classList.remove('open');
        psSheetOpener?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow =
            document.querySelector('.sidebar.open, .modal-overlay.open') ? 'hidden' : '';
        if (psSheetOpener && psSheetOpener.offsetParent !== null) psSheetOpener.focus();
        if (sheet === psSheet) { psSheet = null; psSheetOpener = null; }
    }
    document.addEventListener('click', function (e) {
        const opener = e.target.closest('[data-sheet-open]');
        if (opener) {
            psOpenSheet(document.getElementById(opener.dataset.sheetOpen), opener);
            return;
        }
        const closer = e.target.closest('[data-sheet-close]');
        if (closer) { psCloseSheet(closer.closest('.modal-overlay')); return; }
        /* Click-away, but only on a sheet this handler opened — the page's own
           modals keep their own scrim behaviour. */
        if (psSheet && e.target === psSheet) psCloseSheet(psSheet);
    });
    /* Rotating the phone past the breakpoint can take a sheet's trigger away
       with it — a sheet left open would then be an overlay with no way out. */
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768 && psSheet?.classList.contains('open')) psCloseSheet(psSheet);
    });

    /* Escape closes only what this handler opened, for the same reason.
       Tab is kept inside the open sheet: it is a modal dialog, and tabbing
       out of one into the page behind it leaves a screen-reader user
       reading a list they cannot see. */
    document.addEventListener('keydown', function (e) {
        if (! psSheet?.classList.contains('open')) return;
        if (e.key === 'Escape') { psCloseSheet(psSheet); return; }
        if (e.key !== 'Tab') return;

        const focusable = [...psSheet.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])'
        )].filter((el) => el.offsetParent !== null);
        if (! focusable.length) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault(); last.focus();
        } else if (! e.shiftKey && document.activeElement === last) {
            e.preventDefault(); first.focus();
        }
    });

    // ── More sheet (bottom tab bar "More" destination) ───────────
    const moreSheet = document.getElementById('moreSheet');
    function openMoreSheet() {
        moreSheet.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeMoreSheet() {
        moreSheet.classList.remove('open');
        document.body.style.overflow = '';
    }
    document.getElementById('moreTabBtn')?.addEventListener('click', openMoreSheet);
    moreSheet.addEventListener('click', (e) => { if (e.target === moreSheet) closeMoreSheet(); });
    document.getElementById('moreMenuBtn')?.addEventListener('click', () => { closeMoreSheet(); openDrawer(); });

    // ── Help sheet (More → Help; replaces the top bar's "?" popover) ──
    const helpSheet = document.getElementById('helpSheet');
    const helpBtn = document.getElementById('moreHelpBtn');
    function closeHelpSheet() {
        helpSheet.classList.remove('open');
        document.body.style.overflow = '';
        helpBtn?.setAttribute('aria-expanded', 'false');
    }
    helpBtn?.addEventListener('click', () => {
        closeMoreSheet();
        helpSheet.classList.add('open');
        document.body.style.overflow = 'hidden';
        helpBtn.setAttribute('aria-expanded', 'true');
    });
    helpSheet.addEventListener('click', (e) => {
        if (e.target === helpSheet || e.target.closest('[data-help-close]')) closeHelpSheet();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && helpSheet.classList.contains('open')) closeHelpSheet();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) closeDrawer();
    });
    // Auto-close after picking a destination, like a native app drawer
    sidebar.querySelectorAll('.nav-item').forEach((link) => {
        link.addEventListener('click', () => { if (isMobile()) closeDrawer(); });
    });

    // Swipe the drawer itself left to dismiss
    (function swipeToCloseDrawer() {
        let startX = null;
        sidebar.addEventListener('touchstart', (e) => {
            if (!isMobile() || !sidebar.classList.contains('open')) { startX = null; return; }
            startX = e.touches[0].clientX;
        }, { passive: true });
        sidebar.addEventListener('touchmove', (e) => {
            if (startX === null) return;
            const dx = e.touches[0].clientX - startX;
            if (dx < 0) sidebar.style.transform = `translateX(${dx}px)`;
        }, { passive: true });
        sidebar.addEventListener('touchend', (e) => {
            if (startX === null) return;
            const dx = e.changedTouches[0].clientX - startX;
            sidebar.style.transform = '';
            if (dx < -70) closeDrawer();
            startX = null;
        });
    })();

    // Swipe in from the left edge to open
    (function swipeToOpenDrawer() {
        let startX = null;
        document.addEventListener('touchstart', (e) => {
            if (!isMobile() || sidebar.classList.contains('open') || e.touches[0].clientX > 24) { startX = null; return; }
            startX = e.touches[0].clientX;
        }, { passive: true });
        document.addEventListener('touchend', (e) => {
            if (startX === null) return;
            const dx = e.changedTouches[0].clientX - startX;
            if (dx > 60) openDrawer();
            startX = null;
        });
    })();

    // ── Bottom sheets (any .modal-overlay / .modal-box form dialog) ──
    function attachSheetHandle(box) {
        if (box.querySelector('.sheet-handle')) return;
        const handle = document.createElement('div');
        handle.className = 'sheet-handle';
        box.prepend(handle);

        let startY = null, dragging = false;
        const overlay = box.closest('.modal-overlay');
        const threshold = 90;

        handle.addEventListener('touchstart', (e) => {
            if (!isMobile()) return;
            startY = e.touches[0].clientY;
            dragging = true;
            handle.classList.add('dragging');
            box.style.transition = 'none';
        }, { passive: true });

        handle.addEventListener('touchmove', (e) => {
            if (!dragging) return;
            const dy = Math.max(0, e.touches[0].clientY - startY);
            box.style.transform = `translateY(${dy}px)`;
            const progress = Math.min(1, dy / threshold);
            handle.style.background = `color-mix(in srgb, var(--accent) ${progress * 100}%, var(--input-border))`;
            handle.style.boxShadow = progress > 0.15 ? `0 0 ${8 * progress}px var(--accent-glow)` : 'none';
        }, { passive: true });

        handle.addEventListener('touchend', (e) => {
            if (!dragging) return;
            dragging = false;
            handle.classList.remove('dragging');
            box.style.transition = '';
            box.style.transform = '';
            handle.style.background = '';
            handle.style.boxShadow = '';
            const dy = e.changedTouches[0].clientY - startY;
            if (dy > threshold) {
                overlay.classList.remove('open');
                document.body.style.overflow = '';
            }
        });
    }

    document.querySelectorAll('.modal-overlay .modal-box').forEach(attachSheetHandle);
})();

/* ── Sign-out confirmation ────────────────────────────────
   Every sign-out control stays a real submit button inside a real POST
   form — that is the no-JS fallback and it must keep working. This
   intercepts the submit and asks in #signOutDialog instead, which the
   dialog's own form then completes. */
(function () {
    const dialog = document.getElementById('signOutDialog');
    const forms = document.querySelectorAll('form[data-signout-form]');
    if (!dialog || !forms.length) return;

    const FOCUSABLE = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
    let opener = null;

    function open(trigger) {
        opener = trigger || null;
        dialog.classList.add('open');
        document.body.style.overflow = 'hidden';
        // "Stay signed in" takes focus, not the destructive button: Enter on a
        // dialog you did not mean to open should be the harmless answer.
        dialog.querySelector('.btn-outline')?.focus();
    }

    function close() {
        dialog.classList.remove('open');

        // The nav drawer is deliberately left standing behind this dialog
        // (cancelling should put you back where you were), and it owns the
        // same scroll lock — so releasing it unconditionally would let the
        // page scroll behind an open drawer.
        const stillLocked = document.querySelector('.sidebar.open, .modal-overlay.open');
        document.body.style.overflow = stillLocked ? 'hidden' : '';

        // offsetParent guards against restoring focus into a sheet that was
        // closed on the way in (the More sheet row).
        if (opener && document.contains(opener) && opener.offsetParent !== null) opener.focus();
        opener = null;
    }

    forms.forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            // The More sheet sits on --z-sheet (1050), above --z-modal, so
            // asking from inside it would put the question behind the sheet
            // that asked — it has to close first. The nav drawer is below
            // --z-modal and stays open on purpose: cancelling there should
            // put you back in the drawer you were reading.
            const host = form.closest('.modal-overlay.open');
            if (host) {
                host.classList.remove('open');
                document.body.style.overflow = '';
            }

            open(e.submitter || form.querySelector('[type="submit"]'));
        });
    });

    dialog.querySelectorAll('[data-signout-cancel]').forEach((b) => b.addEventListener('click', close));
    dialog.addEventListener('click', (e) => { if (e.target === dialog) close(); });

    document.addEventListener('keydown', (e) => {
        if (!dialog.classList.contains('open')) return;
        if (e.key === 'Escape') { close(); return; }
        if (e.key !== 'Tab') return;

        // Keep Tab inside the dialog — it is modal, and the page behind it
        // still has a full tab order.
        const items = [...dialog.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);
        if (!items.length) return;
        const first = items[0], last = items[items.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
})();


/* ── File-input preview ───────────────────────────────────
   Any [type=file][data-preview] swaps the named box's contents for the chosen
   image before upload — the branding logo and favicon, and the profile photo.
   A round preview keeps its shape because .upload-preview.is-round owns that,
   not the markup this writes. */
(function () {
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var file = input.files[0];
            var box = document.getElementById(input.dataset.preview);
            if (!file || !box) return;

            var reader = new FileReader();
            reader.onload = function (e) {
                box.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                // Choosing a replacement and also ticking "remove" is a
                // contradiction; the file wins, so untick it.
                var remove = document.getElementById('remove_' + input.id);
                if (remove) remove.checked = false;
            };
            reader.readAsDataURL(file);
        });
    });
})();

/* ── Theme toggle ─────────────────────────────────────── */
(function () {
    const btns = document.querySelectorAll('.theme-toggle-btn');
    if (!btns.length) return;

    function syncIcons() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        btns.forEach(function (btn) {
            const icon = btn.querySelector('i');
            icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
            btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        });
    }
    syncIcons();

    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('p7-theme', next);
            syncIcons();
        });
    });
})();
</script>

@include('partials.command-palette')

<script>
/* ── Command palette (⌘K) and row density ─────────────────────
   Open/close and a client-side filter over the rows the partial
   rendered. Searching real records is later work (§6). */
(function () {
    var root = document.documentElement;

    /* The top bar hamburger collapses the sidebar. Persisted, and applied
       before paint by the head script so the frame does not jump. */
    if (document.documentElement.classList.contains('nav-collapsed-boot')) {
        document.body.classList.add('is-nav-collapsed');
    }
    var navBtn = document.querySelector('[data-sidebar-toggle]');
    if (navBtn) navBtn.addEventListener('click', function () {
        var hidden = document.body.classList.toggle('is-nav-collapsed');
        document.documentElement.classList.toggle('nav-collapsed-boot', hidden);
        navBtn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
        navBtn.setAttribute('aria-label', hidden ? 'Show navigation' : 'Hide navigation');
        navBtn.setAttribute('title', hidden ? 'Show navigation' : 'Hide navigation');
        try { localStorage.setItem('p7-nav', hidden ? 'collapsed' : 'open'); } catch (e) {}
    });

    /* Row action menus (app-core §4.1b). Delegated, because rows are paginated
       and re-rendered: binding per button would miss anything drawn later.

       The panel is positioned here rather than in CSS because .table-wrap
       scrolls horizontally — an absolutely positioned panel would be clipped
       by its own cell. Fixed coordinates escape that, at the cost of having to
       close on scroll and resize, which is what the listeners below do. */
    var openRowMenu = null;

    function closeRowMenu(refocus) {
        if (!openRowMenu) return;
        var btn = openRowMenu.btn, panel = openRowMenu.panel, home = openRowMenu.home;
        panel.setAttribute('hidden', '');
        panel.style.left = panel.style.top = '';
        /* Put it back beside its trigger so the DOM stays where the markup says
           it is, and a second open starts from a known place. */
        if (home && panel.parentNode !== home) home.appendChild(panel);
        btn.setAttribute('aria-expanded', 'false');
        openRowMenu = null;
        if (refocus && btn.focus) btn.focus();
    }

    function placeRowMenu(btn, panel) {
        panel.removeAttribute('hidden');
        var b = btn.getBoundingClientRect();
        var w = panel.offsetWidth, h = panel.offsetHeight, gap = 6;

        /* Right-aligned to the trigger, because the actions column sits at the
           trailing edge and a left-aligned panel would hang off the page. */
        var left = Math.max(8, Math.min(b.right - w, window.innerWidth - w - 8));
        /* Below by default, above when there is not room — a menu opening off
           the bottom of a long table is the common case. */
        var top = (b.bottom + gap + h <= window.innerHeight) ? b.bottom + gap : Math.max(8, b.top - gap - h);

        panel.style.left = Math.round(left) + 'px';
        panel.style.top = Math.round(top) + 'px';
    }

    /* CAPTURE phase, deliberately. The actions cell carries an inline
       onclick="event.stopPropagation()" — that is what stops a click on a
       button from navigating the row — and it runs on the way up, before any
       listener on document. A bubble-phase handler here never sees the click
       at all. Capturing runs before the cell, so both behaviours survive. */
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-rowmenu-toggle]');
        if (toggle) {
            /* The row itself navigates on click; the menu must not trigger it. */
            e.stopPropagation();
            e.preventDefault();
            var panel = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!panel) return;
            var wasOpen = openRowMenu && openRowMenu.panel === panel;
            closeRowMenu(false);
            if (wasOpen) return;
            toggle.setAttribute('aria-expanded', 'true');
            openRowMenu = { btn: toggle, panel: panel, home: panel.parentNode };

            /* Reparent to <body> before positioning, and not for tidiness:
               `position: fixed` resolves against the nearest ancestor that
               establishes a containing block, and ANY non-none transform does
               that. The card entrance animation (app-core §4.3, .card-reveal)
               ends on transform: translateY(0) — still a transform — so a panel
               left inside the card was being positioned relative to the card
               and landed off-screen. In <body> there is nothing in the way. */
            document.body.appendChild(panel);
            placeRowMenu(toggle, panel);
            return;
        }
        /* A click inside the panel is an action; anything else closes it. */
        if (openRowMenu && !openRowMenu.panel.contains(e.target)) closeRowMenu(false);
    }, true);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && openRowMenu) closeRowMenu(true);
    });

    /* Fixed coordinates go stale the moment anything moves, so follow the
       trigger rather than closing on sight.

       Closing was the first attempt and it was wrong: clicking a row that is
       only partly in view makes the browser scroll the button into view, which
       fired this handler and shut the menu in the same gesture that opened it.
       Only a trigger that has actually left the viewport closes now.

       Capture is on so this hears scrolling inside .table-wrap and
       .shell-content too — scroll events do not bubble. */
    var rowMenuFrame = null;
    function trackRowMenu() {
        if (!openRowMenu || rowMenuFrame) return;
        rowMenuFrame = requestAnimationFrame(function () {
            rowMenuFrame = null;
            if (!openRowMenu) return;
            var b = openRowMenu.btn.getBoundingClientRect();
            if (b.bottom < 0 || b.top > window.innerHeight) { closeRowMenu(false); return; }
            placeRowMenu(openRowMenu.btn, openRowMenu.panel);
        });
    }
    window.addEventListener('scroll', trackRowMenu, true);
    window.addEventListener('resize', trackRowMenu);

    /* Top-bar dropdowns — the bell and the account chip. One handler: opening
       either closes the other, click-away and Esc close, and focus returns to
       the trigger so the keyboard does not get stranded in a hidden panel. */
    var pops = Array.prototype.map.call(document.querySelectorAll('[data-pop]'), function (root) {
        return { root: root, btn: root.querySelector('[data-pop-toggle]'), panel: root.querySelector('.shell-pop') };
    }).filter(function (p) { return p.btn && p.panel; });

    function popIsOpen(p) { return !p.panel.hasAttribute('hidden'); }
    function popSet(p, open) {
        if (open) { p.panel.removeAttribute('hidden'); } else { p.panel.setAttribute('hidden', ''); }
        p.btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    function popCloseAll(except) {
        pops.forEach(function (p) { if (p !== except && popIsOpen(p)) popSet(p, false); });
    }

    pops.forEach(function (p) {
        p.btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var willOpen = !popIsOpen(p);
            popCloseAll(p);
            popSet(p, willOpen);
        });
    });
    if (pops.length) {
        document.addEventListener('click', function (e) {
            pops.forEach(function (p) {
                if (popIsOpen(p) && !p.root.contains(e.target)) popSet(p, false);
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            pops.forEach(function (p) {
                if (popIsOpen(p)) { popSet(p, false); p.btn.focus(); }
            });
        });
    }

    var palette = document.getElementById('command-palette');
    if (!palette) return;
    var input   = palette.querySelector('[data-palette-input]');
    var empty   = palette.querySelector('[data-palette-empty]');
    var rows    = palette.querySelectorAll('[data-palette-row]');
    var jump    = palette.querySelector('[data-palette-jump]');
    var results = palette.querySelector('[data-palette-results]');
    var hint    = palette.querySelector('[data-palette-hint]');
    var opener  = null;
    var MIN     = 2;              /* mirrors SearchController::MIN_QUERY */
    var timer   = null;
    var seq     = 0;              /* drops a slow reply that a newer one has overtaken */

    function filter(q) {
        q = (q || '').toLowerCase();
        var any = false;
        rows.forEach(function (row) {
            var hit = row.dataset.search.indexOf(q) !== -1;
            row.hidden = !hit;
            if (hit) any = true;
        });
        if (empty) empty.hidden = any;
    }

    function showJumpList(q) {
        if (results) { results.hidden = true; results.innerHTML = ''; }
        if (hint) hint.hidden = true;
        if (jump) jump.hidden = false;
        filter(q);
    }

    function render(groups) {
        if (!results) return;
        if (jump) jump.hidden = true;
        if (hint) hint.hidden = true;
        results.hidden = false;

        if (!groups.length) {
            results.innerHTML = '<div class="palette-empty">No records match.</div>';
            return;
        }

        var html = '';
        groups.forEach(function (g) {
            html += '<div class="palette-group">' + esc(g.label) + '</div>';
            g.items.forEach(function (it) {
                html += '<a href="' + esc(it.url) + '" class="palette-row" data-palette-hit>'
                     +  '<span class="palette-row-icon"><i class="fa-solid ' + esc(g.icon) + '"></i></span>'
                     +  '<span class="palette-row-text">'
                     +  '<span class="palette-row-title">' + esc(it.title) + '</span>'
                     +  '<span class="palette-row-sub">' + esc(it.sub) + '</span>'
                     +  '</span>'
                     +  '<span class="palette-row-kind">OPEN</span>'
                     +  '</a>';
            });
        });
        results.innerHTML = html;
    }

    /* Server text into markup — escape it, or a tenant named with an angle
       bracket becomes script. */
    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function search(q) {
        var mine = ++seq;
        if (hint) hint.hidden = false;

        fetch('{{ route('search') }}?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function (data) { if (mine === seq) render(data.groups || []); })
            .catch(function () {
                if (mine !== seq) return;
                /* Offer the jump list rather than a dead end. */
                showJumpList('');
                if (empty) { empty.hidden = false; empty.textContent = 'Search is unavailable right now.'; }
            });
    }

    function onQuery(q) {
        clearTimeout(timer);
        q = (q || '').trim();

        if (q.length < MIN) { showJumpList(q); return; }
        timer = setTimeout(function () { search(q); }, 200);
    }
    function open() {
        opener = document.activeElement;
        palette.removeAttribute('hidden');
        palette.classList.add('is-open');
        if (input) { input.value = ''; showJumpList(''); input.focus(); }
    }
    function close() {
        palette.classList.remove('is-open');
        palette.setAttribute('hidden', '');
        if (opener && opener.focus) opener.focus();
    }
    var isOpen = function () { return !palette.hasAttribute('hidden'); };

    document.querySelectorAll('[data-palette-open]').forEach(function (b) {
        b.addEventListener('click', open);
    });
    palette.addEventListener('click', function (e) { if (e.target === palette) close(); });
    if (input) input.addEventListener('input', function () { onQuery(input.value); });

    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && (e.key || '').toLowerCase() === 'k') {
            e.preventDefault();
            isOpen() ? close() : open();
            return;
        }
        if (e.key === 'Escape' && isOpen()) close();

        /* Focus trap while the palette is open — §8 allows one only here. */
        if (e.key === 'Tab' && isOpen()) {
            var focusable = Array.prototype.filter.call(
                palette.querySelectorAll('input, a[href]'),
                function (el) { return !el.hidden && el.offsetParent !== null; }
            );
            if (!focusable.length) return;
            var first = focusable[0], last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    });
})();
</script>

@stack('scripts')
<script>
document.addEventListener('click', function(e) {
    const tr = e.target.closest('tr[data-href]');
    if (tr) window.location = tr.dataset.href;
});
</script>
</body>
</html>
