<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — RealEstate Admin</title>

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0B1120">
    <link rel="icon" type="image/png" href="{{ asset('icons/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="P7H Real Estate">
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

    {{-- ── Design system ────────────────────────────────────────────────────
         Two stylesheets, and the order between them matters:

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
    $mobileRedesignedRoutes = [
        'buildings.index', 'floors.global', 'property-units.index', 'tenants.index',
        'maintenance.index', 'invoices.index', 'reports.index', 'tenants.show', 'buildings.show',
        'lease-contracts.index', 'payments.index',
    ];
    $isMobileScreen = request()->routeIs($mobileRedesignedRoutes);
    $pushedScreenRoutes = ['tenants.show', 'buildings.show'];
    $isPushedScreen = request()->routeIs($pushedScreenRoutes);
@endphp
<body class="{{ request()->routeIs('dashboard') ? 'is-dashboard' : '' }} {{ $isMobileScreen ? 'is-mobile-screen' : '' }} {{ $isPushedScreen ? 'is-pushed-screen' : '' }}">

{{-- ── EXPERIMENT: "Depth & Motion" shared helpers ─────────────────────
     Declared immediately after <body> opens (before @yield('content')
     renders) so any per-page script calling these — e.g. buildings/show
     wiring up pmInitHeroParallax — always finds them already defined.
     All opt-in: each helper only touches elements that carry the
     relevant marker class/attribute, so pages that don't use them are
     unaffected. ── --}}
<script>
(function () {
    /* Material-style ripple on any `.pm-ripple` element. */
    document.addEventListener('pointerdown', function (e) {
        const el = e.target.closest('.pm-ripple');
        if (!el) return;
        const rect = el.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height) * 1.6;
        const wave = document.createElement('span');
        wave.className = 'pm-ripple-wave';
        wave.style.width = wave.style.height = size + 'px';
        wave.style.left = (e.clientX - rect.left - size / 2) + 'px';
        wave.style.top = (e.clientY - rect.top - size / 2) + 'px';
        el.appendChild(wave);
        wave.addEventListener('animationend', () => wave.remove());
    });

    /* Collapsing large-title header: pass the header element, its
       scroll container, and the pixel threshold to shrink at. */
    window.pmInitCollapsingHeader = function (header, scroller, threshold) {
        if (!header || !scroller) return;
        threshold = threshold || 36;
        const read = () => (scroller === window ? window.scrollY : scroller.scrollTop);
        let ticking = false;
        function update() {
            header.classList.toggle('is-collapsed', read() > threshold);
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

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <a href="{{ url('/') }}" class="sidebar-logo logo-desktop">
        <div class="sidebar-logo-icon"><i class="fa-solid fa-building-columns"></i></div>
        <div class="sidebar-logo-text">
            <strong>RealEstate</strong>
            <span>Management Suite</span>
        </div>
    </a>
    <a href="{{ url('/') }}" class="logo-mobile" style="text-decoration:none;">
        <div class="logo-mobile-tile">P7</div>
        <div class="logo-mobile-text">
            <strong>Promoseven RE</strong>
            <span>MANAGEMENT SUITE</span>
        </div>
    </a>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Main</div>
        <a href="{{ url('/dashboard') }}" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-gauge-high nav-icon"></i> Dashboard
        </a>
    </div>

    @unless(auth()->user()?->isMaintenance())
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
    @endunless

    <div class="sidebar-section">
        <div class="sidebar-section-label">Management</div>
        @unless(auth()->user()?->isMaintenance())
        <a href="{{ route('tenants.index') }}" class="nav-item {{ request()->is('tenants*') ? 'active' : '' }}">
            <i class="fa-solid fa-users nav-icon"></i> Tenants
        </a>
        <a href="{{ route('lease-contracts.index') }}" class="nav-item {{ request()->is('lease-contracts*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-contract nav-icon"></i> Lease Contracts
        </a>
        @endunless
        <a href="{{ route('maintenance.index') }}" class="nav-item {{ request()->is('maintenance*') ? 'active' : '' }}">
            <i class="fa-solid fa-wrench nav-icon"></i> Maintenance
        </a>
    </div>

    @unless(auth()->user()?->isMaintenance())
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

    @if(auth()->user()?->canViewReports())
    <div class="sidebar-section">
        <div class="sidebar-section-label">Analytics</div>
        <a href="{{ route('reports.index') }}" class="nav-item {{ request()->is('reports*') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-bar nav-icon"></i> Reports
        </a>
    </div>
    @endif

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
        <a href="{{ route('settings.azure-mail.edit') }}" class="nav-item {{ request()->is('settings/azure-mail*') ? 'active' : '' }}">
            <i class="fa-solid fa-envelope nav-icon"></i> Mail Settings
        </a>
    </div>
    @endif
    @endunless

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}</div>
            <div class="user-info">
                <strong>{{ auth()->user()->name ?? 'Unknown' }}</strong>
                <span>{{ auth()->user()->role_label ?? '' }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin-left:auto">
                @csrf
                <button type="submit" class="topbar-icon-btn" style="width:28px;height:28px;font-size:12px" title="Sign out">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- MORE SHEET (mobile bottom tab bar "More" destination) -->
<div class="modal-overlay" id="moreSheet">
    <div class="modal-box" style="max-width:100%;padding:14px 10px 20px;">
        @unless(auth()->user()?->isMaintenance())
        <a href="{{ route('floors.global') }}" class="more-sheet-item">
            <div class="more-sheet-icon" style="background:var(--m-blue-tint);"><i class="fa-solid fa-layer-group" style="color:var(--m-blue);"></i></div>
            <div><div class="more-sheet-label">Floors</div><div class="more-sheet-desc">Browse all floors</div></div>
        </a>
        <a href="{{ route('property-units.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon" style="background:var(--m-green-tint);"><i class="fa-solid fa-door-open" style="color:var(--m-green);"></i></div>
            <div><div class="more-sheet-label">Property Units</div><div class="more-sheet-desc">Browse property units</div></div>
        </a>
        <a href="{{ route('lease-contracts.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon" style="background:var(--m-navy-active);"><i class="fa-solid fa-file-contract" style="color:#fff;"></i></div>
            <div><div class="more-sheet-label">Lease Contracts</div><div class="more-sheet-desc">Browse lease agreements</div></div>
        </a>
        <a href="{{ route('invoices.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon" style="background:var(--m-gold-tint);"><i class="fa-solid fa-file-invoice-dollar" style="color:var(--m-gold-text);"></i></div>
            <div><div class="more-sheet-label">Invoices</div><div class="more-sheet-desc">View and manage invoices</div></div>
        </a>
        <a href="{{ route('payments.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon" style="background:#E6F6EE;"><i class="fa-solid fa-money-bill-transfer" style="color:#17A96C;"></i></div>
            <div><div class="more-sheet-label">Payments</div><div class="more-sheet-desc">Track received payments</div></div>
        </a>
        @endunless
        @if(auth()->user()?->canViewReports())
        <a href="{{ route('reports.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon" style="background:var(--m-purple-tint);"><i class="fa-solid fa-chart-bar" style="color:var(--m-purple);"></i></div>
            <div><div class="more-sheet-label">Reports</div><div class="more-sheet-desc">Export portfolio reports</div></div>
        </a>
        @endif
        <button type="button" class="more-sheet-item" id="moreMenuBtn">
            <div class="more-sheet-icon" style="background:var(--m-navy-active);"><i class="fa-solid fa-bars" style="color:#fff;"></i></div>
            <div><div class="more-sheet-label">Full menu</div><div class="more-sheet-desc">All sections</div></div>
        </button>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="more-sheet-item danger">
                <div class="more-sheet-icon" style="background:var(--m-red-tint);"><i class="fa-solid fa-right-from-bracket" style="color:var(--m-red);"></i></div>
                <div><div class="more-sheet-label">Sign out</div><div class="more-sheet-desc">{{ auth()->user()->email ?? '' }}</div></div>
            </button>
        </form>
    </div>
</div>

<!-- MAIN WRAP -->
<div class="main-wrap">
    <!-- TOPBAR -->
    <header class="topbar">
        <button class="topbar-icon-btn" style="display:none" id="menuBtn">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-title">@yield('topbar-title', 'Dashboard')</div>
        <div class="topbar-actions">
            <button class="topbar-icon-btn theme-toggle-btn" title="Switch theme" aria-label="Switch to dark mode"><i class="fa-solid fa-moon"></i></button>
            <button class="topbar-icon-btn"><i class="fa-regular fa-bell"></i></button>
            <button class="topbar-icon-btn"><i class="fa-regular fa-circle-question"></i></button>
            <div class="user-avatar" style="width:32px;height:32px;font-size:12px;cursor:pointer;">{{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}</div>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <main class="page-content">
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
    $tabbarIsMore = !$tabbarIsMain && !$tabbarIsBuildings && !$tabbarIsTenants && !$tabbarIsMaintenance;
@endphp
<nav class="bottom-tabbar" id="bottomTabbar">
    <a href="{{ url('/dashboard') }}" class="tabbar-item {{ $tabbarIsMain ? 'active' : '' }}">
        <i class="fa-solid fa-house"></i> Home
    </a>
    @unless(auth()->user()?->isMaintenance())
    <a href="{{ route('buildings.index') }}" class="tabbar-item {{ $tabbarIsBuildings ? 'active' : '' }}">
        <i class="fa-solid fa-building"></i> Properties
    </a>
    <a href="{{ route('tenants.index') }}" class="tabbar-item {{ $tabbarIsTenants ? 'active' : '' }}">
        <i class="fa-solid fa-users"></i> Tenants
    </a>
    @endunless
    <a href="{{ route('maintenance.index') }}" class="tabbar-item {{ $tabbarIsMaintenance ? 'active' : '' }}">
        <i class="fa-solid fa-screwdriver-wrench"></i> Requests
    </a>
    <button type="button" class="tabbar-item {{ $tabbarIsMore ? 'active' : '' }}" id="moreTabBtn">
        <i class="fa-solid fa-ellipsis"></i> More
    </button>
</nav>

<script>
let mDebounceTimer;
function mDebounceSubmit(el) {
    clearTimeout(mDebounceTimer);
    mDebounceTimer = setTimeout(() => el.form.submit(), 500);
}
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
    menuBtn.addEventListener('click', () => {
        sidebar.classList.contains('open') ? closeDrawer() : openDrawer();
    });
    backdrop.addEventListener('click', closeDrawer);

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

@stack('scripts')
<script>
document.addEventListener('click', function(e) {
    const tr = e.target.closest('tr[data-href]');
    if (tr) window.location = tr.dataset.href;
});
</script>
</body>
</html>
