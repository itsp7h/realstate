<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — RealEstate Admin</title>
    <script>
        (function () {
            // Applied before first paint to avoid a flash of the wrong theme.
            var saved = localStorage.getItem('p7-theme');
            var theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root, :root[data-theme="light"] {
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
            --sheet-radius:     22px;
            --ease-spring:      cubic-bezier(.32,.72,0,1);
            --duration-sheet:   380ms;
            --scrim:            rgba(11,17,32,0.55);

            /* ── Mobile app design tokens (Promoseven RE mobile spec) ── */
            --m-bg:            #F4F1EA;
            --m-card:          #FFFFFF;
            --m-ink:           #17203A;
            --m-muted:         #8E9AAE;
            --m-faint:         #A7B0C0;
            --m-line:          #F1F3F8;
            --m-border:        #E4E9F0;

            --m-navy:          #10141F;
            --m-navy-2:        #1E2842;
            --m-navy-line:     #1A2540;
            --m-navy-active:   #1E2D4A;
            --m-navy-text:     #8A9BBE;

            --m-gold:          #E7B266;
            --m-gold-deep:     #D99A3D;
            --m-gold-text:     #C08A2D;
            --m-gold-tint:     #FBF3E4;
            --m-gold-on:       #2A2312;
            --m-gold-grad:     linear-gradient(135deg,#EDBE78,#DC9E45);

            --m-green:  #17A96C;  --m-green-tint:  #E6F6EE;
            --m-red:    #D64545;  --m-red-tint:    #FCEBEB;
            --m-blue:   #4A7DF0;  --m-blue-tint:   #E9F0FD;
            --m-purple: #7A5AF8;  --m-purple-tint: #EFEBFD;

            --m-r-card: 18px;  --m-r-btn: 12px;  --m-r-chip: 10px;  --m-r-badge: 7px;
            --m-shadow: 0 1px 3px rgba(23,32,58,.05);
            --m-shadow-float: 0 8px 24px rgba(23,32,58,.10);
        }

        /* ── Dark mode ──────────────────────────────────────
             Retheme's the shared shell (sidebar, topbar, page bg,
             cards, tables, inputs, alerts) which every page builds
             on. Pages' own colored accents (status badge tints,
             chart series colors) intentionally stay as-is in both
             modes, same as most dark-mode products keep their tag
             colors vivid rather than desaturating them. ── */
        :root[data-theme="dark"] {
            --sidebar-bg:       #0B1120;
            --sidebar-border:   #1A2540;
            --sidebar-hover:    #131E35;
            --sidebar-active:   #1E2D4A;
            --accent:           #E8B86D;
            --accent-dim:       rgba(232,184,109,0.12);
            --accent-glow:      rgba(232,184,109,0.25);
            --page-bg:          #0D1220;
            --card-bg:          #131A2B;
            --card-border:      #232C42;
            --text-primary:     #F1F4F9;
            --text-secondary:   #B7C0D1;
            --text-muted:       #7E8AA3;
            --text-sidebar:     #8A9BBE;
            --text-sidebar-active: #FFFFFF;
            --input-bg:         #0F1524;
            --input-border:     #2A3348;
            --input-focus:      #E8B86D;
            --danger:           #F87171;
            --success:          #34D399;
            --info:             #60A5FA;
            --warning:          #FBBF24;
            --shadow-sm:        0 1px 3px rgba(0,0,0,0.30), 0 1px 2px rgba(0,0,0,0.20);
            --shadow-md:        0 4px 16px rgba(0,0,0,0.36), 0 2px 6px rgba(0,0,0,0.24);
            --shadow-lg:        0 10px 40px rgba(0,0,0,0.45);
            --radius:           12px;
            --radius-sm:        8px;
            --sidebar-width:    260px;
            --sheet-radius:     22px;
            --ease-spring:      cubic-bezier(.32,.72,0,1);
            --duration-sheet:   380ms;
            --scrim:            rgba(0,0,0,0.65);
        }
        :root[data-theme="dark"] body { background: var(--page-bg); }
        :root[data-theme="dark"] thead th { background: #0F1526; }
        :root[data-theme="dark"] tbody tr:hover td { background: #171F33; }
        :root[data-theme="dark"] input[type="text"],
        :root[data-theme="dark"] input[type="number"],
        :root[data-theme="dark"] input[type="date"],
        :root[data-theme="dark"] input[type="email"],
        :root[data-theme="dark"] select,
        :root[data-theme="dark"] textarea {
            color-scheme: dark;
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

        /* ── SIDEBAR BACKDROP (scrim behind the drawer) ────── */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            z-index: 99;
            background: var(--scrim);
            backdrop-filter: blur(2px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .sidebar-backdrop.show { opacity: 1; pointer-events: all; }

        /* ── MORE SHEET (base positioning — this lives in the shared
             layout, so unlike per-page modals it can't rely on that
             page also defining .modal-overlay's fixed/centered base) ── */
        #moreSheet {
            position: fixed; inset: 0; z-index: 1050;
            background: var(--scrim);
            display: flex;
            opacity: 0; pointer-events: none;
            transition: opacity 0.25s ease;
        }
        #moreSheet.open { opacity: 1; pointer-events: all; }

        /* ── SCROLLBAR ────────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
    </style>

    @stack('styles')

    {{-- ── MOBILE APP SYSTEM ──────────────────────────────────────────────
         Loaded after @stack('styles') on purpose: at the same specificity,
         later-in-source wins, so these rules override any per-page mobile
         modal tweaks and give every .modal-overlay/.modal-box in the app
         (buildings, units, tenants, floors, maintenance, leases, invoices,
         EWA bills, dashboard import, form-configs, ...) one consistent
         bottom-sheet behavior without editing each view. Drawer + sheet
         share the same spring easing so they read as one native system. --}}
    <style>
        @media (max-width: 768px) {
            #menuBtn { display: flex !important; }
            body { font-family: 'Poppins', sans-serif; }
            body.is-dashboard .topbar { display: none; }
            body.is-dashboard .page-content { padding: 0 0 calc(78px + env(safe-area-inset-bottom)); }

            .sidebar {
                width: clamp(260px, 84vw, 300px);
                background: var(--m-navy);
                transition: transform var(--duration-sheet) var(--ease-spring);
                box-shadow: none;
            }
            .sidebar.open { box-shadow: var(--shadow-lg); }
            .logo-desktop { display: none; }
            .logo-mobile {
                display: flex; align-items: center; gap: 12px;
                padding: 24px 20px 18px; border-bottom: 1px solid var(--m-navy-line);
            }
            .logo-mobile-tile {
                width: 40px; height: 40px; border-radius: 11px;
                background: var(--m-gold); color: var(--m-navy);
                display: flex; align-items: center; justify-content: center;
                font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 15px;
                box-shadow: 0 0 20px rgba(231,178,102,.25); flex-shrink: 0;
            }
            .logo-mobile-text strong { display: block; color: #fff; font-weight: 700; font-size: 14px; }
            .logo-mobile-text span { color: var(--m-navy-text); font-size: 9.5px; letter-spacing: 1.2px; font-weight: 600; }

            .sidebar .nav-item { border-radius: 10px; font-family: 'Poppins', sans-serif; min-height: 44px; }
            .sidebar .nav-item.active .nav-icon { color: var(--m-gold); }
            .sidebar .nav-item.active::before { background: var(--m-gold); }

            /* ── Shared mobile screen components (list pages) ───────
                 Reused across Buildings/Floors/Units/Tenants/Maintenance/
                 Invoices/Reports so each page doesn't repeat this CSS. ── */
            .m-screen { font-family: 'Poppins', sans-serif; color: var(--m-ink); padding: 18px 18px calc(28px + env(safe-area-inset-bottom)); display: flex; flex-direction: column; gap: 14px; }

            .m-action-row { display: flex; gap: 10px; }
            .m-action-btn {
                flex: 1; height: 46px; border-radius: var(--m-r-btn); font-weight: 600; font-size: 13px;
                font-family: 'Poppins', sans-serif; cursor: pointer; display: flex; align-items: center;
                justify-content: center; gap: 6px; text-decoration: none; border: none;
            }
            .m-action-btn.primary { background: var(--m-gold); color: var(--m-gold-on); font-weight: 700; flex: 1.4; }
            .m-action-btn.outline { background: var(--m-card); border: 1.5px solid var(--m-border); color: #4A5568; }
            .m-action-btn.green-outline { background: #F2FBF6; border: 1.5px solid #A8DFC6; color: var(--m-green); }

            .m-mini-row { display: flex; gap: 10px; }
            .m-mini-stat { flex: 1; background: var(--m-card); border-radius: 14px; padding: 12px; display: flex; flex-direction: column; align-items: center; gap: 2px; box-shadow: var(--m-shadow); }
            .m-mini-stat .v { font-size: 19px; font-weight: 800; color: var(--m-ink); }
            .m-mini-stat .l { font-size: 10.5px; color: var(--m-muted); }

            .m-search-input {
                height: 48px; border: 1.5px solid var(--m-border); border-radius: var(--m-r-btn);
                padding: 0 16px; font-size: 13.5px; font-family: 'Poppins', sans-serif;
                background: var(--m-card); outline: none; color: var(--m-ink); width: 100%;
            }

            .m-chip-row { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 2px; }
            .m-chip-row.no-sb::-webkit-scrollbar { display: none; }
            .m-chip {
                height: 40px; padding: 0 16px; border-radius: 11px; font-size: 12.5px; font-weight: 600;
                font-family: 'Poppins', sans-serif; cursor: pointer; flex-shrink: 0; display: flex;
                align-items: center; text-decoration: none; border: 1.5px solid var(--m-border);
                background: var(--m-card); color: #6B7688;
            }
            .m-chip.active { border-color: var(--m-gold); background: var(--m-gold); color: var(--m-gold-on); }

            .m-row-list { display: flex; flex-direction: column; gap: 10px; }
            .m-row-card {
                background: var(--m-card); border-radius: 15px; padding: 14px 16px; display: flex;
                align-items: center; gap: 13px; box-shadow: var(--m-shadow); text-decoration: none; color: inherit;
                border: none; width: 100%; text-align: left; font-family: 'Poppins', sans-serif; cursor: pointer;
            }
            .m-row-icon { width: 42px; height: 42px; border-radius: 13px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 17px; }
            .m-row-title { font-size: 13.5px; font-weight: 700; color: var(--m-ink); }
            .m-row-sub { font-size: 11px; color: var(--m-muted); margin-top: 1px; }
            .m-row-chip { padding: 6px 9px; border-radius: 9px; background: var(--m-gold-tint); color: var(--m-gold-text); font-size: 10.5px; font-weight: 700; flex-shrink: 0; }
            .m-row-badge { padding: 4px 10px; border-radius: 8px; font-size: 10.5px; font-weight: 600; flex-shrink: 0; }
            .m-row-chevron { color: #C3CBD8; font-size: 14px; flex-shrink: 0; }

            .m-empty { text-align: center; padding: 60px 24px; }
            .m-empty-icon { width: 64px; height: 64px; border-radius: 18px; background: var(--m-gold-tint); display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--m-gold-text); margin: 0 auto 14px; }
            .m-empty-title { font-size: 15px; font-weight: 700; color: var(--m-ink); margin-bottom: 4px; }
            .m-empty-sub { font-size: 12px; color: var(--m-muted); }

            /* Desktop list-page chrome each redesigned mobile screen replaces —
               scoped to body.is-mobile-screen so untouched pages (Lease
               Contracts, Expenses, Payments, ...) keep their normal header. */
            body.is-mobile-screen .page-header,
            body.is-mobile-screen .stats-grid,
            body.is-mobile-screen .m-hide-desktop-index { display: none !important; }

            /* ── Bottom sheet (shared by modals + the More menu) ────── */
            .modal-overlay {
                align-items: flex-end;
                padding: 0;
                background: var(--scrim);
            }
            .modal-box {
                width: 100%;
                max-width: 100%;
                max-height: 92vh;
                margin: 0;
                border-radius: var(--sheet-radius) var(--sheet-radius) 0 0;
                transform: translateY(100%) scale(1);
                transition: transform var(--duration-sheet) var(--ease-spring);
                padding-top: 6px;
                padding-bottom: env(safe-area-inset-bottom);
            }
            .modal-overlay.open .modal-box { transform: translateY(0) scale(1); }

            .sheet-handle {
                width: 36px;
                height: 4px;
                border-radius: 3px;
                background: var(--input-border);
                margin: 10px auto 4px;
                transition: background 0.15s ease, box-shadow 0.15s ease;
                touch-action: none;
            }
            .sheet-handle.dragging { transition: none; }

            /* ── Standard mobile header ───────────────────────────── */
            .topbar {
                height: auto;
                padding: calc(18px + env(safe-area-inset-top)) 18px 12px;
                gap: 10px;
                font-family: 'Poppins', sans-serif;
            }
            .topbar-title { font-size: 19px; font-weight: 800; font-family: 'Poppins', sans-serif; }
            #menuBtn, .topbar-actions .topbar-icon-btn { width: 40px; height: 40px; border-radius: 12px; }
            .topbar .user-avatar { width: 40px; height: 40px; font-size: 14px; }

            /* ── More sheet ───────────────────────────────────────── */
            .more-sheet-item {
                display: flex; align-items: center; gap: 14px;
                width: 100%; padding: 12px 10px; border: none; background: none;
                border-radius: 14px; cursor: pointer; text-align: left;
                font-family: 'Poppins', sans-serif; min-height: 56px;
                text-decoration: none; color: inherit;
            }
            .more-sheet-item:active { background: var(--m-line); }
            .more-sheet-icon {
                width: 42px; height: 42px; border-radius: 13px; flex-shrink: 0;
                display: flex; align-items: center; justify-content: center; font-size: 17px;
            }
            .more-sheet-label { font-size: 14px; font-weight: 700; color: var(--m-ink); }
            .more-sheet-desc { font-size: 11px; color: var(--m-muted); margin-top: 1px; }
            .more-sheet-item.danger .more-sheet-label { color: var(--m-red); }

            /* ── Bottom tab bar ──────────────────────────────────── */
            .page-content { padding-bottom: calc(78px + env(safe-area-inset-bottom)); }

            .bottom-tabbar {
                display: flex;
                position: fixed;
                left: 0; right: 0; bottom: 0;
                z-index: 95;
                background: var(--m-card);
                border-top: 1px solid #E9EDF3;
                padding: 8px 4px calc(8px + env(safe-area-inset-bottom));
                box-shadow: 0 -2px 16px rgba(0,0,0,0.05);
            }
            .tabbar-item {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 4px;
                padding: 6px 2px;
                border: none;
                background: none;
                color: #9AA5B8;
                text-decoration: none;
                font-family: 'Poppins', sans-serif;
                font-size: 10px;
                font-weight: 600;
                transition: color 0.15s ease;
            }
            .tabbar-item i { font-size: 21px; transition: transform 0.15s var(--ease-spring); }
            .tabbar-item.active { color: var(--m-gold-deep); }
            .tabbar-item.active i { transform: translateY(-1px); }
            .tabbar-item:active i { transform: scale(0.88); }
        }

        @media (min-width: 769px) {
            .sheet-handle { display: none; }
            .bottom-tabbar { display: none; }
            .logo-mobile { display: none; }
            #moreSheet { display: none !important; }
            .m-screen { display: none !important; }
        }
    </style>
</head>
@php
    $mobileRedesignedRoutes = [
        'buildings.index', 'floors.global', 'property-units.index', 'tenants.index',
        'maintenance.index', 'invoices.index', 'reports.index',
    ];
    $isMobileScreen = request()->routeIs($mobileRedesignedRoutes);
@endphp
<body class="{{ request()->routeIs('dashboard') ? 'is-dashboard' : '' }} {{ $isMobileScreen ? 'is-mobile-screen' : '' }}">

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
        <a href="{{ route('invoices.index') }}" class="more-sheet-item">
            <div class="more-sheet-icon" style="background:var(--m-gold-tint);"><i class="fa-solid fa-file-invoice-dollar" style="color:var(--m-gold-text);"></i></div>
            <div><div class="more-sheet-label">Invoices &amp; Payments</div><div class="more-sheet-desc">View invoices and payments</div></div>
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
            <button class="topbar-icon-btn" id="themeToggleBtn" title="Switch theme" aria-label="Switch to dark mode"><i class="fa-solid fa-moon"></i></button>
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
        <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>
    @unless(auth()->user()?->isMaintenance())
    <a href="{{ route('buildings.index') }}" class="tabbar-item {{ $tabbarIsBuildings ? 'active' : '' }}">
        <i class="fa-solid fa-building"></i> Buildings
    </a>
    <a href="{{ route('tenants.index') }}" class="tabbar-item {{ $tabbarIsTenants ? 'active' : '' }}">
        <i class="fa-solid fa-users"></i> Tenants
    </a>
    @endunless
    <a href="{{ route('maintenance.index') }}" class="tabbar-item {{ $tabbarIsMaintenance ? 'active' : '' }}">
        <i class="fa-solid fa-wrench"></i> Maintenance
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
    const btn = document.getElementById('themeToggleBtn');
    if (!btn) return;
    const icon = btn.querySelector('i');

    function syncIcon() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    }
    syncIcon();

    btn.addEventListener('click', function () {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('p7-theme', next);
        syncIcon();
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
