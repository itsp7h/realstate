<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="P7H Real Estate">
<title>Sign In — RealEstate Admin</title>

<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#0B1120">
<link rel="icon" type="image/png" href="{{ asset('icons/favicon-32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
    }
</script>
<script>
    (function () {
        // Applied before first paint to avoid a flash of the wrong theme.
        // Shares the same storage key as the app, but defaults to dark here
        // (the mobile login's photo hero is designed dark-first) unless the
        // user has explicitly chosen light somewhere.
        var saved = localStorage.getItem('p7-theme');
        document.documentElement.setAttribute('data-theme', saved === 'light' ? 'light' : 'dark');
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    :root {
        --sidebar-bg:    #0B1120;
        --sidebar-border:#1A2540;
        --accent:        #E8B86D;
        --accent-dim:    rgba(232,184,109,0.12);
        --accent-glow:   rgba(232,184,109,0.25);
        --page-bg:       #F1F5F9;
        --card-bg:       #FFFFFF;
        --card-border:   #E2E8F0;
        --text-primary:  #0F172A;
        --text-secondary:#475569;
        --text-muted:    #94A3B8;
        --input-bg:      #FFFFFF;
        --input-border:  #CBD5E1;
        --danger:        #EF4444;
        --radius:        12px;
        --radius-sm:     8px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        min-height: 100vh;
        color: var(--text-primary);
        font-size: 14px;
    }

    .mobile-shell { display: none; }

    /* ── DESKTOP — brand panel + form panel ─────────────────────────── */
    .desktop-shell { display: flex; min-height: 100vh; }

    .brand-panel {
        flex: 0 0 42%;
        background: var(--sidebar-bg);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 60px;
    }
    .brand-panel::before {
        content: '';
        position: absolute;
        top: -20%; left: -10%;
        width: 480px; height: 480px;
        background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
        pointer-events: none;
    }
    .brand-logo {
        display: flex;
        align-items: center;
        gap: 14px;
        position: relative;
        z-index: 1;
    }
    .brand-logo img { width: 46px; height: 46px; border-radius: 10px; }
    .brand-logo-text { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: #FFFFFF; line-height: 1.3; }
    .brand-logo-text span { display: block; font-size: 11px; font-weight: 500; color: var(--accent); letter-spacing: 0.06em; text-transform: uppercase; margin-top: 2px; }

    .brand-headline {
        font-family: 'Outfit', sans-serif;
        font-size: 34px;
        font-weight: 800;
        color: #FFFFFF;
        line-height: 1.25;
        margin-top: 48px;
        position: relative;
        z-index: 1;
        max-width: 420px;
    }
    .brand-headline em {
        font-style: normal;
        color: var(--accent);
    }
    .brand-sub {
        margin-top: 16px;
        font-size: 14px;
        color: #8A9BBE;
        line-height: 1.7;
        max-width: 380px;
        position: relative;
        z-index: 1;
    }

    .brand-footer {
        position: relative;
        z-index: 1;
        margin-top: 64px;
        font-size: 12px;
        color: #5B6B8C;
    }

    .form-panel {
        flex: 1;
        background: var(--page-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 24px;
    }
    .form-card {
        width: 100%;
        max-width: 380px;
    }
    .form-title {
        font-family: 'Outfit', sans-serif;
        font-size: 24px;
        font-weight: 800;
        color: var(--text-primary);
    }
    .form-sub {
        margin-top: 6px;
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 32px;
    }

    .alert-error {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: #FEF2F2;
        border: 1px solid #FECACA;
        color: #991B1B;
        border-radius: var(--radius-sm);
        padding: 12px 14px;
        font-size: 13px;
        margin-bottom: 20px;
        line-height: 1.5;
    }
    .alert-error i { margin-top: 2px; }

    .form-group { margin-bottom: 18px; }
    .form-label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }
    .form-control {
        width: 100%;
        padding: 11px 14px;
        font-size: 14px;
        font-family: inherit;
        border: 1.5px solid var(--input-border);
        border-radius: var(--radius-sm);
        background: var(--input-bg);
        color: var(--text-primary);
        outline: none;
        transition: border-color 0.15s;
    }
    .form-control:focus { border-color: var(--accent); }
    .form-control.is-invalid { border-color: var(--danger); }
    .field-error { color: var(--danger); font-size: 12px; margin-top: 5px; }

    .form-row {
        display: flex;
        align-items: center;
        margin-bottom: 26px;
    }
    .remember-check {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--text-secondary);
        cursor: pointer;
        user-select: none;
    }
    .remember-check input { accent-color: var(--accent); width: 15px; height: 15px; cursor: pointer; }
    .forgot-link { font-weight: 500; font-size: 13.5px; color: var(--text-muted); cursor: default; }

    .btn-submit {
        width: 100%;
        padding: 12px;
        background: var(--accent);
        color: #0B1120;
        border: none;
        border-radius: var(--radius-sm);
        font-family: 'Outfit', sans-serif;
        font-size: 14.5px;
        font-weight: 700;
        cursor: pointer;
        transition: filter 0.15s, transform 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-submit:hover { filter: brightness(1.06); transform: translateY(-1px); }
    .btn-submit:active { transform: translateY(0); }

    @media (max-width: 860px) and (min-width: 769px) {
        .desktop-shell { flex-direction: column; }
        .brand-panel { flex: 0 0 auto; padding: 40px 32px; }
        .brand-headline { font-size: 26px; margin-top: 28px; }
        .brand-footer { display: none; }
        .form-panel { padding: 40px 24px 60px; }
    }

    /* ── MOBILE — photo hero + sheet (design-handoff option 1b) ─────── */
    :root {
        --m-body-bg:       #060b14;
        --m-sheet-bg:      #0b1220;
        --m-sheet-border:  rgba(255,255,255,.09);
        --m-sheet-shadow:  rgba(0,0,0,.55);
        --m-heading:       #fff;
        --m-subcopy:       rgba(255,255,255,.55);
        --m-field-bg:      #111a2a;
        --m-field-border:  rgba(255,255,255,.09);
        --m-field-icon:    rgba(255,255,255,.5);
        --m-field-text:    #fff;
        --m-field-placeholder: rgba(255,255,255,.38);
        --m-field-error:   #FCA5A5;
        --m-error-bg:      rgba(239,68,68,.14);
        --m-error-border:  rgba(239,68,68,.3);
        --m-error-text:    #FCA5A5;
        --m-link:          #6FA8F5;
        --m-divider-line:  rgba(255,255,255,.1);
        --m-divider-label: rgba(255,255,255,.4);
        --m-social-bg:     rgba(255,255,255,.05);
        --m-social-border: rgba(255,255,255,.11);
        --m-social-text:   #fff;
        --m-legal:         rgba(255,255,255,.38);
        --m-home-indicator:rgba(255,255,255,.25);
    }
    :root[data-theme="light"] {
        /* "2b" design handoff — frosted-glass card over the building photo,
           rather than a flat card, so the hero photo stays visible/blurred
           behind the sheet instead of being covered by an opaque panel. */
        --m-body-bg:       #F6F4F0;
        --m-sheet-bg:      rgba(255,255,255,.4);
        --m-sheet-border:  rgba(255,255,255,.65);
        --m-sheet-shadow:  rgba(14,20,32,.18);
        --m-heading:       #0E1420;
        --m-subcopy:       rgba(14,20,32,.66);
        --m-field-bg:      rgba(255,255,255,.62);
        --m-field-border:  rgba(255,255,255,.7);
        --m-field-icon:    rgba(14,20,32,.42);
        --m-field-text:    #0E1420;
        --m-field-placeholder: rgba(14,20,32,.42);
        --m-field-error:   #DC2626;
        --m-error-bg:      #FEF2F2;
        --m-error-border:  #FECACA;
        --m-error-text:    #991B1B;
        --m-link:          #1B62C4;
        --m-divider-line:  rgba(14,20,32,.14);
        --m-divider-label: rgba(14,20,32,.42);
        --m-social-bg:     rgba(255,255,255,.5);
        --m-social-border: rgba(255,255,255,.7);
        --m-social-text:   #0E1420;
        --m-legal:         rgba(14,20,32,.42);
        --m-home-indicator:rgba(14,20,32,.2);
        --m-toggle-pw:     #B87A05;
    }

    @media (max-width: 768px) {
        .desktop-shell { display: none; }
        .mobile-shell { display: flex; justify-content: center; }

        html, body { overflow: hidden; height: 100%; }
        body { font-family: 'Figtree', sans-serif; background: var(--m-body-bg); }

        .phone {
            position: relative;
            width: 100%;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
            background: var(--m-body-bg);
        }
        .photo {
            position: absolute; top: 0; left: 0; width: 100%; height: 430px;
            background: url('{{ asset('images/login-building.jpg') }}') 50% 18% / cover no-repeat;
        }
        .photo-scrim {
            position: absolute; top: 0; left: 0; right: 0; height: 430px;
            background: linear-gradient(180deg, rgba(6,11,20,.62) 0%, rgba(6,11,20,.15) 40%, rgba(6,11,20,.75) 100%);
        }
        .watermark {
            position: absolute; top: 236px; left: 0; right: 0; text-align: center;
            font-family: 'Figtree', sans-serif; font-weight: 800; font-size: 132px; line-height: 0.8;
            letter-spacing: -6px; color: transparent; -webkit-text-stroke: 1.5px rgba(252,176,23,.5);
            pointer-events: none; user-select: none;
        }
        .hero-top { position: absolute; top: 0; left: 0; right: 0; padding: calc(24px + env(safe-area-inset-top)) 24px 0; display: flex; align-items: center; justify-content: space-between; }
        .m-brand-row { display: flex; align-items: center; gap: 11px; margin-top: 16px; }
        .m-brand-row img { width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; }
        .m-brand-title { font-weight: 700; font-size: 14.5px; line-height: 1.1; color: #fff; }
        .m-brand-sub { font-weight: 700; font-size: 9px; line-height: 1.4; letter-spacing: 1.6px; color: #FCB017; }
        .theme-toggle-btn {
            width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.16); color: #fff; font-size: 14px;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            margin-top: 16px; flex-shrink: 0;
        }

        .sheet {
            position: absolute; left: 0; right: 0; bottom: 0; top: 342px;
            background: var(--m-sheet-bg); border-radius: 34px 34px 0 0; border-top: 1px solid var(--m-sheet-border);
            padding: 26px 26px calc(18px + env(safe-area-inset-bottom)); display: flex; flex-direction: column;
            box-shadow: 0 -26px 60px var(--m-sheet-shadow);
            overflow-y: auto;
        }
        .sheet-inner { margin: auto 0; width: 100%; }
        .sheet h1 { margin: 0 0 8px; font-weight: 700; font-size: 30px; line-height: 1.12; color: var(--m-heading); letter-spacing: -.6px; }
        .m-subcopy { margin: 0 0 24px; font-size: 14px; line-height: 1.5; color: var(--m-subcopy); }

        .sheet .alert-error {
            margin: 0 0 16px; padding: 12px 14px; border-radius: 12px;
            background: var(--m-error-bg); border: 1px solid var(--m-error-border);
            color: var(--m-error-text); font-size: 13px; display: flex; gap: 9px; align-items: flex-start;
        }

        .field-wrap {
            display: flex; align-items: center; gap: 11px; height: 56px; padding: 0 16px;
            border-radius: 16px; background: var(--m-field-bg); border: 1px solid var(--m-field-border);
            margin-bottom: 13px;
        }
        .field-wrap i { font-size: 15px; color: var(--m-field-icon); flex-shrink: 0; }
        .field-wrap input {
            flex: 1; background: none; border: none; outline: none; color: var(--m-field-text); font-size: 16px;
            font-family: 'Figtree', sans-serif; min-width: 0;
        }
        .field-wrap input::placeholder { color: var(--m-field-placeholder); }
        .field-wrap:focus-within { border-color: rgba(252,176,23,.4); }
        .toggle-pw {
            background: none; border: none; cursor: pointer; padding: 6px;
            font-family: 'Figtree', sans-serif; font-weight: 600; font-size: 11.5px; letter-spacing: .6px;
            color: var(--m-toggle-pw, #FCB017);
        }
        .sheet .field-error { color: var(--m-field-error); font-size: 12px; margin: -7px 0 13px; }

        .row-end { display: flex; justify-content: flex-end; margin: 14px 0 20px; }
        .sheet .forgot-link { font-weight: 500; font-size: 13.5px; color: var(--m-link); cursor: default; min-height: 44px; display: flex; align-items: center; }

        .sheet .btn-submit {
            width: 100%; height: 56px; border: none; border-radius: 16px; background: #FCB017; color: #0a0f18;
            font-family: 'Figtree', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 12px 30px rgba(252,176,23,.22);
        }
        .sheet .btn-submit:hover { background: #ffbe3d; filter: none; transform: none; }
        .sheet .btn-submit:active { transform: scale(.99); }
        .sheet .btn-submit:disabled { opacity: .65; cursor: not-allowed; }

        .divider { display: flex; align-items: center; gap: 12px; margin: 22px 0 16px; }
        .divider span.line { flex: 1; height: 1px; background: var(--m-divider-line); }
        .divider span.label { font-size: 12px; color: var(--m-divider-label); white-space: nowrap; }

        .social-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-social {
            height: 50px; border-radius: 14px; background: var(--m-social-bg); border: 1px solid var(--m-social-border);
            color: var(--m-social-text); font-family: 'Figtree', sans-serif; font-weight: 500; font-size: 14px; cursor: not-allowed;
            display: flex; align-items: center; justify-content: center; gap: 9px; opacity: .7;
        }
        .btn-social svg { width: 17px; height: 17px; flex-shrink: 0; }

        .legal { margin: 18px 0 12px; text-align: center; font-size: 11.5px; line-height: 1.5; color: var(--m-legal); }
        .legal span { color: var(--m-link); }
        .home-indicator { height: 5px; width: 134px; border-radius: 3px; background: var(--m-home-indicator); margin: 0 auto 10px; }

        /* ── LIGHT MODE — "2b" design handoff: frosted-glass card floating
             over the building photo, instead of dark mode's opaque navy
             sheet. Every rule below is scoped to [data-theme="light"] so
             dark mode (the original, unaffected) keeps its own look. ── */
        .photo-behind {
            position: absolute; left: 0; right: 0; top: 342px; bottom: 0; overflow: hidden;
            display: none; pointer-events: none;
        }
        :root[data-theme="light"] .photo-behind { display: block; }
        :root[data-theme="light"] .photo-behind::before {
            content: ''; position: absolute; left: 0; top: -114px; width: 100%; height: 520px;
            background: url('{{ asset('images/login-building.jpg') }}') 50% 78% / cover no-repeat;
            filter: brightness(1.25) saturate(.6) contrast(.9); opacity: .55;
        }
        :root[data-theme="light"] .photo-behind::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(246,244,240,.15), rgba(246,244,240,.5));
        }
        :root[data-theme="light"] .photo {
            filter: brightness(1.22) saturate(.72) contrast(.92);
        }
        :root[data-theme="light"] .photo-scrim {
            background: linear-gradient(180deg,
                rgba(14,20,32,.42) 0%, rgba(14,20,32,.06) 30%,
                rgba(246,244,240,.06) 55%, rgba(246,244,240,.35) 100%);
        }
        :root[data-theme="light"] .watermark { display: none; }
        :root[data-theme="light"] .sheet {
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        }
        /* Backdrop-filter fallback — where unsupported, a sharp photo behind
           unblurred glass is unreadable, so raise the fill instead of losing
           the blur silently. */
        @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
            :root[data-theme="light"] .sheet { --m-sheet-bg: rgba(255,255,255,.82); }
        }
        :root[data-theme="light"] .field-wrap:focus-within { background: rgba(255,255,255,.78); }
    }
</style>
</head>
<body>

<div class="desktop-shell">
    <div class="brand-panel">
        <div class="brand-logo">
            <img src="{{ asset('logo/promoseven-logo.png') }}" alt="Promoseven Holdings">
            <div class="brand-logo-text">
                Promoseven Holdings
                <span>Real Estate Division</span>
            </div>
        </div>

        <div class="brand-headline">Every building,<br>every tenant,<br><em>one ledger.</em></div>
        <div class="brand-sub">Buildings, leases, invoices, and reports — all in one place. Sign in to pick up where you left off.</div>

        <div class="brand-footer">&copy; {{ date('Y') }} Promoseven Holdings BSC</div>
    </div>

    <div class="form-panel">
        <div class="form-card">
            <div class="form-title">Welcome back</div>
            <div class="form-sub">Sign in to your account to continue.</div>

            @if ($errors->any())
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>{{ $errors->first() }}</div>
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label" for="login">Email or Username</label>
                    <input type="text" id="login" name="login" class="form-control {{ $errors->has('login') ? 'is-invalid' : '' }}"
                           value="{{ old('login') }}" placeholder="you@promoseven.com or username" required autofocus>
                    @error('login')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                           placeholder="••••••••" required>
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row" style="justify-content: space-between;">
                    <label class="remember-check">
                        <input type="checkbox" name="remember">
                        Keep me signed in
                    </label>
                    <span class="forgot-link" title="Password reset isn't available yet">Forgot password?</span>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign in
                </button>
            </form>
        </div>
    </div>
</div>

<div class="mobile-shell">
    <div class="phone">
        <div class="photo"></div>
        <div class="photo-scrim"></div>
        <div class="watermark">P7H</div>

        <div class="hero-top">
            <div class="m-brand-row">
                <img src="{{ asset('logo/promoseven-logo.png') }}" alt="Promoseven Holdings">
                <div>
                    <div class="m-brand-title">Promoseven Holdings</div>
                    <div class="m-brand-sub">REAL ESTATE DIVISION</div>
                </div>
            </div>
            <button type="button" class="theme-toggle-btn" id="loginThemeToggle" title="Switch theme" aria-label="Switch to light mode"><i class="fa-solid fa-sun"></i></button>
        </div>

        <div class="photo-behind"></div>

        <div class="sheet">
            <div class="sheet-inner">
            <h1>Welcome back.</h1>
            <p class="m-subcopy">Buildings, leases, invoices and reports — all in one place.</p>

            @if ($errors->any())
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px;"></i>
                <div>{{ $errors->first() }}</div>
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginFormMobile" novalidate>
                @csrf

                <div class="field-wrap">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" name="login" value="{{ old('login') }}"
                           placeholder="Email or username" required autocomplete="username">
                </div>
                @error('login')<div class="field-error">{{ $message }}</div>@enderror

                <div class="field-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="passwordMobile" name="password" placeholder="Password" required autocomplete="current-password">
                    <button type="button" class="toggle-pw" id="togglePwMobile">SHOW</button>
                </div>
                @error('password')<div class="field-error">{{ $message }}</div>@enderror

                <div class="row-end">
                    <span class="forgot-link" title="Password reset isn't available yet">Forgot password?</span>
                </div>

                <button type="submit" class="btn-submit" id="submitBtnMobile">
                    <span id="submitLabelMobile">Sign in</span>
                </button>
            </form>

            <div class="divider"><span class="line"></span><span class="label">or continue with</span><span class="line"></span></div>
            <div class="social-row">
                <button type="button" class="btn-social" disabled title="Not available yet">
                    <svg viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.6l6.7-6.7C35.6 2.6 30.2.5 24 .5 14.6.5 6.5 5.9 2.6 13.7l7.8 6.1C12.3 13.9 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8c4.4-4 7.1-10 7.1-17.5z"/><path fill="#FBBC05" d="M10.4 28.2a14.6 14.6 0 010-8.4l-7.8-6.1a24 24 0 000 20.6l7.8-6.1z"/><path fill="#34A853" d="M24 47.5c6.5 0 11.9-2.1 15.9-5.8l-7.5-5.8c-2.1 1.4-4.8 2.3-8.4 2.3-6.3 0-11.7-4.4-13.6-10.3l-7.8 6.1C6.5 42.1 14.6 47.5 24 47.5z"/></svg>
                    Google
                </button>
                <button type="button" class="btn-social" disabled title="Not available yet">
                    <svg viewBox="0 0 23 23"><path fill="#F25022" d="M1 1h10v10H1z"/><path fill="#7FBA00" d="M12 1h10v10H12z"/><path fill="#00A4EF" d="M1 12h10v10H1z"/><path fill="#FFB900" d="M12 12h10v10H12z"/></svg>
                    Microsoft
                </button>
            </div>

            <p class="legal">By signing in you agree to our <span>Terms</span> and <span>Privacy Policy</span>.</p>
            <div class="home-indicator"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePwMobile').addEventListener('click', function () {
    const input = document.getElementById('passwordMobile');
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    this.textContent = showing ? 'SHOW' : 'HIDE';
});
document.getElementById('loginFormMobile').addEventListener('submit', function () {
    document.getElementById('submitBtnMobile').disabled = true;
    document.getElementById('submitLabelMobile').textContent = 'Signing in…';
});

(function () {
    const btn = document.getElementById('loginThemeToggle');
    const icon = btn.querySelector('i');

    function syncIcon() {
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        icon.className = isLight ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
        btn.setAttribute('aria-label', isLight ? 'Switch to dark mode' : 'Switch to light mode');
    }
    syncIcon();

    btn.addEventListener('click', function () {
        const next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('p7-theme', next);
        syncIcon();
    });
})();
</script>

</body>
</html>
