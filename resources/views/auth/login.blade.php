<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — RealEstate Admin</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
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
        display: flex;
        color: var(--text-primary);
        font-size: 14px;
    }

    /* ── LEFT PANEL — brand ─────────────────────────────── */
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

    /* ── RIGHT PANEL — form ──────────────────────────────── */
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
        body { flex-direction: column; }
        .brand-panel { flex: 0 0 auto; padding: 40px 32px; }
        .brand-headline { font-size: 26px; margin-top: 28px; }
        .brand-footer { display: none; }
        .form-panel { padding: 40px 24px 60px; }
    }

    /* ── Mobile "Midnight" login (Promoseven RE mobile spec) ──────── */
    @media (max-width: 768px) {
        :root {
            --p7-gold:        #E7B266;
            --p7-gold-deep:   #DC9E45;
            --p7-gold-light:  #EDBE78;
            --p7-gold-text:   #D99A3D;
            --p7-btn-text:    #2A2312;
            --p7-bg-dark:     #161512;
            --p7-input-bg:    rgba(13,16,26,.6);
            --p7-input-border:#33394E;
            --p7-text-body:   #C6D0E2;
            --p7-text-muted:  #8B99B5;
            --p7-text-btn2:   #C9CFDC;
        }

        @keyframes p7GlowPulse {
            from { opacity: 0.75; transform: scale(1); }
            to   { opacity: 1;    transform: scale(1.08); }
        }
        @keyframes p7WatermarkDrift {
            from { transform: translate(0, 0) rotate(0deg); }
            to   { transform: translate(6px, -10px) rotate(0.6deg); }
        }
        @keyframes p7FadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            body::before, body::after { animation: none !important; }
            .brand-logo, .brand-headline, .brand-sub, .form-group, .form-row, .m-login-buttons { animation: none !important; opacity: 1 !important; transform: none !important; }
        }

        body {
            flex-direction: column;
            background: var(--p7-bg-dark);
            font-family: 'Poppins', sans-serif;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background: radial-gradient(ellipse 70% 50% at 50% 38%, rgba(90,72,45,.30), transparent 70%);
            animation: p7GlowPulse 6s ease-in-out infinite alternate;
        }
        body::after {
            content: 'P7';
            position: fixed; top: -40px; left: -60px; z-index: 0; pointer-events: none;
            font-size: 340px; font-weight: 800; font-family: 'Poppins', sans-serif;
            color: transparent; -webkit-text-stroke: 2px rgba(231,178,102,.14);
            line-height: 1;
            animation: p7WatermarkDrift 14s ease-in-out infinite alternate;
        }
        .brand-panel, .form-panel { position: relative; z-index: 1; }

        .brand-logo, .brand-headline, .brand-sub, .form-group, .form-row, .m-login-buttons {
            opacity: 0;
            animation: p7FadeUp 0.6s cubic-bezier(.22,1,.36,1) forwards;
        }
        .brand-logo       { animation-delay: 0.05s; }
        .brand-headline    { animation-delay: 0.12s; }
        .brand-sub         { animation-delay: 0.20s; }
        .form-group:nth-of-type(1) { animation-delay: 0.28s; }
        .form-group:nth-of-type(2) { animation-delay: 0.34s; }
        .form-row          { animation-delay: 0.40s; }
        .m-login-buttons   { animation-delay: 0.46s; }

        .brand-panel { flex: 0 0 auto; padding: 74px 26px 12px; background: none; }
        .brand-panel::before { display: none; }
        .brand-logo img { width: 46px; height: 46px; border-radius: 50%; }
        .brand-logo-text { font-size: 15px; }
        .brand-logo-text span { color: var(--p7-gold-text); font-size: 10px; letter-spacing: 1.5px; }
        .brand-headline { font-size: 32px; margin-top: 24px; font-family: 'Poppins', sans-serif; line-height: 1.12; }
        .brand-headline em { color: var(--p7-gold); }
        .brand-sub { font-size: 13px; color: var(--p7-text-muted); }
        .brand-footer { display: none; }

        .form-panel { flex: 1; background: none; padding: 4px 26px 44px; align-items: stretch; justify-content: flex-start; }
        .form-card { display: flex; flex-direction: column; height: 100%; }
        .form-card form { display: flex; flex-direction: column; flex: 1; }
        .form-title, .form-sub { display: none; }
        .form-label { color: var(--p7-text-body); font-family: 'Poppins', sans-serif; font-size: 12.5px; font-weight: 600; }
        .form-control {
            height: 52px;
            min-height: 44px;
            border: 1.5px solid var(--p7-input-border);
            border-radius: 13px;
            background: var(--p7-input-bg);
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        .form-control:focus { border-color: var(--p7-gold); }
        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:hover,
        .form-control:-webkit-autofill:focus {
            -webkit-text-fill-color: #fff;
            -webkit-box-shadow: 0 0 0 1000px var(--p7-input-bg) inset;
            box-shadow: 0 0 0 1000px var(--p7-input-bg) inset;
            border-color: var(--p7-input-border);
            caret-color: #fff;
            transition: background-color 5000s ease-in-out 0s;
        }
        .form-row { justify-content: space-between; min-height: 44px; }
        .remember-check { color: var(--p7-text-muted); font-family: 'Poppins', sans-serif; }
        .remember-check input { accent-color: var(--p7-gold-text); }
        .forgot-link {
            color: var(--p7-gold); font-weight: 600; font-size: 13px;
            text-decoration: none; min-height: 44px; display: flex; align-items: center;
        }
        .btn-submit {
            height: 54px;
            min-height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--p7-gold-light), var(--p7-gold-deep));
            color: var(--p7-btn-text);
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 8px 22px rgba(231,178,102,.35);
        }
        .btn-submit:active, .btn-secondary:active { transform: scale(.98); }
        .btn-secondary {
            height: 54px;
            min-height: 44px;
            border-radius: 14px;
            background: transparent;
            border: 1.5px solid var(--p7-input-border);
            color: var(--p7-text-btn2);
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 14px;
            width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 9px;
            cursor: pointer;
        }
        .m-login-buttons { margin-top: auto; display: flex; flex-direction: column; gap: 12px; }
        .alert-error { background: #2A1414; border-color: #4A2020; color: #F0A3A3; }
    }
    @media (min-width: 769px) {
        .forgot-link, .btn-secondary { display: none; }
    }
</style>
</head>
<body>

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

            <div class="form-row">
                <label class="remember-check">
                    <input type="checkbox" name="remember">
                    Keep me signed in
                </label>
                <span class="forgot-link" title="Password reset isn't available yet">Forgot?</span>
            </div>

            <div class="m-login-buttons">
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign in
                </button>
                <button type="button" class="btn-secondary" disabled title="Not available yet" style="opacity:0.6;cursor:not-allowed;">
                    <i class="fa-solid fa-face-smile"></i> Sign in with Face ID
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
