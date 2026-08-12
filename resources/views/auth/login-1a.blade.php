<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Preview 1a — Full-bleed photo, bottom-anchored form</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { overflow: hidden; height: 100%; }
    body {
        font-family: 'Figtree', sans-serif;
        background: #060b14;
        display: flex;
        justify-content: center;
    }
    .phone {
        position: relative;
        width: 100%;
        max-width: 420px;
        height: 100vh;
        overflow: hidden;
        background: #0a0f18;
    }
    .photo {
        position: absolute; inset: 0;
        background: url('{{ asset('images/login-building.jpg') }}') 60% 35% / cover no-repeat;
    }
    .scrim {
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(6,11,20,.72) 0%, rgba(6,11,20,.55) 26%, rgba(6,11,20,.88) 58%, #060b14 82%);
    }
    .watermark {
        position: absolute; left: -14px; top: 108px;
        font-family: 'Figtree', sans-serif; font-weight: 800; font-size: 232px; line-height: 0.8;
        letter-spacing: -12px; color: rgba(255,255,255,.055);
        pointer-events: none; user-select: none;
    }
    .content {
        position: absolute; inset: 0;
        display: flex; flex-direction: column;
        padding: 34px 26px 34px;
        overflow-y: auto;
    }
    .brand-row { display: flex; align-items: center; gap: 11px; }
    .brand-row img { width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0; }
    .brand-title { font-weight: 700; font-size: 15px; line-height: 1.1; color: #fff; }
    .brand-sub { font-weight: 700; font-size: 9.5px; line-height: 1.4; letter-spacing: 1.6px; color: #FCB017; }
    .spacer { flex: 1; }
    h1 {
        margin: 0 0 10px; font-weight: 700; font-size: 38px; line-height: 1.06;
        color: #fff; letter-spacing: -1px;
    }
    h1 span { color: #FCB017; }
    .subcopy { margin: 0 0 26px; font-size: 14.5px; line-height: 1.5; color: rgba(255,255,255,.62); max-width: 300px; }

    .alert-error {
        margin: 0 0 18px; padding: 12px 14px; border-radius: 12px;
        background: rgba(239,68,68,.14); border: 1px solid rgba(239,68,68,.3);
        color: #FCA5A5; font-size: 13px; display: flex; gap: 9px; align-items: flex-start;
    }

    .field-label { display: block; font-weight: 500; font-size: 12.5px; color: rgba(255,255,255,.7); margin-bottom: 7px; }
    .field-wrap {
        display: flex; align-items: center; gap: 11px; height: 54px; padding: 0 16px;
        border-radius: 14px; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.13);
        backdrop-filter: blur(14px); margin-bottom: 16px;
    }
    .field-wrap i { font-size: 15px; color: rgba(255,255,255,.55); flex-shrink: 0; }
    .field-wrap input {
        flex: 1; background: none; border: none; outline: none; color: #fff; font-size: 15px;
        font-family: 'Figtree', sans-serif; min-width: 0;
    }
    .field-wrap input::placeholder { color: rgba(255,255,255,.38); }
    .field-wrap.pw input { letter-spacing: 1px; }
    .field-wrap:focus-within { border-color: rgba(252,176,23,.4); }
    .toggle-pw {
        background: none; border: none; cursor: pointer; padding: 6px;
        font-family: 'Figtree', sans-serif; font-weight: 600; font-size: 11.5px; letter-spacing: .6px; color: #FCB017;
    }
    .field-error { color: #FCA5A5; font-size: 12px; margin: -10px 0 14px; }

    .row-between { display: flex; align-items: center; justify-content: space-between; margin: 18px 0 22px; }
    .check-wrap { display: flex; align-items: center; gap: 9px; cursor: pointer; user-select: none; min-height: 44px; }
    .check-wrap input { position: absolute; opacity: 0; width: 19px; height: 19px; }
    .check-box {
        width: 19px; height: 19px; border-radius: 6px; border: 1.5px solid rgba(255,255,255,.35);
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background .15s, border-color .15s;
    }
    .check-box i { font-size: 10px; color: #0a0f18; opacity: 0; }
    .check-wrap input:checked + .check-box { background: #FCB017; border-color: #FCB017; }
    .check-wrap input:checked + .check-box i { opacity: 1; }
    .check-wrap span.label { font-size: 13.5px; color: rgba(255,255,255,.75); }
    .forgot-link { font-weight: 500; font-size: 13.5px; color: #6FA8F5; cursor: default; min-height: 44px; display: flex; align-items: center; }

    .btn-submit {
        width: 100%; height: 56px; border: none; border-radius: 14px; background: #FCB017; color: #0a0f18;
        font-family: 'Figtree', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        box-shadow: 0 10px 26px rgba(252,176,23,.28);
    }
    .btn-submit:hover { background: #ffbe3d; }
    .btn-submit:active { transform: scale(.99); }
    .btn-submit:disabled { opacity: .65; cursor: not-allowed; }

    .divider { display: flex; align-items: center; gap: 12px; margin: 20px 0 16px; }
    .divider span.line { flex: 1; height: 1px; background: rgba(255,255,255,.14); }
    .divider span.label { font-size: 12px; color: rgba(255,255,255,.45); white-space: nowrap; }

    .social-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .btn-social {
        height: 50px; border-radius: 13px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.14);
        color: #fff; font-family: 'Figtree', sans-serif; font-weight: 500; font-size: 14px; cursor: not-allowed;
        display: flex; align-items: center; justify-content: center; gap: 9px; opacity: .7;
    }
    .btn-social svg { width: 17px; height: 17px; flex-shrink: 0; }

    .home-indicator { height: 5px; width: 134px; border-radius: 3px; background: rgba(255,255,255,.3); margin: 24px auto 0; }

    @media (min-width: 421px) {
        html, body { height: auto; overflow: auto; }
        body { padding: 24px 0; align-items: center; }
        .phone { height: 844px; border-radius: 38px; box-shadow: 0 18px 50px rgba(0,0,0,.35); }
    }
</style>
</head>
<body>

<div class="phone">
    <div class="photo"></div>
    <div class="scrim"></div>
    <div class="watermark">P7H</div>

    <div class="content">
        <div class="brand-row">
            <img src="{{ asset('logo/promoseven-logo.png') }}" alt="Promoseven Holdings">
            <div>
                <div class="brand-title">Promoseven Holdings</div>
                <div class="brand-sub">REAL ESTATE DIVISION</div>
            </div>
        </div>

        <div class="spacer"></div>

        <h1>Every building,<br>every tenant,<br><span>one ledger.</span></h1>
        <p class="subcopy">Sign in to pick up where you left off.</p>

        @if ($errors->any())
        <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px;"></i>
            <div>{{ $errors->first() }}</div>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="loginForm1a" novalidate>
            @csrf

            <label class="field-label" for="login1a">Email or Username</label>
            <div class="field-wrap">
                <i class="fa-regular fa-user"></i>
                <input type="text" id="login1a" name="login" value="{{ old('login') }}"
                       placeholder="you@promoseven.com or username" required autofocus autocomplete="username">
            </div>
            @error('login')<div class="field-error">{{ $message }}</div>@enderror

            <label class="field-label" for="password1a">Password</label>
            <div class="field-wrap pw">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password1a" name="password" placeholder="••••••••" required autocomplete="current-password">
                <button type="button" class="toggle-pw" id="togglePw1a">SHOW</button>
            </div>
            @error('password')<div class="field-error">{{ $message }}</div>@enderror

            <div class="row-between">
                <label class="check-wrap">
                    <input type="checkbox" name="remember" checked>
                    <span class="check-box"><i class="fa-solid fa-check"></i></span>
                    <span class="label">Remember me</span>
                </label>
                <span class="forgot-link" title="Password reset isn't available yet">Forgot password?</span>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn1a">
                <span id="submitLabel1a">Sign in</span>
                <i class="fa-solid fa-arrow-right" id="submitIcon1a"></i>
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

        <div class="home-indicator"></div>
    </div>
</div>

<script>
document.getElementById('togglePw1a').addEventListener('click', function () {
    const input = document.getElementById('password1a');
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    this.textContent = showing ? 'SHOW' : 'HIDE';
});
document.getElementById('loginForm1a').addEventListener('submit', function () {
    document.getElementById('submitBtn1a').disabled = true;
    document.getElementById('submitLabel1a').textContent = 'Signing in…';
    document.getElementById('submitIcon1a').className = 'fa-solid fa-spinner fa-spin';
});
</script>

</body>
</html>
