<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — RealEstate Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --sidebar-bg:       #0B1120;
            --sidebar-border:   #1A2540;
            --sidebar-hover:    #131E35;
            --sidebar-active:   #1E2D4A;
            --accent:           #E8B86D;
            --accent-dim:       rgba(232,184,109,0.12);
            --accent-glow:      rgba(232,184,109,0.25);
            --page-bg:          #F1F5F9;
            --card-bg:          #FFFFFF;
            --card-border:      #E2E8F0;
            --text-primary:     #0F172A;
            --text-secondary:   #475569;
            --text-muted:       #94A3B8;
            --text-sidebar:     #8A9BBE;
            --text-sidebar-active: #FFFFFF;
            --input-bg:         #FFFFFF;
            --input-border:     #CBD5E1;
            --input-focus:      #E8B86D;
            --danger:           #EF4444;
            --success:          #10B981;
            --info:             #3B82F6;
            --warning:          #F59E0B;
            --shadow-sm:        0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md:        0 4px 16px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.04);
            --shadow-lg:        0 10px 40px rgba(0,0,0,0.10);
            --radius:           12px;
            --radius-sm:        8px;
            --sidebar-width:    260px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--page-bg);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
            font-size: 14px;
        }

        /* ── SIDEBAR ─────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
            border-right: 1px solid var(--sidebar-border);
            transition: transform 0.3s ease;
        }

        .sidebar-logo {
            padding: 24px 20px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--sidebar-border);
            text-decoration: none;
        }
        .sidebar-logo-icon {
            width: 38px; height: 38px;
            background: var(--accent);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            color: #0B1120;
            flex-shrink: 0;
            box-shadow: 0 0 20px var(--accent-glow);
        }
        .sidebar-logo-text { line-height: 1; }
        .sidebar-logo-text strong {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            display: block;
        }
        .sidebar-logo-text span {
            font-size: 10px;
            color: var(--text-sidebar);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .sidebar-section {
            padding: 20px 12px 8px;
        }
        .sidebar-section-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-sidebar);
            padding: 0 8px;
            margin-bottom: 6px;
            opacity: 0.6;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--text-sidebar);
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.18s ease;
            position: relative;
            margin-bottom: 2px;
        }
        .nav-item:hover {
            background: var(--sidebar-hover);
            color: #C8D6F0;
        }
        .nav-item.active {
            background: var(--sidebar-active);
            color: var(--text-sidebar-active);
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 20%; bottom: 20%;
            width: 3px;
            background: var(--accent);
            border-radius: 0 3px 3px 0;
        }
        .nav-item .nav-icon {
            width: 18px;
            text-align: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .nav-item.active .nav-icon { color: var(--accent); }
        .nav-badge {
            margin-left: auto;
            background: var(--accent-dim);
            color: var(--accent);
            font-size: 10px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 20px;
        }


        .sidebar-footer {
            margin-top: auto;
            padding: 16px 12px;
            border-top: 1px solid var(--sidebar-border);
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background 0.18s;
        }
        .sidebar-user:hover { background: var(--sidebar-hover); }
        .user-avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--accent), #C49040);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
            color: #0B1120;
            flex-shrink: 0;
        }
        .user-info strong { display: block; font-size: 13px; color: #fff; font-weight: 600; }
        .user-info span { font-size: 11px; color: var(--text-sidebar); }

        /* ── MAIN CONTENT ─────────────────────────────────── */
        .main-wrap {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .topbar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--card-border);
            padding: 0 28px;
            height: 60px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 90;
        }
        .topbar-title {
            font-family: 'Outfit', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
            flex: 1;
        }
        .topbar-actions { display: flex; align-items: center; gap: 10px; }
        .topbar-icon-btn {
            width: 36px; height: 36px;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-sm);
            background: transparent;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-secondary);
            font-size: 14px;
            transition: all 0.15s;
        }
        .topbar-icon-btn:hover { background: var(--page-bg); color: var(--text-primary); }

        .page-content {
            padding: 28px;
            flex: 1;
        }

        /* ── CARDS ────────────────────────────────────────── */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }
        .card-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .card-header-icon {
            width: 34px; height: 34px;
            border-radius: var(--radius-sm);
            background: var(--accent-dim);
            display: flex; align-items: center; justify-content: center;
            color: var(--accent);
            font-size: 15px;
            flex-shrink: 0;
        }
        .card-header h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .card-header p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 1px;
        }
        .card-body { padding: 22px; }

        /* ── FORM ─────────────────────────────────────────── */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px;
        }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.col-span-2 { grid-column: span 2; }
        .form-group.col-span-full { grid-column: 1 / -1; }

        label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            letter-spacing: 0.02em;
        }
        label .required { color: var(--danger); margin-left: 2px; }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 9px 13px;
            border: 1.5px solid var(--input-border);
            border-radius: var(--radius-sm);
            background: var(--input-bg);
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13.5px;
            transition: border-color 0.18s, box-shadow 0.18s;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }
        input.error, select.error { border-color: var(--danger); }
        .field-error { font-size: 11px; color: var(--danger); margin-top: 2px; }

        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        textarea { resize: vertical; min-height: 80px; }

        /* ── BUTTONS ──────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.18s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-primary {
            background: var(--accent);
            color: #0B1120;
        }
        .btn-primary:hover { background: #D4A558; box-shadow: 0 4px 14px var(--accent-glow); transform: translateY(-1px); }
        .btn-outline {
            background: transparent;
            border: 1.5px solid var(--card-border);
            color: var(--text-secondary);
        }
        .btn-outline:hover { background: var(--page-bg); color: var(--text-primary); }
        .btn-danger { background: #FEF2F2; color: var(--danger); border: 1.5px solid #FECACA; }
        .btn-danger:hover { background: var(--danger); color: white; }
        .btn-success { background: #ECFDF5; color: var(--success); border: 1.5px solid #A7F3D0; }
        .btn-success:hover { background: var(--success); color: white; }
        .btn-sm { padding: 6px 13px; font-size: 12px; }
        .btn-lg { padding: 12px 24px; font-size: 14.5px; }
        .btn:active { transform: translateY(0); }

        /* ── TABLE ────────────────────────────────────────── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            padding: 11px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            background: var(--page-bg);
            border-bottom: 1px solid var(--card-border);
            white-space: nowrap;
        }
        tbody td {
            padding: 13px 16px;
            font-size: 13.5px;
            color: var(--text-primary);
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #FAFBFC; }

        /* ── BADGES ───────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-gold  { background: var(--accent-dim); color: var(--accent); }
        .badge-green { background: #ECFDF5; color: var(--success); }
        .badge-blue  { background: #EFF6FF; color: var(--info); }
        .badge-gray  { background: #F1F5F9; color: var(--text-secondary); }
        .badge-red   { background: #FEF2F2; color: var(--danger); }

        /* ── PAGE HEADER ──────────────────────────────────── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 14px;
        }
        .page-header-title {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
        }
        .page-header-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 3px;
        }
        .page-header-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        /* ── BREADCRUMB ───────────────────────────────────── */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb i { font-size: 9px; }

        /* ── SECTION DIVIDER ──────────────────────────────── */
        .section-stack { display: flex; flex-direction: column; gap: 20px; }

        /* ── ALERTS ───────────────────────────────────────── */
        .alert {
            padding: 13px 16px;
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
        }
        .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert-danger  { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }

        /* ── RESPONSIVE ───────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-wrap { margin-left: 0; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.col-span-2 { grid-column: span 1; }
        }

        /* ── SCROLLBAR ────────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    </style>

    @stack('styles')
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <a href="{{ url('/') }}" class="sidebar-logo">
        <div class="sidebar-logo-icon"><i class="fa-solid fa-building-columns"></i></div>
        <div class="sidebar-logo-text">
            <strong>RealEstate</strong>
            <span>Management Suite</span>
        </div>
    </a>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Main</div>
        <a href="{{ url('/dashboard') }}" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-gauge-high nav-icon"></i> Dashboard
        </a>
    </div>

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

    <div class="sidebar-section">
        <div class="sidebar-section-label">Management</div>
        <a href="{{ route('tenants.index') }}" class="nav-item {{ request()->is('tenants*') ? 'active' : '' }}">
            <i class="fa-solid fa-users nav-icon"></i> Tenants
        </a>
        <a href="{{ route('lease-contracts.index') }}" class="nav-item {{ request()->is('lease-contracts*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-contract nav-icon"></i> Lease Contracts
        </a>
        <a href="{{ route('maintenance.index') }}" class="nav-item {{ request()->is('maintenance*') ? 'active' : '' }}">
            <i class="fa-solid fa-wrench nav-icon"></i> Maintenance
        </a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Analytics</div>
        <a href="{{ route('reports.index') }}" class="nav-item {{ request()->is('reports*') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-bar nav-icon"></i> Reports
        </a>
    </div>

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

    <div class="sidebar-section">
        <div class="sidebar-section-label">Admin</div>
        <a href="{{ route('admin.audit-log') }}" class="nav-item {{ request()->is('admin/audit-log*') ? 'active' : '' }}">
            <i class="fa-solid fa-clock-rotate-left nav-icon"></i> Audit Log
        </a>
        <a href="{{ route('admin.error-log') }}" class="nav-item {{ request()->is('admin/error-log*') ? 'active' : '' }}">
            <i class="fa-solid fa-triangle-exclamation nav-icon"></i> Error Log
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">A</div>
            <div class="user-info">
                <strong>Admin User</strong>
                <span>Administrator</span>
            </div>
            <i class="fa-solid fa-ellipsis-vertical" style="margin-left:auto;color:var(--text-sidebar);font-size:12px;"></i>
        </div>
    </div>
</aside>

<!-- MAIN WRAP -->
<div class="main-wrap">
    <!-- TOPBAR -->
    <header class="topbar">
        <button class="topbar-icon-btn" onclick="document.getElementById('sidebar').classList.toggle('open')" style="display:none" id="menuBtn">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-title">@yield('topbar-title', 'Dashboard')</div>
        <div class="topbar-actions">
            <button class="topbar-icon-btn"><i class="fa-regular fa-bell"></i></button>
            <button class="topbar-icon-btn"><i class="fa-regular fa-circle-question"></i></button>
            <div class="user-avatar" style="width:32px;height:32px;font-size:12px;cursor:pointer;">A</div>
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

<script>
    // Mobile menu
    const menuBtn = document.getElementById('menuBtn');
    if (window.innerWidth <= 768) menuBtn.style.display = 'flex';
    window.addEventListener('resize', () => {
        menuBtn.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
    });
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
