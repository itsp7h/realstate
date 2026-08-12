<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — RealEstate Admin</title>
<script>
(function () {
    // Applied before first paint to avoid a flash of the wrong theme.
    var saved = localStorage.getItem('p7-theme');
    var theme = saved || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    document.documentElement.setAttribute('data-theme', theme);
})();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    :root, :root[data-theme="dark"] {
        --bg-dark:       #0B1020;
        --bg-dark-2:     #101625;
        --panel-line:    #1D2436;
        --input-bg:      #141A2A;
        --input-border:  #262E42;
        --input-focus:   #F5A623;
        --text-primary:  #F5F7FA;
        --text-secondary:#B7C0D1;
        --text-muted:    #7E8AA3;
        --accent:        #F5A623;
        --accent-2:      #3B6FF5;
        --danger:        #EF4444;
        --danger-bg:     rgba(239,68,68,0.12);
        --danger-border: rgba(239,68,68,0.35);
        --danger-text:   #FCA5A5;
        --radius:        12px;
        --radius-lg:     16px;
        --watermark-stroke: rgba(245,166,35,0.05);
        --theme-toggle-bg: rgba(255,255,255,0.06);
        --theme-toggle-border: rgba(255,255,255,0.12);
        --theme-toggle-fg: #B7C0D1;
    }

    /* ── Light mode — same layout/composition, light UI chrome on the
         left panel. The right-side skyline + dashboard preview stay
         identical in both modes since it's hero photography, not
         interface surface. ── */
    :root[data-theme="light"] {
        --bg-dark:       #FFFFFF;
        --bg-dark-2:     #F1F4F9;
        --panel-line:    #E4E9F0;
        --input-bg:      #F5F7FA;
        --input-border:  #DCE2EC;
        --input-focus:   #F5A623;
        --text-primary:  #0F172A;
        --text-secondary:#475569;
        --text-muted:    #8A94A6;
        --accent:        #F5A623;
        --accent-2:      #3B6FF5;
        --danger:        #DC2626;
        --danger-bg:     #FEF2F2;
        --danger-border: #FECACA;
        --danger-text:   #991B1B;
        --radius:        12px;
        --radius-lg:     16px;
        --watermark-stroke: rgba(15,23,42,0.045);
        --theme-toggle-bg: #F1F4F9;
        --theme-toggle-border: #E2E6EE;
        --theme-toggle-fg: #475569;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body { overflow-x: hidden; }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        min-height: 100vh;
        color: var(--text-primary);
        font-size: 14px;
        background: var(--bg-dark);
    }

    .login-page {
        position: relative;
        display: flex;
        width: 100%;
        min-height: 100vh;
    }

    /* ── LEFT — login panel ──────────────────────────────── */
    .login-left {
        flex: 0 0 50%;
        max-width: 50%;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        background: linear-gradient(165deg, var(--bg-dark) 0%, var(--bg-dark-2) 100%);
        padding: 56px 64px;
    }
    .login-left-watermark {
        position: absolute;
        top: -8%;
        right: -6%;
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 30vw;
        line-height: 1;
        color: transparent;
        -webkit-text-stroke: 1.5px var(--watermark-stroke);
        pointer-events: none;
        z-index: 0;
        user-select: none;
    }
    .login-left-inner {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 420px;
        margin: 0 auto;
    }

    .theme-toggle {
        position: absolute;
        top: 28px;
        right: 28px;
        z-index: 2;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--theme-toggle-bg);
        border: 1px solid var(--theme-toggle-border);
        color: var(--theme-toggle-fg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.15s, color 0.15s, transform 0.1s;
    }
    .theme-toggle:hover { color: var(--accent); }
    .theme-toggle:active { transform: scale(0.94); }
    .theme-toggle:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

    .brand-logo { display: flex; align-items: center; gap: 14px; }
    .brand-logo img { width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0; }
    .brand-logo-text { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary); line-height: 1.3; }
    .brand-logo-text span { display: block; font-size: 10.5px; font-weight: 600; color: var(--accent); letter-spacing: 0.08em; text-transform: uppercase; margin-top: 2px; }

    .brand-headline {
        font-family: 'Outfit', sans-serif;
        font-size: 36px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.2;
        margin-top: 40px;
    }
    .brand-headline em { font-style: normal; color: var(--accent); }
    .brand-sub {
        margin-top: 18px;
        font-size: 14.5px;
        color: var(--text-secondary);
        line-height: 1.65;
        max-width: 380px;
    }

    .alert-error {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: var(--danger-bg);
        border: 1px solid var(--danger-border);
        color: var(--danger-text);
        border-radius: var(--radius);
        padding: 12px 14px;
        font-size: 13px;
        margin-top: 32px;
        line-height: 1.5;
    }
    .alert-error i { margin-top: 2px; }

    .login-form { margin-top: 40px; }
    .form-group { margin-bottom: 20px; }
    .form-label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 8px;
    }
    .input-wrap { position: relative; display: flex; align-items: center; }
    .input-icon {
        position: absolute;
        left: 15px;
        color: var(--text-muted);
        font-size: 14px;
        pointer-events: none;
        transition: color 0.15s;
    }
    .input-wrap:focus-within .input-icon { color: var(--accent); }
    .form-control {
        width: 100%;
        height: 50px;
        padding: 0 14px 0 42px;
        font-size: 14px;
        font-family: inherit;
        border: 1.5px solid var(--input-border);
        border-radius: var(--radius);
        background: var(--input-bg);
        color: var(--text-primary);
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .form-control::placeholder { color: var(--text-muted); }
    .form-control:focus { border-color: var(--input-focus); box-shadow: 0 0 0 3px rgba(245,166,35,0.16); }
    .form-control.is-invalid { border-color: var(--danger); }
    .form-control.has-toggle { padding-right: 44px; }
    .field-error { color: var(--danger-text); font-size: 12px; margin-top: 6px; }

    .toggle-visibility {
        position: absolute;
        right: 14px;
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 14px;
        padding: 4px;
        display: flex;
        align-items: center;
        transition: color 0.15s;
    }
    .toggle-visibility:hover { color: var(--text-secondary); }
    .toggle-visibility:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 4px; }

    .form-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 22px 0 28px;
    }
    .remember-check {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 13.5px;
        color: var(--text-secondary);
        cursor: pointer;
        user-select: none;
    }
    .remember-check input { accent-color: var(--accent); width: 16px; height: 16px; cursor: pointer; }
    .forgot-link {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--accent-2);
        text-decoration: none;
        cursor: default;
    }

    .btn-submit {
        width: 100%;
        height: 52px;
        background: var(--accent);
        color: #241705;
        border: none;
        border-radius: var(--radius);
        font-family: 'Outfit', sans-serif;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: filter 0.15s, transform 0.1s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
    }
    .btn-submit:hover { filter: brightness(1.07); }
    .btn-submit:active { transform: scale(0.99); }
    .btn-submit:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
    .btn-submit:disabled { opacity: 0.65; cursor: not-allowed; filter: none; }

    .login-footer {
        margin-top: 36px;
        font-size: 11.5px;
        color: var(--text-muted);
        line-height: 1.6;
    }
    .login-footer a { color: var(--text-secondary); text-decoration: underline; cursor: default; }

    /* ── RIGHT — building showcase (desktop only) ────────── */
    .login-right {
        flex: 1;
        position: relative;
        overflow: hidden;
        background: #0A0E1A url('{{ asset('images/login-building.jpg') }}') center center / cover no-repeat;
    }
    .skyline-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(100deg, rgba(6,9,18,0.55) 0%, rgba(6,9,18,0.15) 30%, rgba(6,9,18,0) 55%);
    }

    /* ── Social auth (secondary, quiet — no OAuth exists yet,
         so these are disabled rather than fake-functional) ── */
    .social-divider { display: flex; align-items: center; gap: 12px; margin: 28px 0; }
    .social-divider::before, .social-divider::after { content: ''; flex: 1; height: 1px; background: var(--panel-line); }
    .social-divider span { font-size: 12px; color: var(--text-muted); white-space: nowrap; }
    .social-row { display: flex; gap: 12px; }
    .btn-social {
        flex: 1;
        height: 48px;
        border-radius: var(--radius);
        border: 1.5px solid var(--input-border);
        background: var(--input-bg);
        color: var(--text-secondary);
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 13.5px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .btn-social svg { width: 17px; height: 17px; flex-shrink: 0; }

    /* ── Responsive ───────────────────────────────────────── */
    @media (max-width: 1180px) {
        .login-left { flex: 0 0 54%; max-width: 54%; padding: 48px; }
        .login-right { flex: 0 0 46%; }
    }
    @media (max-width: 900px) {
        .login-left {
            flex: 0 0 100%; max-width: 100%;
            background: linear-gradient(180deg, rgba(5,7,12,.75) 0%, rgba(5,7,12,.55) 40%, rgba(5,7,12,.92) 78%, rgba(5,7,12,.98) 100%),
                        url('{{ asset('images/login-building.jpg') }}') center 20% / cover no-repeat;
        }
        .login-right { display: none; }
        .login-left-watermark { display: none; }
    }
    @media (max-width: 560px) {
        .login-left { padding: 40px 24px; align-items: flex-start; }
        .login-left-inner { max-width: 100%; margin: 0; padding-top: 12px; }
        .brand-headline { font-size: 28px; margin-top: 32px; }
        .login-form { margin-top: 32px; }
        .form-row { margin: 18px 0 22px; }
    }
</style>
</head>
<body>

<div class="login-page">

    <div class="login-left">
        <div class="login-left-watermark">P7</div>

        <button type="button" class="theme-toggle" id="themeToggle" aria-label="Switch to light mode" title="Switch theme">
            <i class="fa-solid fa-sun"></i>
        </button>

        <div class="login-left-inner">
            <div class="brand-logo">
                <img src="{{ asset('logo/promoseven-logo.png') }}" alt="Promoseven Holdings">
                <div class="brand-logo-text">
                    Promoseven Holdings
                    <span>Real Estate Division</span>
                </div>
            </div>

            <div class="brand-headline">Every building,<br>every tenant,<br><em>one ledger.</em></div>
            <div class="brand-sub">Buildings, leases, invoices, and reports — all in one place. Sign in to pick up where you left off.</div>

            @if ($errors->any())
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>{{ $errors->first() }}</div>
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="login-form" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label" for="login">Email or Username</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-user input-icon"></i>
                        <input type="text" id="login" name="login" class="form-control {{ $errors->has('login') ? 'is-invalid' : '' }}"
                               value="{{ old('login') }}" placeholder="you@promoseven.com or username" required autofocus autocomplete="username">
                    </div>
                    @error('login')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control has-toggle {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="toggle-visibility" id="togglePassword" aria-label="Show password">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <label class="remember-check">
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>
                    <span class="forgot-link" title="Password reset isn't available yet">Forgot password?</span>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span id="submitLabel">Sign in</span>
                    <i class="fa-solid fa-arrow-right" id="submitIcon"></i>
                </button>
            </form>

            <div class="social-divider"><span>or continue with</span></div>
            <div class="social-row">
                <button type="button" class="btn-social" disabled title="Not available yet">
                    <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M23.52 12.27c0-.82-.07-1.6-.2-2.36H12v4.47h6.47c-.28 1.5-1.13 2.77-2.42 3.62v3.01h3.9c2.28-2.1 3.57-5.2 3.57-8.74z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.95-2.9l-3.9-3c-1.08.73-2.46 1.16-4.05 1.16-3.11 0-5.75-2.1-6.69-4.92H1.24v3.09C3.22 21.3 7.28 24 12 24z"/><path fill="#FBBC05" d="M5.31 14.34A7.24 7.24 0 0 1 4.9 12c0-.81.14-1.6.4-2.34V6.57H1.24A11.96 11.96 0 0 0 0 12c0 1.93.46 3.76 1.24 5.43l4.07-3.09z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.45-3.45C17.94 1.19 15.24 0 12 0 7.28 0 3.22 2.7 1.24 6.57l4.07 3.09C6.25 6.85 8.89 4.75 12 4.75z"/></svg>
                    Google
                </button>
                <button type="button" class="btn-social" disabled title="Not available yet">
                    <svg viewBox="0 0 24 24"><path fill="#F25022" d="M1 1h10.5v10.5H1z"/><path fill="#7FBA00" d="M12.5 1H23v10.5H12.5z"/><path fill="#00A4EF" d="M1 12.5h10.5V23H1z"/><path fill="#FFB900" d="M12.5 12.5H23V23H12.5z"/></svg>
                    Microsoft
                </button>
            </div>

            <div class="login-footer">
                By signing in, you agree to our <a>Terms of Service</a> and <a>Privacy Policy</a>.
            </div>
        </div>
    </div>

    <div class="login-right">
        <div class="skyline-overlay"></div>
    </div>

</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon = this.querySelector('i');
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    icon.classList.toggle('fa-eye', showing);
    icon.classList.toggle('fa-eye-slash', !showing);
    this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
});

document.querySelector('.login-form').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    document.getElementById('submitLabel').textContent = 'Signing in…';
    document.getElementById('submitIcon').className = 'fa-solid fa-spinner fa-spin';
});

/* ── Theme toggle ─────────────────────────────────────── */
(function () {
    const btn = document.getElementById('themeToggle');
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
