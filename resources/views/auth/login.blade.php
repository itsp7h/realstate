<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
@php
    $branding = \App\Models\BrandingSetting::current();
@endphp
<meta name="apple-mobile-web-app-title" content="{{ $branding->displaySiteName() }}">
<title>Sign In — {{ $branding->displaySiteName() }}</title>

<link rel="manifest" href="{{ asset('manifest.json') }}">
{{-- Matches app-core's --sidebar-bg, so the status bar and the navy brand
     panel are the same colour. --}}
<meta name="theme-color" content="#0B1120">
<link rel="icon" type="image/png" href="{{ $branding->faviconUrl() ?: asset('icons/favicon-32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
    }
</script>
<script>
    (function () {
        // Applied before first paint to avoid a flash of the wrong theme, and
        // resolved exactly as layouts/admin.blade.php resolves it: saved
        // choice, else the OS preference. Sign-in used to force dark unless
        // light had been chosen explicitly, which meant a light-mode user met a
        // dark sign-in page and then a light app — and a dark-mode user got a
        // sign-in page pinned light while the app around it went dark.
        //
        // The mobile hero does not need the override: its --m-* block already
        // carries a full light set (the frosted-glass treatment) alongside the
        // dark one, so it reads correctly either way.
        var saved = localStorage.getItem('p7-theme');
        var theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- Poppins is the design system's display + body face (app-core §1.1
     --font-display / --font-body). Figtree is the mobile hero/sheet layer
     below, which is its own dark-first design. --}}
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

{{-- ── The design system ────────────────────────────────────────────────────
     Sign-in used to be a standalone document with a private :root, its own
     fonts and its own field/button/alert CSS — which is how it ended up on a
     different gold, a different navy and a different type stack from the app
     it signs you into, and why its desktop half never responded to dark mode
     at all. It now loads app-core.css like every other page: tokens, forms,
     buttons and alerts all come from there, both themes included. What stays
     below is only what is genuinely unique to this screen — the two-panel
     composition, and the mobile photo-hero/sheet layer. --}}
@php
    $css = fn (string $path) => asset($path).'?v='.(@filemtime(public_path($path)) ?: 0);
@endphp
<link rel="stylesheet" href="{{ $css('css/app-core.css') }}">

<style>
    /* ══════════════════════════════════════════════════════════════════════
       SIGN-IN — page-local palette and composition
       ──────────────────────────────────────────────────────────────────────
       This screen is the one place in the app that runs a fixed palette of
       its own: it is seen before a session — and therefore before a theme
       preference — exists, and the design handoff specifies it exactly. So
       the palette is redeclared ON `.login-shell` and inherits down, rather
       than edited into app-core's :root, which would repaint every screen.

       That works because app-core is token-driven end to end: .card,
       .form-control, .btn, .alert and .form-check all read tokens, so they
       re-light from the one block below. Nothing here is global, and nothing
       outside this page changes.

       The mobile photo-hero/sheet (≤768px) keeps its own dark-first design
       and its own --m-* set — `.login-shell` is display:none down there, so
       none of this reaches it.
       ══════════════════════════════════════════════════════════════════════ */

    /* app-core lays the admin shell out with `body { display: flex }`. This
       page has no shell — it has two full-height panels of its own. */
    body { display: block; background: #EEF1F6; }

    .mobile-shell { display: none; }

    /* The desktop half is light whatever theme is stored, so the native
       chrome it borrows — autofill, the checkbox, the scrollbar — has to be
       told the same thing. Restated for the dark selector, which is more
       specific than a bare :root. */
    @media (min-width: 769px) {
        :root,
        :root[data-theme="dark"] { color-scheme: light; }
    }

    /* ── DESKTOP — navy brand panel + white form card ─────────────────────
         42/58 split: the brand side is a navy gradient carrying the building
         as texture, the form side is the app's page ground with a single
         raised white card on it. */
    .login-shell {
        display: flex;
        min-height: 100vh;

        /* ── NO COLOUR IS DECLARED HERE, deliberately ──────────────────────
           This block used to re-type twenty-six of app-core's tokens as
           literals to pin the desktop half light. Sixteen matched the real
           token; ten had drifted — --text-secondary #6B7689 against the
           system's #5D6880, --text-muted #6B7689 against #626E85, gold-as-text
           #8A6D20 against #84681E, and --input-border #D5DAE3 against #848FA6,
           which is the darker value app-core picked for contrast.

           Pinning also meant the page could only ever be light: in dark mode
           the app went dark and sign-in stayed white, on the light gold
           gradient while every button in the app had switched to the dark one.

           Inheriting instead means both themes match by construction, and a
           token changed in app-core §1.1 reaches this page like any other.
           Gold as text is --tone-accent-fg, which app-core already fixes at
           6.4:1 in light and lifts to #EDCD85 in dark.

           What stays below is geometry, not colour: sign-in has one job and
           its fields are deliberately taller and softer than a data form's. */

        /* ── Geometry ─────────────────────────────────────────────────── */
        --card-radius:    16px;
        --radius-sm:      12px;   /* fields and buttons share the 12px corner */
        --h-control:      50px;   /* fields */
        --h-control-lg:   52px;   /* the submit */
        --border-control: 1px;
    }

    .login-brand {
        flex: 0 0 42%;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        /* Logo top, message centred, copyright bottom. Centring all three
           together left a dead band above the logo and pushed the copyright
           off the panel's own baseline. */
        justify-content: space-between;
        padding: var(--sp-10) 60px;
        /* The same navy the sidebar is painted in, so the panel you sign in
           against is the panel you land beside. */
        background: linear-gradient(165deg, var(--sidebar-active) 0%, var(--sidebar-bg) 100%);
    }
    /* The photo is the panel's picture, not its texture — the gradient below
       is the ground it sits on and the scrim above is what makes it readable.
       .70 is the balance that scrim is cut for: the facade reads as a
       photograph without competing with the gold. */
    .login-brand-photo {
        position: absolute; inset: 0;
        background: url('{{ asset('images/login-building.jpg') }}') 50% 30% / cover no-repeat;
        opacity: .7;
    }
    /* Full-panel scrim on the photo, deepening down the diagonal so the
       headline band stays lighter than the copyright line beneath it. */
    .login-brand-scrim {
        position: absolute; inset: 0;
        background: linear-gradient(160deg, rgba(11,19,43,.42) 0%, rgba(11,19,43,.68) 70%, rgba(11,19,43,.88) 100%);
    }
    /* Gold bloom in the top corner — the same glow value the focus ring uses,
       so the brand mark is lit by the accent it shares with the form. */
    .login-brand::before {
        content: '';
        position: absolute;
        top: -20%; left: -10%;
        width: 480px; height: 480px;
        background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
        pointer-events: none;
        z-index: 1;
    }
    /* A gold hairline on the seam, fading out top and bottom, so the two
       panels meet on the accent rather than on a hard navy/grey edge. */
    .login-brand::after {
        content: '';
        position: absolute;
        top: 0; right: 0; bottom: 0; width: 1px;
        background: linear-gradient(180deg, transparent 0%, rgba(216,178,95,.38) 50%, transparent 100%);
        z-index: 2;
    }
    /* Content sits above the photo, vignette and bloom. Named rather than
       `> *`, because the photo and vignette are the absolute layers
       underneath and must keep their own positioning. */
    .login-logo,
    .login-brand-copy,
    .login-brand-footer { position: relative; z-index: 2; }

    .login-logo { display: flex; align-items: center; gap: 14px; }
    .login-logo img { width: 46px; height: 46px; border-radius: var(--radius-sm); }
    .login-logo-name {
        font-family: var(--font-display);
        font-size: var(--fs-md);
        font-weight: 600;
        color: #FFFFFF;
        line-height: var(--lh-snug);
    }
    .login-logo-name span {
        display: block;
        font-size: var(--fs-2xs);
        font-weight: 600;
        color: var(--accent);
        letter-spacing: 0.16em;
        text-transform: uppercase;
        margin-top: 3px;
    }

    .login-headline {
        font-family: var(--font-display);
        font-size: 34px;
        font-weight: 700;
        color: #FFFFFF;
        line-height: 1.22;
        letter-spacing: -0.02em;
        max-width: 420px;
    }
    .login-headline em { font-style: normal; color: var(--accent); }
    .login-lede {
        margin-top: var(--sp-4);
        font-size: 14px;
        color: rgba(255,255,255,.74);
        line-height: 1.7;
        max-width: 380px;
    }
    .login-brand-footer {
        font-size: var(--fs-sm);
        color: rgba(255,255,255,.55);
    }

    .login-form {
        flex: 1;
        background: var(--page-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: var(--sp-10) var(--sp-6);
        /* The frame for the decorative layer below: positioned so the shapes
           hang off its own edges, clipped so none of them reaches the seam
           with the navy panel or gives the page a scrollbar. */
        position: relative;
        overflow: hidden;
    }

    /* ── Decoration ───────────────────────────────────────────────────────
         Three shapes on the ground behind the card, in the two colours the
         page already uses: gold at .10 and navy at .08/.14. Inert — no pointer
         events, no place in the reading order — and desktop-only for free:
         they sit inside .login-shell, which the phone screen hides. */
    .deco { position: absolute; pointer-events: none; }
    .deco--ring {
        top: -120px; right: -100px;
        width: 380px; height: 380px;
        border-radius: 50%;
        border: 56px solid rgba(216,178,95,.10);
    }
    .deco--dots {
        bottom: 48px; right: 64px;
        width: 180px; height: 120px;
        background-image: radial-gradient(rgba(30,44,79,.14) 1.5px, transparent 1.5px);
        background-size: 18px 18px;
    }
    .deco--line {
        bottom: -90px; left: -70px;
        width: 260px; height: 260px;
        border-radius: 50%;
        border: 1.5px solid rgba(30,44,79,.08);
    }
    /* The card is app-core's .card with this page's corner, border and lift —
       one deep, soft shadow, so it reads as floating on the ground rather
       than as another panel drawn on it. */
    .login-card {
        width: 100%;
        max-width: 424px;
        /* Positioned elements paint above static ones regardless of source
           order, so the card has to join the stack to stay in front of the
           decoration. Stacking only — nothing about the card changes. */
        position: relative;
        z-index: 1;
        --card-pad-lg: 36px 34px;
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        /* The top edge is the gold rule below, not a hairline. `overflow`
           clips it to the card's own corner radius. */
        border-top: 0;
        overflow: hidden;
        border-radius: var(--card-radius);
        box-shadow: 0 18px 44px rgba(30,44,79,.10);
    }
    /* The gold rule across the card's top edge: drawn out from the centre on
       load, then a slow shimmer along its length. app-core §1.4's global
       prefers-reduced-motion rule collapses both to their end state, so a
       viewer who asked for less motion gets the finished line and no travel. */
    .login-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent), var(--accent-strong), var(--accent));
        background-size: 200% 100%;
        transform: scaleX(0);
        transform-origin: center;
        animation: lineIn .6s cubic-bezier(.22, 1, .36, 1) .2s forwards,
                   shimmer 3s linear 1s infinite;
    }
    @keyframes lineIn  { to { transform: scaleX(1); } }
    @keyframes shimmer { to { background-position: -200% 0; } }
    /* Gold ink, a gold dash, and the only uppercase on the card — enough to
       anchor the heading without a second heading. */
    .login-eyebrow {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        margin-bottom: 10px;
        font-size: var(--fs-xs);
        font-weight: 600;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: var(--tone-accent-fg);
    }
    .login-eyebrow::before {
        content: '';
        width: 18px; height: 2px;
        border-radius: 2px;
        background: var(--accent);
    }
    .login-title {
        font-family: var(--font-display);
        font-size: 26px;
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: var(--lh-tight);
        color: var(--text-primary);
    }
    .login-card-lede {
        margin-top: 6px;
        margin-bottom: 26px;
        font-size: 14px;
        color: var(--text-muted);
    }

    /* Fields: 50px, 12px corner, hairline border, quiet placeholder. The
       type steps up a little from the app's 13.5px — two fields on an
       otherwise empty card can afford it. */
    .login-shell .form-control {
        font-size: 14px;
        padding: 0 16px;
    }
    .login-shell .form-control::placeholder { color: #B6BECE; }
    /* Autofill paints its own fill over the field; hold the card white and
       the ink navy, and keep the focus ring visible on top of it. */
    .login-shell input:-webkit-autofill,
    .login-shell input:-webkit-autofill:hover {
        -webkit-text-fill-color: var(--text-primary);
        -webkit-box-shadow: 0 0 0 40px var(--input-bg) inset;
        caret-color: var(--text-primary);
    }
    .login-shell input:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0 40px var(--input-bg) inset, var(--focus-ring);
    }

    /* Checkbox on the left, the unavailable reset on the right. */
    .login-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
        margin: var(--sp-5) 0 var(--sp-6);
    }
    /* Not a link: password reset is not built yet, so it must not look
       clickable — which is why it takes the footnote grey and not the gold
       ink the eyebrow uses. Kept visible because its absence is the question
       users actually have here. */
    .login-forgot { font-size: var(--fs-base); color: var(--text-faint); cursor: default; }

    .login-shell .btn-lg {
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 0.01em;
    }

    /* 900px is app-core §1.0's "side-by-side panels stack" step. Below it the
       brand panel becomes a band above the form; ≤768px the mobile screen
       below takes over entirely. */
    @media (max-width: 900px) and (min-width: 769px) {
        .login-shell { flex-direction: column; }
        .login-brand { flex: 0 0 auto; padding: var(--sp-8) var(--sp-8); gap: var(--sp-6); }
        /* The band is short here, so the full-height focal point lands on sky.
           Drop it to the building. */
        .login-brand-photo { background-position: 50% 64%; }
        /* The seam is now horizontal — move the hairline to the bottom edge. */
        .login-brand::after {
            top: auto; left: 0; right: 0; bottom: 0; width: auto; height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(216,178,95,.38) 50%, transparent 100%);
        }
        .login-headline { font-size: var(--fs-3xl); }
        .login-brand-footer { display: none; }
        .login-form { padding: var(--sp-10) var(--sp-6) 60px; }
    }

    /* ── MOBILE — photo hero + sheet (design-handoff option 1b) ───────────
         Its own dark-first design with its own token block, deliberately not
         the desktop palette. Untouched by the app-core migration except for
         the field reset noted below. */
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
        --m-legal:         rgba(14,20,32,.42);
        --m-home-indicator:rgba(14,20,32,.2);
        --m-toggle-pw:     #B87A05;
    }

    @media (max-width: 768px) {
        .login-shell { display: none; }
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
        .hero-top { position: absolute; top: 0; left: 0; right: 0; padding: calc(24px + env(safe-area-inset-top)) 24px 0; display: flex; align-items: center; justify-content: space-between; }
        .m-brand-row { display: flex; align-items: center; gap: 11px; margin-top: 16px; }
        .m-brand-row img { width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; }
        .m-brand-title { font-weight: 700; font-size: 14.5px; line-height: 1.1; color: #fff; }
        .m-brand-sub { font-weight: 700; font-size: 9px; line-height: 1.4; letter-spacing: 1.6px; color: var(--accent); }
        /* Top-right of the hero, opposite the brand row. It sits in the
           scrim's darkest band, so a light wash over the photo is enough
           chrome — with the blur to keep the glyph off busy glass. */
        /* The brand statement over the photo — the mobile echo of the desktop
           brand panel, gold accent on the last line and all. Parked above the
           342px sheet lip so expanding the sheet never covers it, and shadowed
           because it sits on sky, which is the lightest part of the photo. */
        .m-headline {
            position: absolute; z-index: 2;
            top: 122px; left: 26px; right: 26px;
            /* Breathing room from the brand header above it. */
            margin: 40px 0 0;
            max-width: 300px;
            font-family: 'Figtree', sans-serif;
            font-size: 28px; font-weight: 700;
            line-height: 1.2; letter-spacing: -.02em;
            color: #fff;
            /* Light touch — the scrim over the photo already carries most of
               the separation, and the heavier pair before this read as grubby. */
            text-shadow: 0 2px 12px rgba(0,0,0,.4);
            transition: opacity .3s ease;
            /* Decoration over the photo: taps belong to the photo underneath,
               which is what collapses the sheet. */
            pointer-events: none;
        }
        .m-headline em { font-style: normal; color: var(--accent); }
        /* Expanded, the photo band shrinks and the building — sign and all —
           rides up into the headline. Little of the photo is left to caption
           by then, so the headline gets out of the way. */
        .phone.is-sheet-open .m-headline { opacity: 0; }

        .theme-toggle-btn {
            width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.2); color: #fff; font-size: 14px;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            margin-top: 16px; flex-shrink: 0;
            -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);
        }

        .sheet {
            position: absolute; left: 0; right: 0; bottom: 0; top: 342px;
            background: var(--m-sheet-bg); border-radius: 34px 34px 0 0; border-top: 1px solid var(--m-sheet-border);
            padding: 26px 26px calc(18px + env(safe-area-inset-bottom)); display: flex; flex-direction: column;
            box-shadow: 0 -26px 60px var(--m-sheet-shadow);
            overflow-y: auto;
        }
        /* Top-aligned, not centred. This was `margin: auto 0` from when the
           heading lived inside it and the content always overflowed, so the
           auto margins resolved to nothing. The heading is now its own block
           that has to sit flush against the sheet's top edge — it is the part
           that peeks when docked — and with the form shorter than the sheet on
           a tall phone, centring the remainder opened a ~57px hole under the
           subtitle and pushed the collapsed peek from 159px to 194px. */
        .sheet-inner { margin: 0; width: 100%; }

        /* ── Collapsible sheet ─────────────────────────────────────────────
             Docked, the sheet shows only its handle and heading so the
             building photo owns the screen; expanded, it sits exactly where
             it always did (top: 342px), so the open state is the design as
             shipped and only the closed state is new.

             The travel is a percentage of the sheet's own height less the
             peek, so it stays correct at any viewport height without JS
             recomputing it on resize. --sheet-peek is measured in the script
             from the handle plus the heading, so a subtitle that wraps to a
             third line still clears the fold; 140px is the design's figure
             and the fallback if the script never runs. */
        .sheet {
            border-radius: 24px 24px 0 0;
            /* The gold accent across the top edge — the mobile counterpart to
               .login-card::before on the desktop card.

               A pinned bar, not a border: `border-top` gets mitred round the
               24px radius and sweeps ~24px down both sides, which reads as a
               gold box drawn around the header row. A background band is
               clipped by the radius instead, so the line stops at the corners
               and runs full width under them. background-attachment stays
               `scroll` (the default), which on this scroll container pins the
               band to the element rather than the content — so it does not
               slide away when the form is scrolled.

               --accent is #D8B25F and is declared once, not per theme, so the
               line is the same gold in light and dark. */
            border-top: 0;
            background-image: linear-gradient(var(--accent), var(--accent));
            background-repeat: no-repeat;
            background-size: 100% 3px;
            background-position: 0 0;
            transform: translateY(calc(100% - var(--sheet-peek, 140px)));
            transition: transform .35s cubic-bezier(.22,1,.36,1);
        }
        .phone.is-sheet-open .sheet { transform: translateY(0); }
        /* Docked, a drag on the sheet is a disclosure gesture, so the hidden
           form must not swallow it as a scroll. */
        .phone:not(.is-sheet-open) .sheet { overflow: hidden; }

        /* Normally a 430px band with the sheet over its foot; docked, the
           photo is the whole viewport. */
        .phone:not(.is-sheet-open) .photo,
        .phone:not(.is-sheet-open) .photo-scrim { height: 100%; }

        /* A real button, so the disclosure is reachable by keyboard and
           announced — with a 28px target around the 4px bar, and the bar
           itself borrowing the home-indicator token it visually echoes. */
        /* The row carries the height and margins the handle used to, and is
           the hints' positioning context — so "vertically aligned with the
           handle" is align-items, not a hand-tuned top offset. The hint was
           previously absolute against the sheet, landing 2.5px off the grip's
           centre, which is what made it read as a second line. */
        .sheet-hrow {
            position: relative;
            flex: none;
            display: flex; align-items: center; justify-content: center;
            height: 28px; margin: -10px 0 6px;
        }
        .sheet-handle {
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 100%;
            padding: 0; border: none; background: none; cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        /* Plain in both states and both themes: a grip that turned gold on
           open put a second gold element next to the hint and the top line. */
        .sheet-grip {
            width: 44px; height: 5px; border-radius: 3px;
            background: #d5dae3;
        }
        .sheet-handle:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 8px; }
        .sheet-head { flex: none; }
        /* The docked sheet has to say that it opens. Parked in the top-right
           corner and out of flow, so it neither pushes the heading down nor
           moves the fold. Retired once the sheet is open — "Tap to sign in"
           over an open form would be nonsense. */
        .peek-hint,
        .close-hint {
            position: absolute; top: 50%; right: 0;
            transform: translateY(-50%);
            display: inline-flex; align-items: center; gap: 5px;
            /* Vertical hit area for the button — 10.5px of text is a 14px-tall
               target. Applied to both so the two stay pixel-identical as they
               swap, and symmetric so it does not shift the optical centre. */
            padding: 8px 0;
            font-family: 'Figtree', sans-serif;
            font-size: 10.5px; font-weight: 600; letter-spacing: .02em;
            /* Stated, not inherited: a <button> carries its own line-height,
               which left the two chevrons 2px apart as the labels swapped. */
            line-height: 1;
            /* Per theme, because the two backdrops are opposites: near-black
               navy in dark, a translucent sheet over the mid-grey building in
               light. One ink cannot clear AA on both — gold measured 9.32:1
               dark but 1.02:1 light, and the #8a6d20 compromise failed both
               (3.82:1 / 2.50:1). Split, each side passes: see the light
               override below. */
            color: var(--accent);
        }
        /* .close-hint does something, so it is a button. */
        .close-hint {
            border: none; background: none; cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .close-hint:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 6px; }
        .peek-hint svg,
        .close-hint svg { display: block; }
        /* One slot, two labels, swapped by the sheet's state. */
        .phone.is-sheet-open .peek-hint { display: none; }
        .phone:not(.is-sheet-open) .close-hint { display: none; }

        /* Spec 4: no slide. The state change still happens, it just arrives
           rather than travels. */
        @media (prefers-reduced-motion: reduce) {
            .sheet, .m-headline { transition: none; }
        }
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
        /* app-core §4.2 styles every bare text input in the app — the right
           default everywhere else, and wrong inside a field-wrap, which draws
           the box itself. Reset the chrome app-core adds, then restate this
           layer's own type. */
        .field-wrap input {
            flex: 1; width: auto; min-width: 0;
            min-height: 0; padding: 0; border: none; border-radius: 0;
            background: none; box-shadow: none; outline: none;
            color: var(--m-field-text); font-size: 16px;
            font-family: 'Figtree', sans-serif;
        }
        .field-wrap input:focus { border: none; box-shadow: none; }
        .field-wrap input::placeholder { color: var(--m-field-placeholder); }
        .field-wrap:focus-within { border-color: var(--accent); }
        .toggle-pw {
            background: none; border: none; cursor: pointer; padding: 6px;
            font-family: 'Figtree', sans-serif; font-weight: 600; font-size: 11.5px; letter-spacing: .6px;
            color: var(--m-toggle-pw, var(--accent));
        }
        .sheet .field-error { display: block; color: var(--m-field-error); font-size: 12px; margin: -7px 0 13px; }

        .row-end { display: flex; justify-content: flex-end; margin: 14px 0 20px; }
        .sheet .forgot-link { font-weight: 500; font-size: 13.5px; color: var(--m-link); text-decoration: none; min-height: 44px; display: flex; align-items: center; }

        .sheet .btn-submit {
            width: 100%; height: 56px; border: none; border-radius: 16px;
            background: var(--accent-gradient); color: var(--on-accent);
            font-family: 'Figtree', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: var(--accent-lift);
        }
        .sheet .btn-submit:hover { box-shadow: var(--accent-lift-hover); }
        .sheet .btn-submit:active { transform: scale(.99); }
        .sheet .btn-submit:disabled { opacity: .65; cursor: not-allowed; }

        .legal { margin: 18px 0 12px; text-align: center; font-size: 11.5px; line-height: 1.5; color: var(--m-legal); }
        .legal span { color: var(--m-link); }
        .home-indicator { height: 5px; width: 134px; border-radius: 3px; background: var(--m-home-indicator); margin: 0 auto 10px; }

        /* ── LIGHT MODE — "2b" design handoff: frosted-glass card floating
             over the building photo, instead of dark mode's opaque navy
             sheet. Every rule below is scoped to [data-theme="light"] so
             dark mode (the original, unaffected) keeps its own look. ── */
        :root[data-theme="light"] .photo {
            filter: brightness(1.22) saturate(.72) contrast(.92);
        }
        :root[data-theme="light"] .photo-scrim {
            background: linear-gradient(180deg,
                rgba(14,20,32,.42) 0%, rgba(14,20,32,.06) 30%,
                rgba(246,244,240,.06) 55%, rgba(246,244,240,.35) 100%);
        }
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
        /* 6.79:1 collapsed and 9.78:1 expanded, against gold's 1.34:1/1.02:1.
           --m-heading is this layer's own ink, so it is the same black the
           heading above it already uses. */
        :root[data-theme="light"] .peek-hint,
        :root[data-theme="light"] .close-hint { color: var(--m-heading); }
    }
</style>
</head>
<body>

<div class="login-shell">
    <div class="login-brand">
        <div class="login-brand-photo"></div>
        <div class="login-brand-scrim"></div>
        <div class="login-logo">
            <img src="{{ $branding->logoUrl() ?: asset('logo/promoseven-logo.png') }}" alt="{{ $branding->displaySiteName() }}">
            <div class="login-logo-name">
                {{ $branding->company_name ?: 'Promoseven Holdings' }}
                <span>{{ $branding->tagline ?: 'Real Estate Division' }}</span>
            </div>
        </div>

        <div class="login-brand-copy">
            <h1 class="login-headline">Every building,<br>every tenant,<br><em>one ledger.</em></h1>
            <p class="login-lede">Buildings, leases, invoices, and reports — all in one place. Sign in to pick up where you left off.</p>
        </div>

        <div class="login-brand-footer">&copy; {{ date('Y') }} {{ $branding->company_name ?: 'Promoseven Holdings BSC' }}</div>
    </div>

    <div class="login-form">
        <span class="deco deco--ring" aria-hidden="true"></span>
        <span class="deco deco--dots" aria-hidden="true"></span>
        <span class="deco deco--line" aria-hidden="true"></span>

        <div class="login-card card">
            <div class="card-body">
            <p class="login-eyebrow">Secure sign-in</p>
            <h2 class="login-title">Welcome back</h2>
            <p class="login-card-lede">Sign in to your account to continue.</p>

            @if (session('status'))
            <div class="alert alert-success" role="status">
                <i class="fa-solid fa-circle-check"></i>
                <div>{{ session('status') }}</div>
            </div>
            @endif

            @error('auth')
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div>{{ $message }}</div>
            </div>
            @enderror

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="form-grid cols-1">
                    <div class="form-group">
                        <label class="form-label" for="login">Email or username</label>
                        <input type="text" id="login" name="login" class="form-control @error('login') is-invalid @enderror"
                               value="{{ old('login') }}" placeholder="you@promoseven.com or username"
                               autocomplete="username" required autofocus
                               @error('login') aria-invalid="true" aria-describedby="login-error" @enderror>
                        @error('login')
                            <div class="field-error" id="login-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               placeholder="••••••••" autocomplete="current-password" required
                               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        @error('password')
                            <div class="field-error" id="password-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
                        @enderror
                    </div>
                </div>

                <div class="login-row">
                    <div class="form-check">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">Keep me signed in</label>
                    </div>
                    <a class="login-forgot" href="{{ route('password.request') }}">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign in
                </button>
            </form>
            </div>
        </div>
    </div>
</div>

@php
    /* The sheet starts open when there is something in it the user must see or
       finish — a rejected attempt, a field error, or text already typed — so a
       failed sign-in never lands behind a collapsed panel. Mirrors the
       "keep it open while a field has focus or text" rule in the script. */
    $sheetOpen = $errors->any() || filled(old('login'));
@endphp

<div class="mobile-shell">
    <div class="phone{{ $sheetOpen ? ' is-sheet-open' : '' }}">
        <div class="photo"></div>
        <div class="photo-scrim"></div>

        <div class="hero-top">
            <div class="m-brand-row">
                <img src="{{ $branding->logoUrl() ?: asset('logo/promoseven-logo.png') }}" alt="{{ $branding->displaySiteName() }}">
                <div>
                    <div class="m-brand-title">{{ $branding->company_name ?: 'Promoseven Holdings' }}</div>
                    <div class="m-brand-sub">{{ Str::upper($branding->tagline ?: 'Real Estate Division') }}</div>
                </div>
            </div>
            <button type="button" class="theme-toggle-btn" id="loginThemeToggle" title="Switch theme" aria-label="Switch to light mode"><i class="fa-solid fa-sun"></i></button>
        </div>

        {{-- The brand statement, mirroring the desktop panel's headline. Sits
             above the expanded sheet's top edge so it reads in both states. --}}
        <h2 class="m-headline">Every building,<br>every tenant,<br><em>one ledger.</em></h2>

        <div class="sheet" id="loginSheet" aria-expanded="{{ $sheetOpen ? 'true' : 'false' }}">
            {{-- One row: grip centred, hint pinned right and centred against it. --}}
            <div class="sheet-hrow">
                <button type="button" class="sheet-handle" id="sheetHandle"
                        aria-controls="loginSheet" aria-expanded="{{ $sheetOpen ? 'true' : 'false' }}"
                        aria-label="{{ $sheetOpen ? 'Collapse sign-in form' : 'Expand sign-in form' }}">
                    <span class="sheet-grip" aria-hidden="true"></span>
                </button>

                <span class="peek-hint">Tap to sign in
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 15l-6-6-6 6"></path></svg>
                </span>

                <button type="button" class="close-hint" id="closeHint">Tap to close
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>
                </button>
            </div>

            {{-- The only part that stays on screen when the sheet is docked. --}}
            <div class="sheet-head">
                <h1>Welcome back.</h1>
                <p class="m-subcopy">Buildings, leases, invoices and reports — all in one place.</p>
            </div>

            <div class="sheet-inner">

            @error('auth')
            <div class="alert-error" role="alert">
                <i class="fa-solid fa-circle-exclamation" style="margin-top:2px;"></i>
                <div>{{ $message }}</div>
            </div>
            @enderror

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
                    <a class="forgot-link" href="{{ route('password.request') }}">Forgot password?</a>
                </div>

                <button type="submit" class="btn-submit" id="submitBtnMobile">
                    <span id="submitLabelMobile">Sign in</span>
                </button>
            </form>

            <p class="legal">By signing in you agree to our <span>Terms</span> and <span>Privacy Policy</span>.</p>
            <div class="home-indicator"></div>
            </div>
        </div>
    </div>
</div>

<script>
/* ── Collapsible sign-in sheet (=<768px) ────────────────────────────────────
     Docked by default so the building photo stays visible; the form is one
     tap away. Everything here is a no-op above 768px, where .mobile-shell is
     display:none and none of these nodes are laid out. */
(function () {
    const phone  = document.querySelector('.phone');
    const sheet  = document.getElementById('loginSheet');
    const handle = document.getElementById('sheetHandle');
    const inner  = sheet && sheet.querySelector('.sheet-inner');
    const closeHint = document.getElementById('closeHint');
    const form   = document.getElementById('loginFormMobile');
    if (!phone || !sheet || !handle || !inner || !form) return;

    const isOpen = () => phone.classList.contains('is-sheet-open');

    /* Measure the peek from the real boxes rather than trusting the 140px in
       the stylesheet: the subtitle wraps to two lines on a narrow phone and
       three on the narrowest, and a clipped heading is the one thing the
       collapsed state cannot afford.

       The fold lands on the form's top edge, so the visible strip is exactly
       handle + heading + subtitle and not a sliver of the first field. The
       breathing room under the subtitle is its own 24px bottom margin, which
       sits inside the measured box. */
    function measure() {
        const peek = Math.round(
            inner.getBoundingClientRect().top - sheet.getBoundingClientRect().top
        );
        if (peek > 0) sheet.style.setProperty('--sheet-peek', peek + 'px');
    }

    /* Spec 3: a field that is focused or already carries text means the user
       is mid-task, and the sheet must not close under them. Hidden inputs are
       excluded deliberately — @csrf puts a permanently non-empty _token in
       this form, and counting it would wedge the sheet open forever. */
    function fieldBusy() {
        return Array.prototype.some.call(form.querySelectorAll('input'), function (el) {
            if (el.type === 'hidden') return false;
            return el === document.activeElement || (el.value || '').trim() !== '';
        });
    }

    function setOpen(open) {
        if (open === isOpen()) return;
        if (!open && fieldBusy()) return;
        phone.classList.toggle('is-sheet-open', open);
        /* Cheap, and keeps the peek honest if the head's height ever turns
           out to depend on the state (a themed or translated subtitle that
           wraps differently). Must run after the class lands, never before. */
        measure();
        sheet.setAttribute('aria-expanded', open ? 'true' : 'false');
        handle.setAttribute('aria-expanded', open ? 'true' : 'false');
        handle.setAttribute('aria-label', open ? 'Collapse sign-in form' : 'Expand sign-in form');
        if (!open) sheet.scrollTop = 0;
    }

    measure();
    window.addEventListener('resize', measure);
    window.addEventListener('orientationchange', measure);

    /* The handle toggles; the rest of a docked sheet is a big "open me". A tap
       inside an open sheet belongs to the form, not to the disclosure. */
    handle.addEventListener('click', function (e) {
        e.stopPropagation();
        setOpen(!isOpen());
    });
    sheet.addEventListener('click', function () {
        if (!isOpen()) setOpen(true);
    });

    /* The close hint. stopPropagation is load-bearing: without it the click
       bubbles to the handler above, which — now that setOpen(false) has
       already run — would see a closed sheet and re-open it instantly.
       setOpen carries the no-focus/no-text guard, so a half-filled form
       refuses to close here exactly as it does everywhere else. */
    if (closeHint) {
        closeHint.addEventListener('click', function (e) {
            e.stopPropagation();
            setOpen(false);
        });
    }

    /* Tapping the photo above the sheet collapses it — but not the theme
       toggle sitting up there in .hero-top. */
    phone.addEventListener('click', function (e) {
        if (sheet.contains(e.target) || e.target.closest('.hero-top')) return;
        setOpen(false);
    });

    /* Spec 3: the keyboard opening must not leave the field behind the fold.
       focusin also covers a keyboard user tabbing into the docked form — but
       not the handle, which lives inside the sheet and takes focus on the
       pointer press *before* its own click lands. Opening here first turned
       the click's toggle into a close, so a docked sheet could not be opened
       by clicking its handle at all. The handle speaks for itself. */
    sheet.addEventListener('focusin', function (e) {
        if (e.target.closest('.sheet-handle')) return;
        setOpen(true);
    });

    /* Swipe down to dismiss, swipe up to open — a handle you can only drag one
       way reads as broken. Down only counts from the top of the scroll, or it
       would fight scrolling back up through the form. */
    let y0 = null;
    sheet.addEventListener('touchstart', function (e) {
        y0 = e.touches.length === 1 ? e.touches[0].clientY : null;
    }, { passive: true });
    sheet.addEventListener('touchend', function (e) {
        if (y0 === null) return;
        const dy = e.changedTouches[0].clientY - y0;
        y0 = null;
        if (isOpen()) {
            if (dy > 56 && sheet.scrollTop <= 0) setOpen(false);
        } else if (dy < -40) {
            setOpen(true);
        }
    }, { passive: true });
})();

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
