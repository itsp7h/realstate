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

    @media (max-width: 860px) {
        body { flex-direction: column; }
        .brand-panel { flex: 0 0 auto; padding: 40px 32px; }
        .brand-headline { font-size: 26px; margin-top: 28px; }
        .brand-footer { display: none; }
        .form-panel { padding: 40px 24px 60px; }
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
                <label class="form-label" for="email">Email address</label>
                <input type="email" id="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                       value="{{ old('email') }}" placeholder="you@promoseven.com" required autofocus>
                @error('email')
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
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign in
            </button>
        </form>
    </div>
</div>

</body>
</html>
