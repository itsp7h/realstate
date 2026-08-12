<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Preview 1b — Photo hero + dark sheet</title>

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
        height: 100vh;
        overflow: hidden;
        background: #0a0f18;
    }
    .photo {
        position: absolute; top: 0; left: 0; width: 100%; height: 430px;
        background: url('{{ asset('images/login-building.jpg') }}') 55% 25% / cover no-repeat;
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
    .hero-top { position: absolute; top: 0; left: 0; right: 0; padding: 24px 24px 0; }
    .brand-row { display: flex; align-items: center; gap: 11px; margin-top: 16px; }
    .brand-row img { width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; }
    .brand-title { font-weight: 700; font-size: 14.5px; line-height: 1.1; color: #fff; }
    .brand-sub { font-weight: 700; font-size: 9px; line-height: 1.4; letter-spacing: 1.6px; color: #FCB017; }

    .sheet {
        position: absolute; left: 0; right: 0; bottom: 0; top: 342px;
        background: #0b1220; border-radius: 34px 34px 0 0; border-top: 1px solid rgba(255,255,255,.09);
        padding: 30px 26px 0; display: flex; flex-direction: column;
        box-shadow: 0 -26px 60px rgba(0,0,0,.55);
        overflow-y: auto;
    }
    h1 { margin: 0 0 8px; font-weight: 700; font-size: 30px; line-height: 1.12; color: #fff; letter-spacing: -.6px; }
    .subcopy { margin: 0 0 24px; font-size: 14px; line-height: 1.5; color: rgba(255,255,255,.55); }

    .alert-error {
        margin: 0 0 16px; padding: 12px 14px; border-radius: 12px;
        background: rgba(239,68,68,.14); border: 1px solid rgba(239,68,68,.3);
        color: #FCA5A5; font-size: 13px; display: flex; gap: 9px; align-items: flex-start;
    }

    .field-wrap {
        display: flex; align-items: center; gap: 11px; height: 56px; padding: 0 16px;
        border-radius: 16px; background: #111a2a; border: 1px solid rgba(255,255,255,.09);
        margin-bottom: 13px;
    }
    .field-wrap i { font-size: 15px; color: rgba(255,255,255,.5); flex-shrink: 0; }
    .field-wrap input {
        flex: 1; background: none; border: none; outline: none; color: #fff; font-size: 15px;
        font-family: 'Figtree', sans-serif; min-width: 0;
    }
    .field-wrap input::placeholder { color: rgba(255,255,255,.38); }
    .field-wrap:focus-within { border-color: rgba(252,176,23,.4); }
    .toggle-pw {
        background: none; border: none; cursor: pointer; padding: 6px;
        font-family: 'Figtree', sans-serif; font-weight: 600; font-size: 11.5px; letter-spacing: .6px; color: #FCB017;
    }
    .field-error { color: #FCA5A5; font-size: 12px; margin: -7px 0 13px; }

    .row-end { display: flex; justify-content: flex-end; margin: 14px 0 20px; }
    .forgot-link { font-weight: 500; font-size: 13.5px; color: #6FA8F5; cursor: default; min-height: 44px; display: flex; align-items: center; }

    .btn-submit {
        width: 100%; height: 56px; border: none; border-radius: 16px; background: #FCB017; color: #0a0f18;
        font-family: 'Figtree', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        box-shadow: 0 12px 30px rgba(252,176,23,.22);
    }
    .btn-submit:hover { background: #ffbe3d; }
    .btn-submit:active { transform: scale(.99); }
    .btn-submit:disabled { opacity: .65; cursor: not-allowed; }

    .divider { display: flex; align-items: center; gap: 12px; margin: 22px 0 16px; }
    .divider span.line { flex: 1; height: 1px; background: rgba(255,255,255,.1); }
    .divider span.label { font-size: 12px; color: rgba(255,255,255,.4); white-space: nowrap; }

    .social-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .btn-social {
        height: 50px; border-radius: 14px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.11);
        color: #fff; font-family: 'Figtree', sans-serif; font-weight: 500; font-size: 14px; cursor: not-allowed;
        display: flex; align-items: center; justify-content: center; gap: 9px; opacity: .7;
    }
    .btn-social svg { width: 17px; height: 17px; flex-shrink: 0; }

    .sheet-spacer { flex: 1; min-height: 20px; }
    .legal { margin: 0 0 12px; text-align: center; font-size: 11.5px; line-height: 1.5; color: rgba(255,255,255,.38); }
    .legal span { color: #6FA8F5; }
    .home-indicator { height: 5px; width: 134px; border-radius: 3px; background: rgba(255,255,255,.25); margin: 0 auto 10px; }

    @media (min-width: 700px) {
        html, body { height: auto; overflow: auto; }
        body { padding: 24px 0; align-items: center; }
        .phone { max-width: 420px; height: 844px; border-radius: 38px; box-shadow: 0 18px 50px rgba(0,0,0,.35); }
    }
</style>
</head>
<body>

<div class="phone">
    <div class="photo"></div>
    <div class="photo-scrim"></div>
    <div class="watermark">P7H</div>

    <div class="hero-top">
        <div class="brand-row">
            <img src="{{ asset('logo/promoseven-logo.png') }}" alt="Promoseven Holdings">
            <div>
                <div class="brand-title">Promoseven Holdings</div>
                <div class="brand-sub">REAL ESTATE DIVISION</div>
            </div>
        </div>
    </div>

    <div class="sheet">
        <h1>Welcome back.</h1>
        <p class="subcopy">Buildings, leases, invoices and reports — all in one place.</p>

        @if ($errors->any())
        <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation" style="margin-top:2px;"></i>
            <div>{{ $errors->first() }}</div>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="loginForm1b" novalidate>
            @csrf

            <div class="field-wrap">
                <i class="fa-regular fa-user"></i>
                <input type="text" name="login" value="{{ old('login') }}"
                       placeholder="Email or username" required autofocus autocomplete="username">
            </div>
            @error('login')<div class="field-error">{{ $message }}</div>@enderror

            <div class="field-wrap">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password1b" name="password" placeholder="Password" required autocomplete="current-password">
                <button type="button" class="toggle-pw" id="togglePw1b">SHOW</button>
            </div>
            @error('password')<div class="field-error">{{ $message }}</div>@enderror

            <div class="row-end">
                <span class="forgot-link" title="Password reset isn't available yet">Forgot password?</span>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn1b">
                <span id="submitLabel1b">Sign in</span>
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

        <div class="sheet-spacer"></div>
        <p class="legal">By signing in you agree to our <span>Terms</span> and <span>Privacy Policy</span>.</p>
        <div class="home-indicator"></div>
    </div>
</div>

<script>
document.getElementById('togglePw1b').addEventListener('click', function () {
    const input = document.getElementById('password1b');
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    this.textContent = showing ? 'SHOW' : 'HIDE';
});
document.getElementById('loginForm1b').addEventListener('submit', function () {
    document.getElementById('submitBtn1b').disabled = true;
    document.getElementById('submitLabel1b').textContent = 'Signing in…';
});
</script>

</body>
</html>
