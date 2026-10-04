# Design tokens

Source of truth: `public/css/app-core.css` §1.1 (light) and §1.2 (dark).
Read that file before adding a token. **Never hardcode a color, size, radius,
shadow, duration, or z-index** — if the value you want isn't a token, add the
token in §1.1 *and* its dark counterpart in §1.2.

## Identity

Navy chrome + gold accent. The sidebar is the darkest surface in both themes;
the accent is the only saturated color that appears without a status meaning.

```
--sidebar-bg #0B1120   --sidebar-border #1A2540   --sidebar-hover #131E35
--sidebar-active #1E2D4A
--accent #E8B86D       --accent-strong #D4A558 (hover)
--accent-dim rgba(232,184,109,.12)   --accent-glow rgba(232,184,109,.25)
--on-accent #0B1120    ← text/icon on any gold fill. Never white on gold.
```

## Surfaces & text

`--page-bg` · `--page-bg-alt` · `--card-bg` · `--card-border` · `--row-border` ·
`--row-hover` · `--text-primary` · `--text-secondary` · `--text-muted` ·
`--text-sidebar` · `--text-sidebar-active`

Rule of thumb: a card sits on `--page-bg`; a bar *inside* a card (filter bar,
table footer) uses `--page-bg-alt`; separators between rows use `--row-border`,
separators between components use `--card-border`.

## The six semantic tones

Every status surface — badge, alert, stat icon, soft button, card rail — draws
its three values from one tone set. This is what makes a status read the same
on every screen and flip correctly in dark mode from one place.

```
--tone-success-fg/-bg/-border
--tone-danger-fg/-bg/-border
--tone-warning-fg/-bg/-border
--tone-info-fg/-bg/-border
--tone-neutral-fg/-bg/-border
--tone-accent-fg/-bg/-border      ← fg is #8A6318, NOT --accent
```

`-fg` on its own `-bg` clears WCAG AA (4.5:1). The old gold badge (`--accent` on
`#FDF6EA`, ~1.7:1) failed outright — that's why `--tone-accent-fg` exists. Do not
"fix" a gold badge by reaching back for `--accent`.

The solid values `--success --danger --warning --info` are for icons, fills and
chart series only — never as text on a light tint.

## Type

```
--font-display  Outfit            page titles, KPI values, card titles
--font-body     Plus Jakarta Sans everything else
--font-data     Outfit + tabular-nums   money, counts, any numeric column
```

Money and counts **must** use `--font-data` with `font-variant-numeric:
tabular-nums` and right alignment (`.num`, `.amount-col`). This app is columns of
BHD amounts; proportional digits in a ledger never line up.

Scale — use the step, never a raw px:

```
--fs-2xs 10px  eyebrow / section label (uppercase, letter-spacing ~.06em)
--fs-xs  11px  badge, table header, meta
--fs-sm  12px  form label, help text
--fs-base 13.5px body, table cell, input, button
--fs-md  15px  card title
--fs-lg  17px  topbar title, section heading
--fs-xl  20px  stat value
--fs-2xl 24px  page title
--fs-3xl 30px  hero figure
```

Line heights: `--lh-tight 1.2` (figures/titles) · `--lh-snug 1.4` (badges,
compact) · `--lh-base 1.55` (prose, cells).

## Spacing — 4px base

`--sp-1 4` `--sp-2 8` `--sp-3 12` `--sp-4 16` `--sp-5 20` `--sp-6 24`
`--sp-7 28` `--sp-8 32` `--sp-10 40` `--sp-12 48`

Named by step, not by use — one scale serves padding, gap and margin. A value
off this scale (13px, 18px, 22px) is drift; round to the nearest step.

## Radius, elevation, controls

```
--radius 12px · --radius-sm 8px · --radius-lg 16px · --radius-pill 9999px
--shadow-sm / -md / -lg / -modal    (ambient + key, already composed)
--h-control 38px · --h-control-sm 32px · --h-control-lg 44px
--border-control 1.5px
--focus-ring 0 0 0 3px var(--accent-glow) · --focus-outline 2px solid var(--accent-strong)
```

Every interactive control derives its box from `--h-control*`. Variants change
color only — never height, padding, radius or type.

## Motion

```
--ease-out cubic-bezier(.22,1,.36,1) · --ease-spring cubic-bezier(.32,.72,0,1)
--dur-fast .15s · --dur-base .18s · --dur-slow .28s · --duration-sheet 380ms
```

Everything animated must be wrapped by, or survive, `@media
(prefers-reduced-motion: reduce)`. Animate `transform`/`opacity`; avoid animating
layout properties.

## Layers

`--z-sticky 90` `--z-tabbar 95` `--z-backdrop 99` `--z-sidebar 100`
`--z-modal 1000` `--z-sheet 1050` `--z-toast 1100`. Never write a numeric
z-index.

## Shell

`--sidebar-width 260px` · `--sheet-radius 22px` · `--scrim rgba(11,17,32,.55)`

## Dark mode

§1.2 redefines **only what changes**. The theme is applied to
`<html data-theme>` by an inline script in the layout before first paint, from
`localStorage['p7-theme']` falling back to `prefers-color-scheme`.

When you add a component:
1. Build it from tokens → it is already dark-mode correct.
2. If you had to introduce a token, add its dark value in §1.2 in the same commit.
3. Verify by setting `data-theme="dark"` on `<html>` and looking at borders,
   muted text, tinted backgrounds and any image/icon with a baked-in background.

## Legacy `--m-*` palette

§1.2b holds the older mobile-app palette used by the `.m-*` screens. **New work
uses §1.1 tokens.** Don't extend `--m-*`; it stays only until those screens are
migrated.
