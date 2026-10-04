<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
@php
    $branding = \App\Models\BrandingSetting::current();
    $css = fn (string $path) => asset($path).'?v='.(@filemtime(public_path($path)) ?: 0);
@endphp
<title>@yield('title') — {{ $branding->displaySiteName() }}</title>

<meta name="theme-color" content="#0B1120">
<link rel="icon" type="image/png" href="{{ $branding->faviconUrl() ?: asset('icons/favicon-32.png') }}">
<script>
    (function () {
        // Same storage key and pre-paint treatment as the app and the sign-in
        // page, so moving between them never flashes the wrong theme.
        var saved = localStorage.getItem('p7-theme');
        document.documentElement.setAttribute('data-theme', saved === 'light' ? 'light' : 'dark');
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="{{ $css('css/app-core.css') }}">

{{-- ── Auth utility pages ───────────────────────────────────────────────────
     Password reset is a transactional errand, not a destination: the visitor
     arrived from an email, has one field to fill, and leaves. So it gets a
     centred card on the app's own ground rather than the sign-in page's
     photo panel — which is there to introduce the product to someone about to
     use it, and would only be in the way here.

     Everything below is layout for that one arrangement. Colour, type, the
     card, the fields and the buttons are all app-core's. --}}
<style>
    body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: var(--sp-6);
        background: var(--page-bg);
    }
    .auth-col { width: 100%; max-width: 404px; }
    .auth-brand {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--sp-3);
        margin-bottom: var(--sp-6);
    }
    .auth-brand img { width: 40px; height: 40px; border-radius: var(--radius-sm); }
    .auth-brand-name {
        font-family: var(--font-display);
        font-size: var(--fs-md);
        font-weight: 700;
        color: var(--text-primary);
        line-height: var(--lh-snug);
    }
    .auth-brand-name span {
        display: block;
        font-size: var(--fs-xs);
        font-weight: 500;
        color: var(--text-muted);
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-top: 1px;
    }
    .auth-title {
        font-family: var(--font-display);
        font-size: var(--fs-2xl);
        font-weight: 800;
        letter-spacing: -0.01em;
        color: var(--text-primary);
    }
    .auth-lede {
        margin-top: var(--sp-1);
        margin-bottom: var(--sp-6);
        font-size: var(--fs-base);
        color: var(--text-muted);
        line-height: var(--lh-base);
    }
    .auth-back {
        margin-top: var(--sp-5);
        text-align: center;
        font-size: var(--fs-sm);
    }
    .auth-back a { color: var(--text-secondary); text-decoration: none; font-weight: 500; }
    .auth-back a:hover { color: var(--tone-accent-fg); text-decoration: underline; }
    /* A form's own submit is the page's single action, so it runs the width of
       the card the way the sign-in button does. */
    .auth-actions { margin-top: var(--sp-6); }
</style>
</head>
<body>

<div class="auth-col">
    <div class="auth-brand">
        <img src="{{ $branding->logoUrl() ?: asset('logo/promoseven-logo.png') }}" alt="{{ $branding->displaySiteName() }}">
        <div class="auth-brand-name">
            {{ $branding->company_name ?: 'Promoseven Holdings' }}
            <span>{{ $branding->tagline ?: 'Real Estate Division' }}</span>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @yield('auth-content')
        </div>
    </div>

    <div class="auth-back">
        <a href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i> Back to sign in</a>
    </div>
</div>

</body>
</html>
