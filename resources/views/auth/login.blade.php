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
        --radius:        12px;
        --radius-lg:     16px;
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
        -webkit-text-stroke: 1.5px rgba(245,166,35,0.05);
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

    .brand-logo { display: flex; align-items: center; gap: 14px; }
    .brand-logo img { width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0; }
    .brand-logo-text { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: #FFFFFF; line-height: 1.3; }
    .brand-logo-text span { display: block; font-size: 10.5px; font-weight: 600; color: var(--accent); letter-spacing: 0.08em; text-transform: uppercase; margin-top: 2px; }

    .brand-headline {
        font-family: 'Outfit', sans-serif;
        font-size: 36px;
        font-weight: 800;
        color: #FFFFFF;
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
        color: #FCA5A5;
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
    .field-error { color: #FCA5A5; font-size: 12px; margin-top: 6px; }

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

    /* ── RIGHT — showcase (desktop only) ─────────────────── */
    .login-right {
        flex: 1;
        position: relative;
        overflow: hidden;
        background: var(--bg-dark);
    }
    .skyline-svg { position: absolute; inset: 0; width: 100%; height: 100%; }
    .skyline-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(115deg, rgba(11,16,32,0.94) 0%, rgba(11,16,32,0.55) 38%, rgba(20,32,64,0.28) 65%, rgba(20,32,64,0.15) 100%);
    }

    /* Decorative dashboard preview — static mock data only, never wired
       to real app state, since this renders on the public login page. */
    .dash-preview {
        position: absolute;
        left: 43%;
        bottom: 8%;
        z-index: 2;
        width: 560px;
        max-width: 50vw;
        background: #0F1524;
        border: 1px solid #232B40;
        border-radius: var(--radius-lg);
        box-shadow: 0 30px 70px rgba(0,0,0,0.5);
        overflow: hidden;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .dash-preview-top {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 18px;
        border-bottom: 1px solid #1E2536;
    }
    .dash-preview-logo { width: 24px; height: 24px; border-radius: 6px; flex-shrink: 0; }
    .dash-preview-brand { font-size: 12.5px; font-weight: 700; color: #fff; }
    .dash-preview-title { font-size: 12px; font-weight: 600; color: #B7C0D1; margin-left: 14px; flex: 1; }
    .dash-preview-icon { width: 26px; height: 26px; border-radius: 7px; background: #1B2233; display: flex; align-items: center; justify-content: center; color: #8B99B5; font-size: 10.5px; }
    .dash-preview-avatar { width: 26px; height: 26px; border-radius: 50%; background: var(--accent); color: #241705; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; }

    .dash-preview-body { display: flex; }
    .dash-preview-nav { width: 118px; flex-shrink: 0; padding: 12px 8px; border-right: 1px solid #1E2536; display: flex; flex-direction: column; gap: 2px; }
    .dash-preview-nav-item { display: flex; align-items: center; gap: 8px; padding: 7px 8px; border-radius: 7px; font-size: 10px; font-weight: 600; color: #7E8AA3; }
    .dash-preview-nav-item i { width: 12px; font-size: 10px; }
    .dash-preview-nav-item.active { background: rgba(245,166,35,0.14); color: var(--accent); }

    .dash-preview-main { flex: 1; padding: 14px 16px; min-width: 0; }
    .dash-preview-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 12px; }
    .dash-preview-stat { background: #141B2C; border: 1px solid #1E2536; border-radius: 9px; padding: 8px 10px; }
    .dash-preview-stat-label { font-size: 8.5px; font-weight: 600; color: #7E8AA3; text-transform: uppercase; letter-spacing: 0.04em; }
    .dash-preview-stat-value { font-size: 14px; font-weight: 800; color: #fff; font-family: 'Outfit', sans-serif; margin-top: 2px; }

    .dash-preview-panels { display: grid; grid-template-columns: 1.4fr 1fr; gap: 10px; }
    .dash-preview-panel { background: #141B2C; border: 1px solid #1E2536; border-radius: 9px; padding: 10px 12px; }
    .dash-preview-panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .dash-preview-panel-title { font-size: 10px; font-weight: 700; color: #E3E7EF; }
    .dash-preview-panel-chip { font-size: 8px; font-weight: 600; color: #7E8AA3; background: #1B2233; padding: 2px 6px; border-radius: 5px; }

    .dash-activity-row { display: flex; align-items: flex-start; gap: 7px; margin-bottom: 8px; }
    .dash-activity-row:last-child { margin-bottom: 0; }
    .dash-activity-dot { width: 16px; height: 16px; border-radius: 50%; background: rgba(59,111,245,0.16); color: var(--accent-2); display: flex; align-items: center; justify-content: center; font-size: 7px; flex-shrink: 0; margin-top: 1px; }
    .dash-activity-text { font-size: 9px; font-weight: 700; color: #E3E7EF; line-height: 1.3; }
    .dash-activity-sub { font-size: 8px; color: #7E8AA3; margin-top: 1px; }

    /* ── Responsive ───────────────────────────────────────── */
    @media (max-width: 1180px) {
        .login-left { flex: 0 0 54%; max-width: 54%; padding: 48px; }
        .login-right { flex: 0 0 46%; }
        .dash-preview { width: 420px; left: auto; right: 3%; bottom: 6%; max-width: 44vw; }
        .dash-preview-nav { display: none; }
    }
    @media (max-width: 900px) {
        .login-left { flex: 0 0 100%; max-width: 100%; }
        .login-right, .dash-preview { display: none; }
        .login-left-watermark { font-size: 60vw; top: -4%; right: -18%; }
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

            <div class="login-footer">
                By signing in, you agree to our <a>Terms of Service</a> and <a>Privacy Policy</a>.
            </div>
        </div>
    </div>

    <div class="login-right">
        @php
            // Deterministic-looking but not identical every load — purely decorative,
            // this whole panel is static marketing chrome and never touches real data
            // (it renders on the public, pre-auth login page).
            $litSeed = 17;
        @endphp
        <svg class="skyline-svg" viewBox="0 0 1000 1000" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="skyGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#241B3D"/>
                    <stop offset="45%" stop-color="#3B2E52"/>
                    <stop offset="75%" stop-color="#7A4B58"/>
                    <stop offset="100%" stop-color="#B9714A"/>
                </linearGradient>
                <linearGradient id="towerGrad" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="#0F1626"/>
                    <stop offset="55%" stop-color="#1B2740"/>
                    <stop offset="100%" stop-color="#101827"/>
                </linearGradient>
                <linearGradient id="towerGradB" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="#0B1120"/>
                    <stop offset="100%" stop-color="#161F33"/>
                </linearGradient>
            </defs>
            <rect x="0" y="0" width="1000" height="1000" fill="url(#skyGrad)"/>

            {{-- Back tower --}}
            <rect x="640" y="120" width="230" height="880" fill="url(#towerGradB)"/>
            {{-- Main tower --}}
            <rect x="330" y="40" width="340" height="960" fill="url(#towerGrad)"/>
            {{-- Foreground low block --}}
            <rect x="60" y="560" width="300" height="440" fill="#0C1220"/>

            {{-- Windows: main tower --}}
            @for ($row = 0; $row < 32; $row++)
                @for ($col = 0; $col < 9; $col++)
                    @php
                        $x = 344 + $col * 36;
                        $y = 64 + $row * 28;
                        $lit = (($row * 9 + $col + $litSeed) * 7) % 11 < 3;
                    @endphp
                    <rect x="{{ $x }}" y="{{ $y }}" width="20" height="16" rx="1"
                          fill="{{ $lit ? '#F3B25C' : '#233250' }}"
                          opacity="{{ $lit ? '0.85' : '0.55' }}"/>
                @endfor
            @endfor

            {{-- Windows: back tower --}}
            @for ($row = 0; $row < 26; $row++)
                @for ($col = 0; $col < 6; $col++)
                    @php
                        $x = 656 + $col * 34;
                        $y = 140 + $row * 28;
                        $lit = (($row * 6 + $col + $litSeed) * 5) % 13 < 2;
                    @endphp
                    <rect x="{{ $x }}" y="{{ $y }}" width="18" height="15" rx="1"
                          fill="{{ $lit ? '#E9A867' : '#1B2740' }}"
                          opacity="{{ $lit ? '0.7' : '0.5' }}"/>
                @endfor
            @endfor
        </svg>
        <div class="skyline-overlay"></div>
    </div>

    <div class="dash-preview">
            <div class="dash-preview-top">
                <img src="{{ asset('logo/promoseven-logo.png') }}" alt="" class="dash-preview-logo">
                <span class="dash-preview-brand">Promoseven</span>
                <span class="dash-preview-title"><i class="fa-solid fa-bars" style="margin-right:6px;"></i>Dashboard</span>
                <span class="dash-preview-icon"><i class="fa-regular fa-bell"></i></span>
                <span class="dash-preview-avatar" style="margin-left:8px;">A</span>
            </div>
            <div class="dash-preview-body">
                <div class="dash-preview-nav">
                    <div class="dash-preview-nav-item active"><i class="fa-solid fa-gauge-high"></i> Dashboard</div>
                    <div class="dash-preview-nav-item"><i class="fa-solid fa-building"></i> Buildings</div>
                    <div class="dash-preview-nav-item"><i class="fa-solid fa-users"></i> Tenants</div>
                    <div class="dash-preview-nav-item"><i class="fa-solid fa-file-contract"></i> Leases</div>
                    <div class="dash-preview-nav-item"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</div>
                    <div class="dash-preview-nav-item"><i class="fa-solid fa-chart-bar"></i> Reports</div>
                </div>
                <div class="dash-preview-main">
                    <div class="dash-preview-stats">
                        <div class="dash-preview-stat">
                            <div class="dash-preview-stat-label">Total Buildings</div>
                            <div class="dash-preview-stat-value">27</div>
                        </div>
                        <div class="dash-preview-stat">
                            <div class="dash-preview-stat-label">Active Tenants</div>
                            <div class="dash-preview-stat-value">186</div>
                        </div>
                        <div class="dash-preview-stat">
                            <div class="dash-preview-stat-label">Outstanding Invoices</div>
                            <div class="dash-preview-stat-value">$124,850</div>
                        </div>
                        <div class="dash-preview-stat">
                            <div class="dash-preview-stat-label">Monthly Revenue</div>
                            <div class="dash-preview-stat-value">$98,420</div>
                        </div>
                    </div>
                    <div class="dash-preview-panels">
                        <div class="dash-preview-panel">
                            <div class="dash-preview-panel-head">
                                <span class="dash-preview-panel-title">Revenue Overview</span>
                                <span class="dash-preview-panel-chip">This Month</span>
                            </div>
                            <svg viewBox="0 0 220 70" width="100%" height="56" preserveAspectRatio="none">
                                <polyline points="0,58 25,50 50,54 75,40 100,44 125,30 150,34 175,18 200,22 220,10"
                                          fill="none" stroke="#3B6FF5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="0,58 25,50 50,54 75,40 100,44 125,30 150,34 175,18 200,22 220,10 220,70 0,70"
                                          fill="rgba(59,111,245,0.12)" stroke="none"/>
                            </svg>
                        </div>
                        <div class="dash-preview-panel">
                            <div class="dash-preview-panel-head">
                                <span class="dash-preview-panel-title">Recent Activity</span>
                            </div>
                            <div class="dash-activity-row">
                                <span class="dash-activity-dot"><i class="fa-solid fa-file-contract"></i></span>
                                <div><div class="dash-activity-text">New lease signed</div><div class="dash-activity-sub">2h ago</div></div>
                            </div>
                            <div class="dash-activity-row">
                                <span class="dash-activity-dot"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                                <div><div class="dash-activity-text">Invoice paid</div><div class="dash-activity-sub">5h ago</div></div>
                            </div>
                            <div class="dash-activity-row">
                                <span class="dash-activity-dot"><i class="fa-solid fa-user-plus"></i></span>
                                <div><div class="dash-activity-text">New tenant added</div><div class="dash-activity-sub">1d ago</div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
</script>

</body>
</html>
