@extends('layouts.admin')

@section('title', 'Dashboard')

{{-- Home has no create verb of its own, so the one floating button is the
     thing you most often arrive wanting to do. It renders inside the
     layout's bottom bar, beside the tab pill. --}}
@section('mobile-fab')
    <button type="button" class="pm-fab" onclick="openExpenseSheet()" title="Record an expense"
            aria-label="Record an expense"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
@endsection
@section('topbar-title', 'Dashboard')

{{-- The 54px shell page header — DESKTOP-UI.md §4.2. The page does not
     render a header of its own. --}}
@section('page-title', 'Dashboard')
@section('page-subtitle')
    Portfolio overview · {{ now()->format('F Y') }} ·
    {{ $stats['buildings'] === 0 ? 'No buildings yet' : 'All buildings' }}
@endsection
@section('page-actions')
    {{-- Export asks for a format rather than assuming one. It was a bare link
         to the workbook, so the PDF the same endpoint now renders had no way
         in. The shared partial owns the menu and the button, so the dashboard's
         Export is the same object as every list's. --}}
    @include('partials.export-menu', [
        'route'  => 'data.export',
        'id'     => 'export-format-menu',
        'sub'    => 'Every field, import-ready',
        'pdfSub' => 'Nested by property, for reading',
    ])
    {{-- §2 also lists a Filter button. The dashboard has nothing to filter —
         every figure is the whole portfolio for the current month — so it is
         left out rather than rendered inert. --}}
    {{-- Same .btn family as the Export beside it — page actions are one
         system on every other page, and the dashboard was the exception. --}}
    <button type="button" class="btn btn-primary" onclick="openSmartImport()">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Smart import
    </button>
@endsection

@push('styles')
<style>
/* ── STATS ─────────────────────────────────────────────── */

/* ── RECENT TABLES ──────────────────────────────────────────
     Two panels of the §4.3 card system standing side by side. They read as one
     row, so every spacing decision is shared: the chrome, header rhythm and
     type all come from app-core, and the page-local calls are only what the
     pair genuinely needs — equal heights, the softer corner, each panel's
     column proportions, and one row rhythm applied to both. */
.dash-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--sp-6);
    /* Equal-height panels: the pair reads as one band, so both edges — top and
       bottom — have to line up even though the two tables hold a different
       number of rows. The shorter panel carries the difference as quiet space
       under its last row rather than by stretching its rows out of step with
       the other table's. */
    align-items: stretch;
}
@media (max-width: 900px) { .dash-grid { grid-template-columns: 1fr; } }
/* This grid spaces its children with `gap`, so app-core's normal-flow stacking
   margin has to go — it would drop the second card --sp-5 below the first. */
.dash-grid > .card + .card { margin-top: 0; }

.dash-recent { --card-radius: var(--radius-lg); }
/* Cell box, both panels, one declaration each so nothing can half-apply.
   Horizontal: the card's own --card-pad-x, so a column, the header icon and
   the title all start on the same left edge and end on the same right one.
   Vertical: 18px around a 21px line is a 57px row; the header sits at 41px.
   These panels are read at a glance rather than scanned line by line, which
   is why they run airier than a full listing — and why the table carries no
   .is-compact: that modifier's padding shorthand (0,1,3) outranks any
   longhand written here, so the two would silently fight. */
.dash-recent thead th { padding: var(--sp-3) var(--card-pad-x); }
.dash-recent tbody td { padding: 18px var(--card-pad-x); }
/* Fixed proportions — auto widths let a column collapse to its text and leave
   the rest of the row as slack. The two panels differ because their content
   does: a building's name needs the room, a unit's condition does. */
.dash-recent table { table-layout: fixed; }
.dash-recent.is-buildings :is(th, td):nth-child(1) { width: 25%; }
.dash-recent.is-buildings :is(th, td):nth-child(2) { width: 45%; }
.dash-recent.is-buildings :is(th, td):nth-child(3) { width: 30%; }
.dash-recent.is-units :is(th, td):nth-child(1) { width: 28%; }
.dash-recent.is-units :is(th, td):nth-child(2) { width: 32%; }
.dash-recent.is-units :is(th, td):nth-child(3) { width: 40%; }
/* One line per row keeps the two tables on the same rhythm whatever the data. */
.dash-recent td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dash-code {
    font-family: var(--font-display); font-weight: 700;
    color: var(--text-primary); font-size: var(--fs-base);
}

/* ── MODAL BASE ─────────────────────────────────────────── */

/* ── IMPORT DROP ZONE (shared) ──────────────────────────── */
.import-drop-zone {
    border: 2px dashed var(--card-border); border-radius: var(--radius);
    background: var(--page-bg); padding: 36px 24px;
    text-align: center; cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.import-drop-zone:hover, .import-drop-zone.drag-over {
    border-color: var(--accent); background: var(--accent-dim);
}
.import-drop-icon { font-size: 36px; color: var(--text-muted); margin-bottom: 10px; transition: color 0.2s, transform 0.2s; }
.import-drop-zone:hover .import-drop-icon,
.import-drop-zone.drag-over .import-drop-icon { color: var(--accent); transform: translateY(-3px); }
.import-drop-label { font-family: 'Outfit', sans-serif; font-size: var(--fs-md); font-weight: 700; color: var(--text-primary); margin-bottom: 5px; }
.import-drop-sub { font-size: var(--fs-sm); color: var(--text-muted); }
.import-file-name { margin-top: var(--sp-3); font-size: var(--fs-base); font-weight: 600; color: var(--accent); min-height: 18px; }

/* ── IMPORT BANNER (error) ──────────────────────────────── */
.import-banner {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 14px 18px; border-radius: var(--radius);
    border: 1px solid; animation: bannerSlide 0.3s ease both;
}
.import-banner.error { background: var(--tone-danger-bg); border-color: var(--tone-danger-border); }
.import-banner-icon { font-size: var(--fs-md); flex-shrink: 0; padding-top: 2px; }
.import-banner.error .import-banner-icon { color: var(--tone-danger-fg); }
.import-banner-body { flex: 1; }
.import-banner-title { font-size: var(--fs-base); font-weight: 600; color: var(--text-primary); }
.import-banner-close {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: var(--fs-base); flex-shrink: 0;
    padding: 2px 4px; border-radius: 4px; transition: color 0.15s;
}
.import-banner-close:hover { color: var(--text-primary); }

/* ── SMART IMPORT RESULTS ───────────────────────────────── */
.smart-results-wrap {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 18px 22px;
    margin-bottom: var(--sp-6);
    animation: bannerSlide 0.3s ease both;
}
@keyframes bannerSlide {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.smart-results-top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px;
}
.smart-results-heading {
    font-family: 'Outfit', sans-serif; font-size: var(--fs-base); font-weight: 700;
    color: var(--text-primary); display: flex; align-items: center; gap: 8px;
}
.smart-results-close {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: var(--fs-base); padding: 2px 6px;
    border-radius: 4px; transition: color 0.15s;
}
.smart-results-close:hover { color: var(--text-primary); }
.smart-results-grid {
    display: flex; flex-wrap: wrap; gap: 10px;
}
.smart-result-card {
    display: flex; align-items: flex-start; gap: 12px;
    min-width: 160px; flex: 1;
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 12px 14px;
}
.smart-result-card.has-errors { border-color: var(--tone-warning-border); background: var(--tone-warning-bg); }
.smart-result-entity-icon {
    width: 32px; height: 32px; border-radius: var(--radius-sm);
    background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: var(--fs-base); flex-shrink: 0;
}
.smart-result-entity { font-size: var(--fs-sm); font-weight: 700; color: var(--text-primary); text-transform: capitalize; }
.smart-result-count  { font-family: 'Outfit', sans-serif; font-size: var(--fs-xl); font-weight: 800; color: var(--text-primary); line-height: 1.2; }
.smart-result-errors { margin-top: 6px; }
.smart-result-errors summary { font-size: var(--fs-xs); color: var(--tone-warning-fg); cursor: pointer; list-style: revert; }
.smart-result-errors ul { margin: 6px 0 0 14px; padding: 0; font-size: var(--fs-xs); color: var(--text-secondary); line-height: 1.8; }

/* ── SMART IMPORT MODAL ─────────────────────────────────── */
.smart-import-box { max-width: 560px; }
.smart-detect-info { margin-bottom: var(--sp-4); }
.smart-detect-label {
    font-size: var(--fs-sm); font-weight: 700; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.05em;
    margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
}
.smart-detect-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.smart-detect-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: var(--fs-sm); font-weight: 600;
    padding: 4px 10px; border-radius: var(--radius-pill);
    background: var(--tone-accent-bg); color: var(--tone-accent-fg);
    border: 1px solid var(--tone-accent-border);
}
.smart-detect-note {
    font-size: var(--fs-sm); color: var(--text-muted); margin: 0; line-height: 1.6;
}

/* ═══════════════════════════════════════════════════════════════════
   ARCHETYPE A — DASHBOARD-SPEC.md §2–§5, §7
   Supersedes DESKTOP-UI.md §5A. Every size, radius and spacing value
   below is the one the spec measured off the approved screenshot.
   Page-local, namespaced dash-.

   The pastel KPI tiles are the app's own tone tokens: §3's semantic
   table and --tone-*-bg / --tone-*-fg are the same four pairs, and the
   dark theme already defines them as 12–14% alpha fills of the same
   saturated inks, which is exactly what §7 asks for.
   ═══════════════════════════════════════════════════════════════════ */
:root {
    /* Navy and gold are the product's frame and already tokenised by the
       shell (app-core.css §7); alias them rather than restate the hex. */
    --dash-navy:       var(--shell-navy);
    --dash-gold:       var(--shell-gold);
    --dash-navy-ink:   var(--shell-nav-ink-active);
    --dash-navy-55:    rgba(255,255,255,0.55);
    --dash-navy-60:    rgba(255,255,255,0.60);
    --dash-navy-line:  rgba(255,255,255,0.12);
    --dash-gold-tint:  rgba(224,169,53,0.14);
}

/* The desktop archetype. The media query has to come AFTER the base rule:
   at equal specificity the later declaration wins, and with the order
   reversed the desktop block stayed visible at 320px and pushed the page
   30px wider than the viewport. */
.dash-desktop { display: flex; flex-direction: column; gap: 20px; }
@media (max-width: 768px) {
    /* ≤768px .m-dash takes the screen; kept in its own query so the existing
       mobile block below is untouched. */
    .dash-desktop { display: none; }
}

/* ── §4  Main band ──────────────────────────────────────────────── */
.dash-band { display: grid; grid-template-columns: 1fr 376px; gap: 20px; align-items: start; }
@media (max-width: 1200px) { .dash-band { grid-template-columns: 1fr; } }
.dash-side { display: flex; flex-direction: column; gap: 20px; }

.dash-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 14px;
    padding: var(--sp-6);
}
.dash-card-head { display: flex; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.dash-card-text { flex: 1; min-width: 0; }
.dash-card-title {
    font-family: var(--font-display);
    font-size: var(--fs-lg);
    font-weight: 700;
    color: var(--text-primary);
}
.dash-card-sub { margin-top: 2px; font-size: var(--fs-sm); color: var(--text-muted); }

.dash-legend { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.dash-legend-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    background: var(--card-bg);
    font-family: var(--font-body);
    font-size: var(--fs-sm);
    font-weight: 500;
    color: var(--text-primary);
    cursor: pointer;
}
.dash-legend-btn:hover { border-color: var(--input-border); }
.dash-legend-btn.is-off { opacity: 0.45; text-decoration: line-through; }
.dash-legend-sq { width: 6px; height: 6px; border-radius: 2px; flex: none; }

.dash-period { display: flex; justify-content: flex-end; margin-top: var(--sp-3); }
.dash-period select {
    height: 36px;
    border: 1px solid var(--card-border);
    border-radius: 9px;
    background: var(--card-bg);
    color: var(--text-primary);
    font-family: var(--font-body);
    font-size: var(--fs-sm);
    padding: 0 var(--sp-3);
}
.dash-canvas { position: relative; width: 100%; height: 260px; margin-top: var(--sp-3); }

/* Collected card — navy in both themes (§7) */
.dash-collect {
    background: var(--dash-navy);
    border-radius: 14px;
    padding: var(--sp-6);
    color: var(--dash-navy-ink);
}
.dash-collect-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.dash-collect-label {
    font-family: var(--font-body);
    font-size: var(--fs-xs);
    font-weight: 700;
    letter-spacing: 0.14em;
    color: var(--dash-navy-55);
}
.dash-collect-fig {
    margin-top: 10px;
    font-family: var(--font-data);
    font-size: 34px;
    font-weight: 700;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.dash-collect-of { margin-top: 6px; font-size: var(--fs-base); color: var(--dash-navy-60); }
.dash-collect-vault {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: var(--dash-gold-tint);
    color: var(--dash-gold);
    display: flex; align-items: center; justify-content: center;
    font-size: var(--fs-md);
    flex: none;
}
.dash-collect-pct {
    margin-top: var(--sp-5);
    text-align: right;
    font-family: var(--font-data);
    font-size: var(--fs-sm);
    font-weight: 700;
    color: var(--dash-gold);
    font-variant-numeric: tabular-nums;
}
.dash-collect-track {
    height: 5px;
    margin-top: 6px;
    border-radius: 3px;
    background: var(--dash-navy-line);
    overflow: hidden;
}
.dash-collect-fill { height: 100%; border-radius: 3px; background: var(--dash-gold); }
.dash-collect-splits { margin-top: var(--sp-5); display: grid; grid-template-columns: repeat(3, 1fr); }
.dash-split { padding-left: 14px; }
.dash-split + .dash-split { border-left: 1px solid var(--dash-navy-line); }
.dash-split:first-child { padding-left: 0; }
.dash-split-lbl {
    font-family: var(--font-body);
    font-size: var(--fs-2xs);
    font-weight: 700;
    letter-spacing: 0.09em;
    color: var(--dash-navy-55);
}
.dash-split-val {
    margin-top: var(--sp-1);
    font-family: var(--font-data);
    font-size: 19px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}
.dash-split-val.is-gold { color: var(--dash-gold); }

/* Needs your attention */
.dash-attention { padding: var(--sp-5); }
.dash-attention-label {
    font-family: var(--font-body);
    font-size: var(--fs-xs);
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--text-muted);
}
.dash-attention-list { margin-top: 14px; display: flex; flex-direction: column; gap: 10px; }
.dash-attention-row {
    min-height: 56px;
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 0 12px;
    border: 0;
    border-radius: 10px;
    background: none;
    font-family: var(--font-body);
    text-align: left;
    text-decoration: none;
    cursor: pointer;
}
.dash-attention-row:hover { background: var(--page-bg); }
.dash-attention-tile {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: var(--fs-base);
    flex: none;
}
.dash-attention-text { flex: 1; min-width: 0; }
.dash-attention-title { display: block; font-size: var(--fs-base); font-weight: 600; color: var(--text-primary); }
.dash-attention-sub { display: block; font-size: var(--fs-sm); color: var(--text-muted); }
.dash-attention-chev { font-size: var(--fs-sm); color: var(--text-muted); }
.dash-attention-empty { margin-top: 14px; font-size: var(--fs-base); color: var(--text-muted); }

/* ── §5  Property performance ───────────────────────────────────── */
.dash-props-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: var(--sp-5);
}
.dash-viewall {
    height: 36px;
    display: inline-flex;
    align-items: center;
    gap: var(--sp-2);
    padding: 0 var(--sp-4);
    border: 1px solid var(--card-border);
    border-radius: 9px;
    background: var(--card-bg);
    font-family: var(--font-body);
    font-size: var(--fs-sm);
    font-weight: 500;
    color: var(--text-primary);
    text-decoration: none;
}
.dash-viewall:hover { border-color: var(--input-border); }
.dash-props { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.dash-prop {
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    display: block;
    text-decoration: none;
    overflow: hidden;
}
.dash-prop:hover { border-color: var(--input-border); }
.dash-prop-top { display: flex; align-items: center; gap: 12px; padding: var(--sp-4); }
.dash-prop-photo {
    width: 80px; height: 64px;
    border-radius: var(--radius-sm);
    object-fit: cover;
    flex: none;
    display: block;
}
/* No photo of their building: a neutral tile, never someone else's building. */
.dash-prop-photo-empty {
    width: 80px; height: 64px;
    border-radius: var(--radius-sm);
    background: var(--page-bg);
    color: var(--text-muted);
    display: flex; align-items: center; justify-content: center;
    font-size: var(--fs-md);
    opacity: 0.35;
    flex: none;
}
.dash-prop-tile {
    width: 34px; height: 34px;
    border-radius: var(--radius-sm);
    background: var(--dash-navy);
    color: var(--dash-gold);
    font-family: var(--font-data);
    font-size: var(--fs-sm);
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex: none;
}
.dash-prop-id { flex: 1; min-width: 0; }
.dash-prop-name {
    font-size: var(--fs-base);
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dash-prop-meta {
    margin-top: 2px;
    font-size: var(--fs-sm);
    color: var(--text-muted);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dash-prop-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    border-top: 1px solid var(--card-border);
    padding: 14px 16px;
}
.dash-prop-stat { padding-left: var(--sp-3); }
.dash-prop-stat + .dash-prop-stat { border-left: 1px solid var(--card-border); }
.dash-prop-stat:first-child { padding-left: 0; }
.dash-prop-stat-lbl {
    font-family: var(--font-body);
    font-size: var(--fs-2xs);
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--text-muted);
}
.dash-prop-stat-val {
    margin-top: var(--sp-1);
    font-family: var(--font-data);
    font-size: var(--fs-md);
    font-weight: 700;
    color: var(--text-primary);
    font-variant-numeric: tabular-nums;
}
.dash-prop-stat-val.is-negative { color: var(--tone-danger-fg); }
.dash-prop-foot {
    border-top: 1px solid var(--card-border);
    padding: 12px 16px;
    font-size: var(--fs-sm);
    font-weight: 600;
    color: var(--text-secondary);
}
.dash-prop:hover .dash-prop-foot { color: var(--shell-gold); }

/* Focus, per DESKTOP-UI.md §8 */
.dash-prop:focus-visible,
.dash-attention-row:focus-visible,
.dash-legend-btn:focus-visible,
.dash-viewall:focus-visible { outline: var(--focus-outline); outline-offset: 2px; }

/* ── MOBILE DASHBOARD (hidden on desktop) ──────────────────── */
.m-dash { display: none; }

/* ── MOBILE DASHBOARD — Miknas Property Manager design ──────
     The desktop dashboard sections below are replaced wholesale by the
     .pm-* markup on mobile — not squeezed responsively — since the
     mobile spec is a different screen, not a narrower desktop one. ── */
@media (max-width: 768px) {
    .page-header, .stats-grid, .data-hero,
    .finance-card, .property-section-head, .property-grid, .dash-grid {
        display: none !important;
    }

    .m-dash {
        display: flex; flex-direction: column; font-family: 'Poppins', system-ui, sans-serif;
        position: fixed; inset: 0; z-index: 10; background: var(--ps-bg);
    }

    /* ── Segmented control ───────────────────────────────── */
    .pm-segment-wrap { padding: 16px 16px 0; }
    .pm-segment { display: flex; gap: 4px; padding: var(--sp-1); background: var(--ps-track); border-radius: var(--ps-r-btn); }
    .pm-seg-btn {
        flex: 1; min-height: 40px; border: 0; border-radius: 9px; font-size: .8rem; font-weight: 500;
        cursor: pointer; background: transparent; color: var(--ps-muted); font-family: 'Poppins', system-ui, sans-serif;
    }
    /* --ps-ink, not --ps-navy. The two are the same value in light, which is
       why this looked right for so long, but --ps-navy is the token for navy
       FILLS and never flips — so in dark the active tab was navy text on the
       dark surface it had just been given, and the selected tab was the one
       you could not read. */
    .pm-seg-btn.active { background: var(--ps-surface); color: var(--ps-ink); font-weight: 600; box-shadow: 0 2px 6px rgba(30,44,79,.10); }

    /* 12px, the same section gap .m-screen gives every list screen: Home
       sat at 14 and the lists at 12, which is the drift this consolidation
       exists to remove — the number belongs to the system, not the page. */
    .pm-dash-layout { padding: 16px 16px 0; display: flex; flex-direction: column; gap: 12px; }
    .pm-dash-layout[hidden] { display: none; }

    /* ── Occupancy card ───────────────────────────────────
         The one figure the other two are downstream of, so it leads. Three
         columns: the ring, the two counts it splits into, the way to act on
         the second of them. */
    .dashm-occ {
        display: flex; align-items: center; gap: 14px;
        background: var(--ps-surface);
        border: 1px solid var(--ps-border-soft);
        border-radius: var(--ps-r-card-sm);
        padding: 16px 14px;
        box-shadow: var(--ps-card-shadow);
    }
    .dashm-ring { flex: none; position: relative; width: 96px; height: 96px; }
    .dashm-ring svg { width: 96px; height: 96px; transform: rotate(-90deg); }
    .dashm-ring circle { fill: none; stroke-width: 10; }
    .dashm-ring-track { stroke: var(--ps-track); }
    .dashm-ring-fill { stroke: var(--ps-gold-dark); stroke-linecap: round; }
    .dashm-ring-text {
        position: absolute; inset: 0;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
    }
    .dashm-ring-pct {
        font-family: 'Poppins', system-ui, sans-serif; font-weight: 700;
        font-size: 1.1875rem; line-height: 1.1; color: var(--ps-ink);
    }
    .dashm-ring-cap {
        font-size: .46875rem; font-weight: 600; letter-spacing: .14em;
        color: var(--ps-faint); margin-top: 2px;
    }

    .dashm-occ-figures {
        flex: 1; min-width: 0;
        display: flex; flex-direction: column;
    }
    /* The rule between them is the divider the two counts share; it belongs
       to the second row so the first has no stray edge above it. */
    .dashm-occ-figure + .dashm-occ-figure {
        border-top: 1px solid var(--ps-border-soft);
        margin-top: 10px; padding-top: 10px;
    }
    /* Same label as the stat strip's, down to the token: .12em and the
       muted grey that clears AA, not the decorative one. */
    .dashm-occ-label {
        font-size: .5625rem; font-weight: 600; letter-spacing: .12em;
        color: var(--ps-muted); line-height: 1.4;
    }
    .dashm-occ-value {
        font-family: 'Poppins', system-ui, sans-serif; font-weight: 700;
        font-size: 1rem; line-height: 1.3; color: var(--ps-ink); margin-top: 2px;
    }
    .dashm-occ-value span { font-size: .75rem; font-weight: 500; color: var(--ps-muted); }

    .dashm-occ-cta {
        flex: none; display: flex; flex-direction: column; align-items: center; gap: 6px;
        width: 84px; text-decoration: none;
    }
    .dashm-occ-cta-icon {
        width: 40px; height: 40px; border-radius: var(--ps-r-pill);
        background: var(--ps-gold-tint); color: var(--ps-gold-text);
        display: flex; align-items: center; justify-content: center; font-size: 15px;
    }
    .dashm-occ-cta-text {
        font-size: .65625rem; font-weight: 600; line-height: 1.3; text-align: center;
        color: var(--ps-gold-text);
    }
    /* The chevron rides the last word, so it can never be orphaned onto a
       line of its own — which is what "List / vacancies / ›" was. */
    .dashm-occ-cta-end { white-space: nowrap; }
    .dashm-occ-cta-text i { font-size: 8px; }



    /* 320px: the occupancy card is the only thing on this screen wide
       enough to need tightening. */
    @media (max-width: 430px) {
        .dashm-occ { gap: 10px; padding: 14px 12px; }
        .dashm-occ-cta { max-width: 62px; }
    }

    /* ── Alerts sheet (the bell) ──────────────────────────── */
    /* The sheet's chrome — modal padding and footer buttons — lives in
       app-mobile.css beside the sign-out sheet's, since restyling a shared
       component from page CSS is what the cascade contract forbids. Only
       the empty state is genuinely local to this page.

       Empty state: centred, quiet, and specific about what was checked. */
    .alerts-clear { text-align: center; padding: 18px 6px 10px; }
    .alerts-clear-icon {
        width: 52px; height: 52px; margin: 0 auto 14px;
        border-radius: var(--ps-r-pill);
        background: var(--ps-gold-tint); color: var(--ps-gold-text);
        display: flex; align-items: center; justify-content: center; font-size: 22px;
    }
    .alerts-clear-title { font-size: 1rem; font-weight: 600; color: var(--ps-ink); }
    /* balance, not a hard width: 32ch left "days." alone on a third line. */
    .alerts-clear-sub {
        font-size: .8125rem; line-height: 1.6; color: var(--ps-muted);
        margin-top: 6px; max-width: 34ch; margin-left: auto; margin-right: auto;
        text-wrap: balance;
    }

    /* One-shot highlight for the block the bell scrolls to: a gold ring that
       fades to nothing, drawn with box-shadow so no layout shifts under the
       finger. Only the ring animates — recolouring the gold eyebrow was the
       obvious second cue and the wrong one, since the emphasis colour that
       reads on the light page vanishes into the dark one. */
    #pmNeedsToday.is-flash > .pm-action-list {
        border-radius: var(--ps-r-card);
        animation: pmFlashRing 1.4s ease-out;
    }
    @keyframes pmFlashRing {
        0%, 40% { box-shadow: 0 0 0 3px var(--ps-gold-tint), 0 0 0 4px var(--ps-gold); }
        100%    { box-shadow: 0 0 0 3px transparent, 0 0 0 4px transparent; }
    }
    @media (prefers-reduced-motion: reduce) {
        #pmNeedsToday.is-flash > .pm-action-list { animation: none; }
    }

    /* ── Today: navy hero ─────────────────────────────────── */
    /* The one dark surface in the mobile app: navy → navy-deep, gold
       accents, gold-dash eyebrow inverted to sit on the dark. */
    .pm-hero-card { background: var(--ps-placeholder); border-radius: var(--ps-r-card); padding: 20px; box-shadow: var(--ps-card-shadow-lift); }
    .pm-hero-top-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .pm-hero-label {
        display: flex; align-items: center; gap: 10px;
        font-size: .65rem; font-weight: 600; letter-spacing: .18em; text-transform: uppercase; color: var(--ps-gold);
    }
    .pm-hero-label::before { content: ""; flex: none; width: 26px; height: 3px; border-radius: 2px; background: var(--ps-gold); }
    .pm-hero-figure { font-family: 'Poppins', system-ui, sans-serif; font-weight: 700; font-size: 2.1rem; color: var(--ps-ink-inverse); line-height: 1.15; letter-spacing: -.01em; margin-top: var(--sp-2); }
    .pm-hero-sub { font-size: .8rem; font-weight: 500; color: var(--ps-navy-text); margin-top: 4px; }
    .pm-hero-icon { width: 44px; height: 44px; border-radius: var(--ps-r-pill); background: rgba(216,178,95,.16); color: var(--ps-gold); display: flex; align-items: center; justify-content: center; font-size: var(--fs-lg); flex-shrink: 0; }
    .pm-hero-bar { height: 6px; border-radius: var(--ps-r-pill); background: rgba(255,255,255,.12); margin-top: var(--sp-4); overflow: hidden; }
    .pm-hero-bar-fill { height: 100%; border-radius: var(--ps-r-pill); background: var(--ps-btn-grad); }
    .pm-hero-stats { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 20px; }
    .pm-hero-stat { border-left: 1px solid rgba(255,255,255,.14); padding-left: 12px; }
    .pm-hero-stat-label { font-size: .6rem; font-weight: 600; color: var(--ps-navy-text); letter-spacing: .14em; text-transform: uppercase; }
    .pm-hero-stat-value { font-family: 'Poppins', system-ui, sans-serif; font-weight: 700; font-size: 1.2rem; color: var(--ps-ink-inverse); margin-top: 3px; }

    /* ── Today: compact property rows ─────────────────────── */
    .pm-compact-row { display: flex; align-items: center; gap: 13px; min-height: var(--ps-touch); background: var(--ps-surface); border: 1px solid var(--ps-border); border-radius: var(--ps-r-card-sm); padding: 12px 15px; text-decoration: none; box-shadow: var(--ps-card-shadow); }
    .pm-compact-tile { flex: none; width: var(--ps-touch); height: var(--ps-touch); border-radius: 13px; background: var(--ps-navy); color: var(--ps-gold); font-family: 'Poppins', system-ui, sans-serif; font-weight: 600; font-size: .8rem; display: flex; align-items: center; justify-content: center; }
    .pm-compact-name { font-size: .9375rem; font-weight: 600; color: var(--ps-navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pm-compact-sub { font-size: .8rem; color: var(--ps-muted); }
    .pm-compact-figure { font-family: 'Poppins', system-ui, sans-serif; font-weight: 700; font-size: 1rem; color: var(--ps-navy); text-align: right; }
    .pm-compact-net { font-size: .7rem; font-weight: 600; text-align: right; }

    /* ── Cash flow: ledger card ───────────────────────────── */
    .pm-ledger-card { background: var(--ps-surface); border: 1px solid var(--ps-border); border-radius: var(--ps-r-card-sm); overflow: hidden; box-shadow: var(--ps-card-shadow); }
    .pm-ledger-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 15px; border-bottom: 1px solid var(--ps-border); }
    .pm-ledger-head-label { font-size: .6rem; font-weight: 600; letter-spacing: .16em; text-transform: uppercase; color: var(--ps-gold-text); }
    .pm-ledger-head-meta { font-size: .8rem; font-weight: 500; color: var(--ps-muted); }
    .pm-ledger-row { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-bottom: 1px solid var(--ps-border); }
    .pm-ledger-icon { flex: none; width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: var(--fs-sm); }
    .pm-ledger-label { font-size: .9375rem; font-weight: 600; color: var(--ps-navy); }
    .pm-ledger-meta { font-size: .8rem; color: var(--ps-muted); }
    .pm-ledger-amount { font-family: 'Poppins', system-ui, sans-serif; font-weight: 700; font-size: 1rem; }
    .pm-ledger-net { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 15px; background: var(--ps-bg); }
    .pm-ledger-net-label { font-size: .8rem; font-weight: 600; color: var(--ps-muted-deep); }
    .pm-ledger-net-value { font-family: 'Poppins', system-ui, sans-serif; font-weight: 700; font-size: 1.35rem; letter-spacing: -.01em; color: var(--ps-navy); }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
function importDragOver(e, dropId) {
    e.preventDefault();
    document.getElementById(dropId).classList.add('drag-over');
}
function importDragLeave(dropId) {
    document.getElementById(dropId).classList.remove('drag-over');
}
function importDrop(e, dropId, inputId) {
    e.preventDefault();
    document.getElementById(dropId).classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    const input = document.getElementById(inputId);
    input.files = dt.files;
    input.dispatchEvent(new Event('change'));
}
function openSmartImport() {
    document.getElementById('smartImportModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeSmartImport() {
    document.getElementById('smartImportModal').classList.remove('open');
    document.body.style.overflow = '';
}
function smartImportFileChosen(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('smartImportFileName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('smartImportDropLabel').textContent = 'File selected — ready to import';
    document.getElementById('smartImportSubmit').disabled = false;
}

/* ── PORTFOLIO FINANCIAL CHART ────────────────────────────── */
(function () {
    const canvas = document.getElementById('portfolioChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const chartData = @json($chartData);
    const ctx = canvas.getContext('2d');

    /* Chart.js takes colour values, not custom properties, so read the
       tokens off :root. Series colours stay the fixed five from §3. */
    const css = getComputedStyle(document.documentElement);
    const ink = (token) => css.getPropertyValue(token).trim();

    function gradient(hex) {
        const g = ctx.createLinearGradient(0, 0, 0, 260);
        g.addColorStop(0, hex + 'E6');
        g.addColorStop(1, hex + '1A');
        return g;
    }

    const portfolioChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Income',   data: chartData.income,   backgroundColor: gradient(ink('--chart-income')), hoverBackgroundColor: ink('--chart-income'), borderRadius: 3, borderSkipped: false, barThickness: 9 },
                { label: 'Expenses', data: chartData.expenses, backgroundColor: gradient(ink('--chart-expenses')), hoverBackgroundColor: ink('--chart-expenses'), borderRadius: 3, borderSkipped: false, barThickness: 9 },
                { label: 'Credits',  data: chartData.credits,  backgroundColor: gradient(ink('--chart-credits')), hoverBackgroundColor: ink('--chart-credits'), borderRadius: 3, borderSkipped: false, barThickness: 9 },
                { label: 'Debits',   data: chartData.debits,   backgroundColor: gradient(ink('--chart-debits')), hoverBackgroundColor: ink('--chart-debits'), borderRadius: 3, borderSkipped: false, barThickness: 9 },
                { label: 'Profit', data: chartData.profit, type: 'line', borderColor: ink('--chart-profit'), borderWidth: 2.5, tension: 0.4, fill: false, pointRadius: 4, pointHoverRadius: 7, pointBackgroundColor: ink('--card-bg'), pointBorderColor: ink('--chart-profit'), pointBorderWidth: 2 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: ink('--text-primary'), titleColor: ink('--card-bg'), bodyColor: ink('--card-bg'),
                    titleFont: { family: 'Outfit', size: 13, weight: '700' },
                    bodyFont: { family: 'Plus Jakarta Sans', size: 12.5, weight: '500' },
                    padding: 12, cornerRadius: 10, boxPadding: 6, usePointStyle: true,
                    borderColor: ink('--card-border'), borderWidth: 1,
                    callbacks: {
                        label: function (c) {
                            let l = c.dataset.label || '';
                            if (l) l += ': BHD ';
                            if (c.parsed.y !== null) l += new Intl.NumberFormat('en-US').format(c.parsed.y);
                            return l;
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    /* §4 asks for 0–1,000 in 250 steps. suggestedMax rather
                       than max so a month above 1,000 grows the axis instead
                       of clipping the bar. */
                    suggestedMax: 1000,
                    border: { display: false },
                    grid: { color: ink('--card-border'), tickLength: 0, borderDash: [4, 4] },
                    ticks: {
                        stepSize: 250,
                        padding: 10, color: ink('--text-muted'), font: { family: 'Plus Jakarta Sans', size: 11 },
                        callback: (v) => new Intl.NumberFormat('en-US').format(v),
                    },
                },
                x: { grid: { display: false }, ticks: { color: ink('--text-muted'), font: { family: 'Plus Jakarta Sans', size: 11 } } },
            },
        },
    });

    /* A theme toggle repaints the tokens; Chart.js already holds resolved
       colour values, so hand it the new ones and redraw. */
    new MutationObserver(function () {
        const o = portfolioChart.options, d = portfolioChart.data.datasets;
        d[0].backgroundColor = gradient(ink('--chart-income'));
        d[1].backgroundColor = gradient(ink('--chart-expenses'));
        d[2].backgroundColor = gradient(ink('--chart-credits'));
        d[3].backgroundColor = gradient(ink('--chart-debits'));
        d[4].pointBackgroundColor = ink('--card-bg');
        o.plugins.tooltip.backgroundColor = ink('--text-primary');
        o.plugins.tooltip.titleColor = o.plugins.tooltip.bodyColor = ink('--card-bg');
        o.plugins.tooltip.borderColor = ink('--card-border');
        o.scales.y.grid.color = ink('--card-border');
        o.scales.y.ticks.color = o.scales.x.ticks.color = ink('--text-muted');
        portfolioChart.update('none');
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    document.querySelectorAll('.dash-legend-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const i = Number(btn.dataset.dataset);
            const meta = portfolioChart.getDatasetMeta(i);
            meta.hidden = meta.hidden === null ? !portfolioChart.data.datasets[i].hidden : !meta.hidden;
            btn.classList.toggle('is-off', meta.hidden === true);
            portfolioChart.update();
        });
    });
})();

/* ── MOBILE DASHBOARD: Portfolio / Today / Cash flow segmented control ── */
(function () {
    const segment = document.getElementById('pmSegment');
    if (!segment) return;

    const buttons = segment.querySelectorAll('.pm-seg-btn');
    const layouts = document.querySelectorAll('.pm-dash-layout');
    const STORAGE_KEY = 'pm-dash-tab';

    function show(tab) {
        buttons.forEach((b) => b.classList.toggle('active', b.dataset.seg === tab));
        layouts.forEach((l) => { l.hidden = l.dataset.layout !== tab; });
    }

    buttons.forEach((btn) => {
        btn.addEventListener('click', function () {
            localStorage.setItem(STORAGE_KEY, btn.dataset.seg);
            show(btn.dataset.seg);
        });
    });

    show(localStorage.getItem(STORAGE_KEY) || 'cards');

    if (window.pmInitSegmentThumb) window.pmInitSegmentThumb(segment);

    /* The bell (and a #today link arriving from another mobile screen) lands
       on the same place: the Today segment, scrolled to the alert list, with
       one brief highlight so the jump is legible rather than a silent
       re-render. The segment choice is persisted like a manual tap, so the
       tab bar doesn't snap back to Portfolio on the next visit. */
    window.pmRevealAlerts = function () {
        localStorage.setItem(STORAGE_KEY, 'pulse');
        show('pulse');

        const target = document.getElementById('pmNeedsToday');
        const scroll = document.getElementById('pmDashScroll');
        if (!target || !scroll) return;

        requestAnimationFrame(function () {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            scroll.scrollTo({
                top: Math.max(0, target.offsetTop - 12),
                behavior: reduce ? 'auto' : 'smooth',
            });
            target.classList.remove('is-flash');
            void target.offsetWidth;
            target.classList.add('is-flash');
            setTimeout(() => target.classList.remove('is-flash'), 1400);
        });
    };

    if (window.location.hash === '#today') window.pmRevealAlerts();
})();

/* ── MOBILE DASHBOARD: the bell's alerts sheet ────────────────────
   The bell opens a sheet rather than re-selecting a tab: switching to
   Today is invisible when Today is already the remembered tab, which
   made every tap after the first one look like nothing happened.

   Opening and closing it is [data-sheet-open] / [data-sheet-close] in the
   markup — the layout's one sheet handler does the aria-expanded, the
   scroll lock, the focus move and Escape. All that is left here is the one
   behaviour that belongs to this sheet: the row that hands off to the full
   list, which has to close before it scrolls. */
(function () {
    document.getElementById('alertsOpenToday')?.addEventListener('click', () => {
        if (window.pmRevealAlerts) window.pmRevealAlerts();
    });
})();

/* ── MOBILE DASHBOARD: collapsing large title + pull-to-refresh ──── */
(function () {
    const header = document.getElementById('pmDashHeader');
    const scroll = document.getElementById('pmDashScroll');
    if (window.pmInitCollapsingHeader) window.pmInitCollapsingHeader(header, scroll, 24);
    if (window.pmInitPullToRefresh) window.pmInitPullToRefresh(scroll);
})();
</script>
@endpush

@section('content')

@php
    $portfolioIncome = $buildingPerformance->sum('total_income');
    $portfolioNet = $buildingPerformance->sum('net_income');
    $portfolioExpense = $portfolioIncome - $portfolioNet;
    $elecTotal  = $buildingPerformance->sum(fn ($p) => $p['expenses']['electricity']);
    $waterTotal = $buildingPerformance->sum(fn ($p) => $p['expenses']['water']);
    $maintTotal = $buildingPerformance->sum(fn ($p) => $p['expenses']['maintenance']);
    $otherTotal = $buildingPerformance->sum(fn ($p) => $p['expenses']['other']);

    /* The bell, this segment, and the badge on every other screen all read
       one feed now: App\Services\AttentionFeed, shared onto the layout as
       $attentionItems / $attentionCount by AppServiceProvider.

       This page used to derive its own list from $portfolioMetrics, and the
       two disagreed on four separate axes — the feed counts records where
       this counted categories (three rows, so it could never exceed 3), it
       reads Invoice::status='overdue' where this read overdue *tenants*, it
       splits maintenance into assessed-and-waiting versus not-yet-assessed
       where this had one "open", and it drops the accounting items for a
       Maintenance user where this showed them to everyone. That is why the
       same bell read 1 here and 2 on Buildings.

       $attentionRows is presentation only: the feed's shape with the icon
       prefix and the `href` key the two Today lists below expect. No count
       is derived from it — $attentionCount is the number, everywhere. */
    $attentionRows = array_map(fn ($item) => [
        'tone'  => $item['tone'],
        'icon'  => 'fa-solid '.$item['icon'],
        'title' => $item['title'],
        'sub'   => $item['sub'],
        'href'  => $item['url'],
    ], $attentionItems);

    // Smart import is appended rather than being one of them — it is an
    // always-present shortcut, not something that needs you, so it must not
    // reach the bell or its count.
    $needsToday = array_merge($attentionRows, [[
        'icon' => 'fa-solid fa-wand-magic-sparkles',
        'title' => 'Smart import',
        'sub' => 'Bring in properties from a spreadsheet',
        'onclick' => 'openSmartImport()',
    ]]);
@endphp

{{-- ═══════════════════════════════ MOBILE DASHBOARD ═══════════════════════════════ --}}
@php
    $mHour = (int) now()->format('G');
    $mGreeting = $mHour < 12 ? 'Good morning' : ($mHour < 17 ? 'Good afternoon' : 'Good evening');
@endphp
<div class="m-dash">
    {{-- The eyebrow is its own full-width row, above the title/controls row.
         Beside the three 44px controls it had ~133px to work with, and
         "GOOD AFTERNOON, {NAME}" at .18em tracking wrapped onto a second
         line for every name — an eyebrow row that isn't a row. --}}
    <div class="pm-header is-collapsible" id="pmDashHeader">
        {{-- .pm-greeting::before already draws the language's gold dash, so
             the eyebrow needs no element of its own — adding one drew two. --}}
        <div class="pm-greeting">{{ $mGreeting }}, {{ explode(' ', auth()->user()->name ?? 'there')[0] }}</div>
        <div class="pm-header-row">
        <div class="pm-header-text">
            <div class="pm-title is-lg" id="pmDashTitle">Dashboard</div>
            <div class="pm-subtitle">{{ now()->format('F Y') }} &middot; {{ $stats['buildings'] }} {{ \Illuminate\Support\Str::plural('property', $stats['buildings']) }}</div>
        </div>
        {{-- The controls are their own 8px row, the same element the compact
             variant has in .topbar-actions, so the gap between the discs is
             set in one place and the title↔controls gutter in another. --}}
        <div class="pm-header-actions">
        {{-- The theme toggle moved to More. Three 44px discs plus the title
             left the title truncating at 320px, and switching theme is a
             once-a-day action sitting beside two you use constantly. --}}
        {{-- The bell was inert with a live red dot on it — it promised unread
             items and did nothing when tapped. The alerts it was hinting at
             already exist as "NEEDS YOU TODAY" in the Today segment, so the
             bell now takes you straight there instead of to a dead tooltip. --}}
        <button type="button" class="pm-icon-btn" id="pmAlertsBtn" title="Alerts"
                data-sheet-open="alertsSheet"
                aria-haspopup="dialog" aria-controls="alertsSheet" aria-expanded="false"
                aria-label="{{ $attentionCount > 0
                    ? $attentionCount . ' ' . \Illuminate\Support\Str::plural('alert', $attentionCount) . ' — show what needs you today'
                    : 'No alerts — show what needs you today' }}">
            <i class="fa-regular fa-bell" aria-hidden="true"></i>
            {{-- A counter, not a dot: the compact header's bell already shows
                 one, and two spellings of "unread" across two screens is the
                 thing the header system is meant to stop. The count is known
                 here, so it is shown. --}}
            @include('partials.bell-badge', ['count' => $attentionCount])
        </button>
        {{-- Asks first. This avatar is 44px from the notification bell and the
             theme toggle, and a single tap used to end the session outright —
             the same mistake the More sheet's sign-out row already guards
             against, and it hands off to the same #signOutDialog sheet. --}}
        <form method="POST" action="{{ route('logout') }}" data-signout-form>
            @csrf
            <x-avatar class="pm-avatar" tag="button" type="submit" title="Sign out"
                      aria-label="Sign out of {{ auth()->user()->email ?? 'this account' }}" />
        </form>
        </div>
        </div>
    </div>

    <div class="pm-scroll" id="pmDashScroll">
        <div class="pm-segment-wrap">
            <div class="pm-segment" id="pmSegment">
                <button type="button" class="pm-seg-btn active" data-seg="cards">Portfolio</button>
                <button type="button" class="pm-seg-btn" data-seg="pulse">Today</button>
                <button type="button" class="pm-seg-btn" data-seg="ledger">Cash flow</button>
            </div>
        </div>

        {{-- ── Portfolio layout ─────────────────────────────────── --}}
        <div class="pm-dash-layout" data-layout="cards">

            {{-- Occupancy leads, because it is the one number that explains
                 the other two: money collected and money outstanding are both
                 downstream of how much of the portfolio is let. The ring is an
                 SVG rather than a conic-gradient so the rounded cap and the
                 track render identically in both themes. --}}
            @php
                $occPct    = (int) $portfolioMetrics['occupancyPct'];
                $unitsLet  = (int) $stats['occupied'];
                $unitsAll  = (int) $stats['units'];
                $vacant    = max(0, $unitsAll - $unitsLet);
                // r = (96 - stroke 10) / 2; the dash array is the full
                // circumference so the offset can be read as "the part not yet
                // filled" rather than a magic number.
                $ringCirc  = 2 * M_PI * 43;
                $ringFill  = $ringCirc * (1 - min(100, max(0, $occPct)) / 100);
            @endphp
            <div class="dashm-occ">
                <div class="dashm-ring" role="img"
                     aria-label="{{ $occPct }}% of the portfolio is occupied — {{ $unitsLet }} of {{ $unitsAll }} {{ \Illuminate\Support\Str::plural('unit', $unitsAll) }} let">
                    <svg viewBox="0 0 96 96" aria-hidden="true">
                        <circle class="dashm-ring-track" cx="48" cy="48" r="43"></circle>
                        <circle class="dashm-ring-fill" cx="48" cy="48" r="43"
                                stroke-dasharray="{{ round($ringCirc, 2) }}"
                                stroke-dashoffset="{{ round($ringFill, 2) }}"></circle>
                    </svg>
                    <div class="dashm-ring-text" aria-hidden="true">
                        <div class="dashm-ring-pct">{{ $occPct }}%</div>
                        <div class="dashm-ring-cap">OCCUPIED</div>
                    </div>
                </div>

                <div class="dashm-occ-figures">
                    <div class="dashm-occ-figure">
                        <div class="dashm-occ-label">UNITS LET</div>
                        <div class="dashm-occ-value">{{ $unitsLet }} <span>of {{ $unitsAll }}</span></div>
                    </div>
                    <div class="dashm-occ-figure">
                        <div class="dashm-occ-label">VACANT</div>
                        <div class="dashm-occ-value">{{ $vacant }} <span>{{ \Illuminate\Support\Str::plural('unit', $vacant) }}</span></div>
                    </div>
                </div>

                {{-- The vacancies are the action the card exists to offer, so
                     it links to that list already filtered rather than to the
                     units index for the reader to narrow themselves. --}}
                <a class="dashm-occ-cta" href="{{ route('property-units.index', ['occupancy' => 'vacant']) }}"
                   aria-label="List the {{ $vacant }} vacant {{ \Illuminate\Support\Str::plural('unit', $vacant) }}">
                    <span class="dashm-occ-cta-icon"><i class="fa-solid fa-door-open" aria-hidden="true"></i></span>
                    <span class="dashm-occ-cta-text">List <span class="dashm-occ-cta-end">vacancies <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span></span>
                </a>
            </div>

            {{-- Money, on the shared stat strip — the same object the list
                 screens use, wearing the one variant that exists: the navy
                 plate. This screen is the reason the variant exists. The
                 ring above is portfolio *state* and the rows below are
                 destinations; these three numbers are the subject, and on a
                 page of white cards the only way to say that is a change of
                 surface. One plate, lit from the top right.

                 The gold picks out exactly one of the three. Collected and
                 billed are history; what is owed is the only figure that
                 asks for something, and the row directly beneath it is the
                 asking. A zero owed asks for nothing and takes the quiet
                 ink instead — see .is-zero in app-mobile.css. --}}
            @php $owed = (float) $portfolioMetrics['outstanding']; @endphp
            <div class="ps-stat-strip is-navy">
                <div class="ps-stat">
                    <div class="ps-stat-value {{ \App\Support\MoneyFormat::isZero($portfolioMetrics['collected']) ? 'is-zero' : '' }}">{{ \App\Support\MoneyFormat::figure($portfolioMetrics['collected']) }}</div>
                    <div class="ps-stat-label">{{ now()->format('M') }} BHD</div>
                </div>
                <div class="ps-stat">
                    <div class="ps-stat-value {{ \App\Support\MoneyFormat::isZero($portfolioMetrics['billed']) ? 'is-zero' : '' }}">{{ \App\Support\MoneyFormat::figure($portfolioMetrics['billed']) }}</div>
                    <div class="ps-stat-label">Billed BHD</div>
                </div>
                <div class="ps-stat">
                    <div class="ps-stat-value is-owed {{ \App\Support\MoneyFormat::isZero($owed) ? 'is-zero' : '' }}">{{ \App\Support\MoneyFormat::figure($owed) }}</div>
                    <div class="ps-stat-label">Owed BHD</div>
                </div>
            </div>

            {{-- Only when something is actually owed: a zero here is good
                 news and gets no call to action. When it does appear it is
                 the one alert on the screen, so it wears red rather than
                 the gold tile every other row wears — gold is the brand's
                 accent, red is "this needs you", and a row that mixes the
                 two says neither. --}}
            @if($owed > 0)
                <a class="pm-action-row is-alert" href="{{ route('invoices.index', ['status' => 'overdue']) }}">
                    <span class="pm-action-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
                    <span style="flex:1;min-width:0;">
                        <span class="pm-action-title">Chase what's owed</span>
                        <span class="pm-action-sub">BHD {{ number_format($owed, 0) }} across overdue invoices</span>
                    </span>
                    <i class="fa-solid fa-chevron-right pm-action-chevron" aria-hidden="true"></i>
                </a>
            @endif

            {{-- A row per building, on the shared list row — the same object
                 Buildings and every other list screen is built from. It used
                 to be a near-identical copy of it under its own .dashm-prop
                 names. --}}
            <div>
                <div class="pm-section-label">By property</div>
                <div class="m-row-list">
                    @forelse($buildingPerformance as $perf)
                        @php
                            $b = $perf['building'];
                            $photo = $b->images->first()?->url;
                            $occ = (int) $perf['occupancy_percent'];
                            $step = min(8, $loop->index);
                        @endphp
                        <a href="{{ route('buildings.show', $b) }}" class="m-row-card ps-reveal" style="--ps-step:{{ $step }}">
                            <span class="m-row-thumb">
                                @if($photo)
                                    <img src="{{ $photo }}" alt="" loading="lazy">
                                @else
                                    <i class="fa-regular fa-building" aria-hidden="true"></i>
                                @endif
                            </span>
                            <span class="m-row-text">
                                <span class="m-row-title">{{ $b->property_name }}</span>
                                @if($b->city)<span class="m-row-sub">{{ $b->city }}</span>@endif
                            </span>
                            <span class="m-row-occ">
                                <span class="m-row-bar">
                                    <span class="m-row-bar-fill" style="--ps-pct:{{ $occ }}%;--ps-step:{{ $step }}"></span>
                                </span>
                                <span class="m-row-pct">{{ $occ }}%</span>
                            </span>
                            <i class="fa-solid fa-chevron-right m-row-chevron" aria-hidden="true"></i>
                        </a>
                    @empty
                        <div class="pm-empty">No properties yet</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Today layout ─────────────────────────────────────── --}}
        <div class="pm-dash-layout" data-layout="pulse" hidden>
            <div class="pm-hero-card">
                <div class="pm-hero-top-row">
                    <div>
                        <div class="pm-hero-label">PORTFOLIO &mdash; {{ strtoupper(now()->format('F Y')) }}</div>
                        <div class="pm-hero-figure">BHD {{ number_format($portfolioMetrics['collected'], 0) }}</div>
                        <div class="pm-hero-sub">collected of BHD {{ number_format($portfolioMetrics['billed'], 0) }} billed</div>
                    </div>
                    <div class="pm-hero-icon"><i class="fa-solid fa-building-columns"></i></div>
                </div>
                <div class="pm-hero-bar"><div class="pm-hero-bar-fill" style="width:{{ $portfolioMetrics['collectedPct'] }}%"></div></div>
                <div class="pm-hero-stats">
                    <div class="pm-hero-stat"><div class="pm-hero-stat-label">UNITS</div><div class="pm-hero-stat-value">{{ $stats['units'] }}</div></div>
                    <div class="pm-hero-stat"><div class="pm-hero-stat-label">OCCUPANCY</div><div class="pm-hero-stat-value" style="color:var(--ps-gold-text);">{{ $portfolioMetrics['occupancyPct'] }}%</div></div>
                    <div class="pm-hero-stat"><div class="pm-hero-stat-label">OVERDUE</div><div class="pm-hero-stat-value" style="color:var(--pm-red);">{{ $portfolioMetrics['overdueCount'] }}</div></div>
                </div>
            </div>

            <div id="pmNeedsToday">
                <div class="pm-section-label">NEEDS YOU TODAY</div>
                <div class="pm-action-list">
                    @foreach($needsToday as $item)
                        @if(isset($item['onclick']))
                        <button type="button" class="pm-action-row" style="width:100%;border:1px solid var(--pm-border);cursor:pointer;font-family:inherit;" onclick="{{ $item['onclick'] }}">
                        @else
                        <a href="{{ $item['href'] }}" class="pm-action-row">
                        @endif
                            <div class="pm-action-icon"><i class="{{ $item['icon'] }}"></i></div>
                            <div style="flex:1;min-width:0;text-align:left;">
                                <div class="pm-action-title">{{ $item['title'] }}</div>
                                <div class="pm-action-sub">{{ $item['sub'] }}</div>
                            </div>
                            <i class="fa-solid fa-chevron-right pm-action-chevron"></i>
                        @if(isset($item['onclick']))
                        </button>
                        @else
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>

            <div>
                <div class="pm-section-label">PROPERTIES</div>
                <div class="pm-action-list">
                    @forelse($buildingPerformance as $perf)
                        @php
                            $b = $perf['building'];
                            $initials = collect(explode(' ', $b->property_name))->map(fn($w) => $w[0] ?? '')->take(2)->implode('');
                            $netColor = $perf['net_income'] >= 0 ? 'var(--pm-text-2)' : 'var(--pm-red)';
                        @endphp
                        <a href="{{ route('buildings.show', $b) }}" class="pm-compact-row">
                            <div class="pm-compact-tile">{{ strtoupper($initials) }}</div>
                            <div style="flex:1;min-width:0;">
                                <div class="pm-compact-name">{{ $b->property_name }}</div>
                                <div class="pm-compact-sub">{{ $perf['occupancy_percent'] }}% occupied</div>
                            </div>
                            <div>
                                <div class="pm-compact-figure">BHD {{ number_format($perf['total_income'], 0) }}</div>
                                <div class="pm-compact-net" style="color:{{ $netColor }};">net BHD {{ number_format($perf['net_income'], 0) }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="pm-empty">No properties yet</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Cash flow layout ─────────────────────────────────── --}}
        <div class="pm-dash-layout" data-layout="ledger" hidden>
            <div class="pm-ledger-card">
                <div class="pm-ledger-head">
                    <div class="pm-ledger-head-label">{{ strtoupper(now()->format('F')) }} LEDGER</div>
                    <div class="pm-ledger-head-meta">BHD, month to date</div>
                </div>
                <div class="pm-ledger-row">
                    {{-- Gold, not green. The palette on this screen is navy,
                         gold, and red for true alerts only — income is not an
                         alert and it is not a fourth colour either. The sign
                         and the label carry the direction. --}}
                    <div class="pm-ledger-icon" style="background:var(--ps-gold-tint);color:var(--ps-gold-text);"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="pm-ledger-label">Rent collected</div>
                        <div class="pm-ledger-meta">Accrued across {{ $stats['buildings'] }} {{ \Illuminate\Support\Str::plural('property', $stats['buildings']) }}</div>
                    </div>
                    <div class="pm-ledger-amount" style="color:var(--ps-ink);">+{{ number_format($portfolioIncome, 0) }}</div>
                </div>
                @if($portfolioMetrics['outstanding'] > 0)
                <div class="pm-ledger-row">
                    <div class="pm-ledger-icon" style="background:var(--pm-red-tint);color:var(--pm-red);"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="pm-ledger-label">Rent outstanding</div>
                        <div class="pm-ledger-meta">{{ $portfolioMetrics['overdueCount'] }} {{ \Illuminate\Support\Str::plural('tenant', $portfolioMetrics['overdueCount']) }} overdue</div>
                    </div>
                    <div class="pm-ledger-amount" style="color:var(--pm-red);">-{{ number_format($portfolioMetrics['outstanding'], 0) }}</div>
                </div>
                @endif
                @if($elecTotal + $waterTotal > 0)
                <div class="pm-ledger-row">
                    <div class="pm-ledger-icon" style="background:var(--pm-warn-tint);color:var(--pm-warn);"><i class="fa-solid fa-bolt"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="pm-ledger-label">Utilities</div>
                        <div class="pm-ledger-meta">Electricity &amp; water</div>
                    </div>
                    <div class="pm-ledger-amount" style="color:var(--pm-text-2);">-{{ number_format($elecTotal + $waterTotal, 0) }}</div>
                </div>
                @endif
                @if($maintTotal > 0)
                <div class="pm-ledger-row">
                    <div class="pm-ledger-icon" style="background:var(--pm-info);color:var(--ink-on-fill);background-color:rgba(59,130,246,.12);color:var(--pm-info);"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="pm-ledger-label">Maintenance</div>
                        <div class="pm-ledger-meta">Approved repairs</div>
                    </div>
                    <div class="pm-ledger-amount" style="color:var(--pm-text-2);">-{{ number_format($maintTotal, 0) }}</div>
                </div>
                @endif
                @if($otherTotal > 0)
                <div class="pm-ledger-row">
                    <div class="pm-ledger-icon" style="background:var(--pm-page);color:var(--pm-text-2);"><i class="fa-solid fa-receipt"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="pm-ledger-label">Other expenses</div>
                        <div class="pm-ledger-meta">Recorded this month</div>
                    </div>
                    <div class="pm-ledger-amount" style="color:var(--pm-text-2);">-{{ number_format($otherTotal, 0) }}</div>
                </div>
                @endif
                <div class="pm-ledger-net">
                    <div class="pm-ledger-net-label">NET POSITION</div>
                    <div class="pm-ledger-net-value">BHD {{ number_format($portfolioNet, 0) }}</div>
                </div>
            </div>

            <div>
                <div class="pm-section-label">Occupancy by property</div>
                <div class="m-row-list">
                    @forelse($buildingPerformance as $perf)
                        @php
                            $b = $perf['building'];
                            $pct = min(100, max(0, (int) $perf['occupancy_percent']));
                            $step = min(8, $loop->index);
                        @endphp
                        <a href="{{ route('buildings.show', $b) }}" class="m-row-card ps-reveal" style="--ps-step:{{ $step }}">
                            <span class="m-row-thumb"><i class="fa-regular fa-building" aria-hidden="true"></i></span>
                            <span class="m-row-text">
                                <span class="m-row-title">{{ $b->property_name }}</span>
                                @if($b->city)<span class="m-row-sub">{{ $b->city }}</span>@endif
                            </span>
                            <span class="m-row-occ">
                                <span class="m-row-bar">
                                    <span class="m-row-bar-fill" style="--ps-pct:{{ $pct }}%;--ps-step:{{ $step }}"></span>
                                </span>
                                <span class="m-row-pct">{{ $pct }}%</span>
                            </span>
                            <i class="fa-solid fa-chevron-right m-row-chevron" aria-hidden="true"></i>
                        </a>
                    @empty
                        <div class="pm-empty">No properties yet</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ═══════════════════════ ALERTS SHEET (the bell) ═══════════════════════
     Where the header bell goes. Tapping it used to switch the segmented
     control to "Today" and scroll the alert list into view — which is a
     silent no-op once Today is already the remembered tab, so from the
     second tap onwards the bell looked dead. A sheet always answers.

     It reads the same shared feed the Today segment does; the tones are the
     app's six semantic ones, so overdue money is danger, an open request is
     warning, and a lease running out is info. --}}
<div class="modal-overlay" id="alertsSheet" role="dialog" aria-modal="true" aria-labelledby="alertsSheetTitle">
    <div class="modal-box" style="--modal-w:440px;">
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon"><i class="fa-regular fa-bell" aria-hidden="true"></i></div>
                <div class="modal-header-text">
                    <div class="modal-header-title" id="alertsSheetTitle">Alerts</div>
                    <div class="modal-header-sub">
                        @if($attentionCount)
                            {{ $attentionCount }} {{ \Illuminate\Support\Str::plural('thing', $attentionCount) }} {{ $attentionCount === 1 ? 'needs' : 'need' }} you today
                        @else
                            {{ now()->format('l, j F') }}
                        @endif
                    </div>
                </div>
                <button type="button" class="modal-close-btn" data-sheet-close aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="modal-body">
            @if($attentionCount)
                <div class="pm-action-list">
                    @foreach($attentionRows as $alert)
                        <a href="{{ $alert['href'] }}" class="pm-action-row">
                            <div class="pm-action-icon" style="background:var(--tone-{{ $alert['tone'] }}-bg);color:var(--tone-{{ $alert['tone'] }}-fg);">
                                <i class="{{ $alert['icon'] }}" aria-hidden="true"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="pm-action-title">{{ $alert['title'] }}</div>
                                <div class="pm-action-sub">{{ $alert['sub'] }}</div>
                            </div>
                            <i class="fa-solid fa-chevron-right pm-action-chevron" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            @else
                {{-- An empty bell still has to say something. Naming the three
                     things it checked is what makes "nothing" trustworthy. --}}
                <div class="alerts-clear">
                    <div class="alerts-clear-icon"><i class="fa-regular fa-circle-check" aria-hidden="true"></i></div>
                    <div class="alerts-clear-title">You're all clear</div>
                    <div class="alerts-clear-sub">No overdue rent, no open requests, and no leases ending in the next 30 days.</div>
                </div>
            @endif
        </div>

        <div class="modal-footer alerts-footer">
            <button type="button" class="btn btn-outline" id="alertsOpenToday" data-sheet-close>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Open the Today view
            </button>
        </div>
    </div>
</div>

@include('components.expense-sheet')

{{-- ═══════════════════════ DESKTOP — ARCHETYPE A ═══════════════════════
     DASHBOARD-SPEC.md §3 → §5, in that order: KPI strip → main band →
     property performance → two-up recent tables. The page header (§2) is the
     shell's, declared in the @section blocks at the top of this file.

     Presentation only: every figure comes from a variable the controller
     already passed. Nothing here queries.

     No trend chips (§3): a chip needs a real prior-period comparison and the
     controller exposes none, and the spec says not to invent one. ── --}}
<div class="dash-desktop">

    {{-- ── §3  KPI strip ──────────────────────────────────────────── --}}
    @php
        /* Current-month portfolio expense. buildingPerformance is already
           scoped to this month and net = income − expense, so this is
           arithmetic on data in hand rather than another query. */
        $kpis = [
            [
                'href' => route('buildings.index'),
                'icon' => 'fa-building',   'tone' => 'blue',
                'label' => 'Buildings',    'money' => false,
                'value' => number_format($stats['buildings']),
                'sub'   => $stats['floors'].' '.Str::plural('floor', $stats['floors'])
                            .' · '.$stats['units'].' '.Str::plural('unit', $stats['units']),
            ],
            [
                'href' => route('property-units.index'),
                'icon' => 'fa-door-open',  'tone' => 'blue',
                'label' => 'Occupancy',    'money' => false,
                'value' => $portfolioMetrics['occupancyPct'].'%',
                'sub'   => $stats['occupied'].' of '.$stats['units'].' units let',
            ],
            [
                'href' => route('invoices.index'),
                'icon' => 'fa-file-invoice-dollar', 'tone' => 'amber',
                'label' => 'Billed',       'money' => true,
                'value' => number_format($portfolioMetrics['billed'], 0),
                'sub'   => now()->format('F Y'),
            ],
            [
                'href' => route('payments.index'),
                'icon' => 'fa-money-bill-transfer', 'tone' => 'green',
                'label' => 'Collected',    'money' => true,
                'value' => number_format($portfolioMetrics['collected'], 0),
                'sub'   => $portfolioMetrics['collectedPct'].'% of billed',
            ],
            [
                'href' => route('expenses.index'),
                'icon' => 'fa-arrow-trend-down', 'tone' => 'red',
                'label' => 'Expenses',     'money' => true,
                'value' => number_format($portfolioExpense, 0),
                'sub'   => now()->format('F Y'),
            ],
        ];

        /* Tone map for the attention rows. The KPI cards use .stat-icon's
           own tone modifiers, which are the same tokens. */
        $tone = [
            'info'    => 'background:var(--tone-info-bg);color:var(--tone-info-fg);',
            'warning' => 'background:var(--tone-warning-bg);color:var(--tone-warning-fg);',
            'success' => 'background:var(--tone-success-bg);color:var(--tone-success-fg);',
            'danger'  => 'background:var(--tone-danger-bg);color:var(--tone-danger-fg);',
        ];
    @endphp

    {{-- The shared KPI component (app-core §4.4). The dashboard has no KPI
         card of its own, which is what keeps its strip identical to the one
         on Buildings, Invoices or Maintenance. --}}
    <div class="stats-grid is-5 card-reveal">
        @foreach($kpis as $kpi)
            <a href="{{ $kpi['href'] }}" class="stat-card">
                <div class="stat-card-top">
                    <span class="stat-icon {{ $kpi['tone'] }}"><i class="fa-solid {{ $kpi['icon'] }}" aria-hidden="true"></i></span>
                    <span class="stat-lbl">{{ $kpi['label'] }}</span>
                </div>
                <div class="stat-val">@if($kpi['money'])<span class="stat-cur">BHD</span>@endif{{ $kpi['value'] }}</div>
                <div class="stat-foot">
                    <span class="stat-sub">{{ $kpi['sub'] }}</span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- ── §4  Main band ──────────────────────────────────────────── --}}
    <div class="dash-band card-reveal">

        <div class="dash-card reveal-item">
            <div class="dash-card-head">
                <div class="dash-card-text">
                    <div class="dash-card-title">Portfolio financial overview</div>
                    <div class="dash-card-sub">Income, expenses, credits, debits and profit · BHD</div>
                </div>
                @if(collect($chartData['income'])->filter(fn($v) => $v !== null)->isNotEmpty())
                    <div class="dash-legend">
                        <button type="button" class="dash-legend-btn" data-dataset="0"><span class="dash-legend-sq" style="background:var(--chart-income);"></span>Income</button>
                        <button type="button" class="dash-legend-btn" data-dataset="1"><span class="dash-legend-sq" style="background:var(--chart-expenses);"></span>Expenses</button>
                        <button type="button" class="dash-legend-btn" data-dataset="2"><span class="dash-legend-sq" style="background:var(--chart-credits);"></span>Credits</button>
                        <button type="button" class="dash-legend-btn" data-dataset="3"><span class="dash-legend-sq" style="background:var(--chart-debits);"></span>Debits</button>
                        <button type="button" class="dash-legend-btn" data-dataset="4"><span class="dash-legend-sq" style="background:var(--chart-profit);"></span>Profit</button>
                    </div>
                @endif
            </div>

            <div class="dash-period">
                <label class="sr-only" for="dashPeriod">Period</label>
                {{-- One option: the controller builds the series for the
                     current year only, so there is nothing else to offer
                     without a new query. --}}
                <select id="dashPeriod" disabled title="Only the current year is available">
                    <option>This year — {{ $chartYear }}</option>
                </select>
            </div>

            @if(collect($chartData['income'])->filter(fn($v) => $v !== null)->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></div>
                    <h4>No financial activity yet</h4>
                    <p>Nothing has been billed, collected or spent in {{ $chartYear }} so far.</p>
                </div>
            @else
                <div class="dash-canvas">
                    <canvas id="portfolioChart" role="img"
                            aria-label="Monthly income, expenses, credits, debits and profit for {{ $chartYear }}, in BHD"></canvas>
                </div>
            @endif
        </div>

        <div class="dash-side reveal-item">
            <div class="dash-collect">
                <div class="dash-collect-top">
                    <div>
                        <div class="dash-collect-label">COLLECTED — {{ Str::upper(now()->format('F')) }}</div>
                        <div class="dash-collect-fig">BHD {{ number_format($portfolioMetrics['collected'], 0) }}</div>
                        <div class="dash-collect-of">of BHD {{ number_format($portfolioMetrics['billed'], 0) }} billed</div>
                    </div>
                    <span class="dash-collect-vault"><i class="fa-solid fa-vault" aria-hidden="true"></i></span>
                </div>
                <div class="dash-collect-pct">{{ $portfolioMetrics['collectedPct'] }}%</div>
                <div class="dash-collect-track">
                    <div class="dash-collect-fill" style="width:{{ $portfolioMetrics['collectedPct'] }}%;"></div>
                </div>
                <div class="dash-collect-splits">
                    <div class="dash-split">
                        <div class="dash-split-lbl">UNITS</div>
                        <div class="dash-split-val">{{ $stats['units'] }}</div>
                    </div>
                    <div class="dash-split">
                        <div class="dash-split-lbl">OCCUPANCY</div>
                        <div class="dash-split-val is-gold">{{ $portfolioMetrics['occupancyPct'] }}%</div>
                    </div>
                    <div class="dash-split">
                        <div class="dash-split-lbl">OVERDUE</div>
                        <div class="dash-split-val">{{ $portfolioMetrics['overdueCount'] }}</div>
                    </div>
                </div>
            </div>

            <div class="dash-card dash-attention">
                <div class="dash-attention-label">Needs your attention</div>
                <div class="dash-attention-list">
                    @forelse($needsToday as $item)
                        {{-- The feed tags every item with its own tone, so this
                             no longer sniffs the icon class to guess one — a
                             fourth place the same decision was being made. --}}
                        @php $rowTone = $item['tone'] ?? 'info'; @endphp
                        @if(isset($item['href']))
                            <a href="{{ $item['href'] }}" class="dash-attention-row">
                        @else
                            <button type="button" class="dash-attention-row" onclick="{{ $item['onclick'] }}">
                        @endif
                            <span class="dash-attention-tile" style="{{ $tone[$rowTone] }}">
                                <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <span class="dash-attention-text">
                                <span class="dash-attention-title">{{ $item['title'] }}</span>
                                <span class="dash-attention-sub">{{ $item['sub'] }}</span>
                            </span>
                            <i class="fa-solid fa-chevron-right dash-attention-chev" aria-hidden="true"></i>
                        @if(isset($item['href']))
                            </a>
                        @else
                            </button>
                        @endif
                    @empty
                        <div class="dash-attention-empty">Nothing needs your attention today.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ── §5  Property performance ───────────────────────────────── --}}
    <div class="dash-card reveal-item">
        <div class="dash-props-head">
            <div class="dash-card-title">Property performance — {{ now()->format('F Y') }}</div>
            <a href="{{ route('buildings.index') }}" class="dash-viewall">View all</a>
        </div>

        @if($buildingPerformance->isEmpty())
            <div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-building" aria-hidden="true"></i></div>
                <h4>No buildings yet</h4>
                <p>Add a building to start tracking its income, expenses and occupancy here.</p>
                <a href="{{ route('buildings.create') }}" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-plus"></i> New building
                </a>
            </div>
        @else
            <div class="dash-props">
                @foreach($buildingPerformance as $perf)
                    @php
                        $building = $perf['building'];
                        $initials = Str::upper(
                            collect(explode(' ', trim((string) $building->property_name)))
                                ->filter()->take(2)->map(fn ($w) => Str::substr($w, 0, 1))->implode('')
                        );
                        $photo = $building->images->first();
                    @endphp
                    <a href="{{ route('buildings.show', $building) }}" class="dash-prop">
                        <div class="dash-prop-top">
                            @if($photo)
                                <img src="{{ $photo->url }}" alt="{{ $building->property_name }}" class="dash-prop-photo">
                            @else
                                <span class="dash-prop-photo-empty" aria-hidden="true"><i class="fa-solid fa-building"></i></span>
                            @endif
                            <span class="dash-prop-tile">{{ $initials ?: '—' }}</span>
                            <span class="dash-prop-id">
                                <span class="dash-prop-name">{{ $building->property_name }}</span>
                                <span class="dash-prop-meta">{{ $building->property_code }}@if($building->property_type) · {{ $building->property_type }}@endif</span>
                            </span>
                        </div>
                        <div class="dash-prop-stats">
                            <div class="dash-prop-stat">
                                <div class="dash-prop-stat-lbl">Income</div>
                                <div class="dash-prop-stat-val">BHD {{ number_format($perf['total_income'], 0) }}</div>
                            </div>
                            <div class="dash-prop-stat">
                                <div class="dash-prop-stat-lbl">Net</div>
                                <div class="dash-prop-stat-val {{ $perf['net_income'] < 0 ? 'is-negative' : '' }}">BHD {{ number_format($perf['net_income'], 0) }}</div>
                            </div>
                            <div class="dash-prop-stat">
                                <div class="dash-prop-stat-lbl">Occ.</div>
                                <div class="dash-prop-stat-val">{{ $perf['occupancy_percent'] }}%</div>
                            </div>
                        </div>
                        <div class="dash-prop-foot">View details &rarr;</div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- RECENT RECORDS --}}
    <div class="dash-grid card-reveal">

        <div class="card dash-recent is-buildings">
            <div class="card-header">
                <span class="card-header-icon is-neutral is-soft" aria-hidden="true"><i class="fa-solid fa-building"></i></span>
                <div class="card-header-text">
                    <h3 class="card-title">Recent Buildings</h3>
                </div>
                <div class="card-header-actions">
                    <a href="{{ route('buildings.index') }}" class="btn btn-outline btn-sm">View all</a>
                </div>
            </div>
            <div class="card-body is-flush">
                @if($recentBuildings->isEmpty())
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-building"></i></div>
                        <h4>No buildings yet</h4>
                    </div>
                @else
                <div class="table-wrap">
                    <table aria-label="Recent buildings">
                        <thead><tr><th>Code</th><th>Name</th><th>Type</th></tr></thead>
                        <tbody>
                            @foreach($recentBuildings as $b)
                            <tr data-href="{{ route('buildings.show', $b) }}">
                                <td><span class="dash-code">{{ $b->property_code }}</span></td>
                                <td>{{ $b->property_name }}</td>
                                <td>{{ $b->property_type ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="card dash-recent is-units">
            <div class="card-header">
                <span class="card-header-icon is-neutral is-soft" aria-hidden="true"><i class="fa-solid fa-door-open"></i></span>
                <div class="card-header-text">
                    <h3 class="card-title">Recent Units</h3>
                </div>
                <div class="card-header-actions">
                    <a href="{{ route('property-units.index') }}" class="btn btn-outline btn-sm">View all</a>
                </div>
            </div>
            <div class="card-body is-flush">
                @if($recentUnits->isEmpty())
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-door-open"></i></div>
                        <h4>No units yet</h4>
                    </div>
                @else
                <div class="table-wrap">
                    <table aria-label="Recent units">
                        <thead><tr><th>Unit</th><th>Building</th><th>Condition</th></tr></thead>
                        <tbody>
                            @foreach($recentUnits as $u)
                            <tr data-href="{{ route('property-units.show', $u) }}">
                                <td><span class="dash-code">{{ $u->unit_name }}</span></td>
                                <td>{{ optional($u->building)->property_code ?? '—' }}</td>
                                <td>{{ $u->unit_condition ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- SMART IMPORT RESULTS --}}
@if(session('smart_import_results'))
<div class="smart-results-wrap" id="smartResultsWrap">
    <div class="smart-results-top">
        <div class="smart-results-heading">
            @php
                $totalImported = collect(session('smart_import_results'))->sum('imported');
                $totalErrors   = collect(session('smart_import_results'))->sum(fn($r) => count($r['errors']));
            @endphp
            <i class="fa-solid {{ $totalErrors > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check' }}" style="color:{{ $totalErrors > 0 ? 'var(--accent)' : 'var(--chart-income)' }}"></i>
            Import complete &mdash; {{ $totalImported }} record(s) saved
            @if($totalErrors > 0), {{ $totalErrors }} skipped @endif
        </div>
        <button class="smart-results-close" onclick="document.getElementById('smartResultsWrap').remove()" title="Dismiss" aria-label="Dismiss import results">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <div class="smart-results-grid">
        @foreach(session('smart_import_results') as $entity => $result)
        <div class="smart-result-card {{ count($result['errors']) > 0 ? 'has-errors' : '' }}">
            <div class="smart-result-entity-icon">
                @php
                    $icons = ['tenants'=>'fa-user','contracts'=>'fa-file-contract','buildings'=>'fa-building','floors'=>'fa-layer-group','units'=>'fa-door-open'];
                @endphp
                <i class="fa-solid {{ $icons[$entity] ?? 'fa-database' }}"></i>
            </div>
            <div class="smart-result-body">
                <div class="smart-result-entity">{{ ucfirst($entity) }}</div>
                <div class="smart-result-count">{{ $result['imported'] }} imported</div>
                @if(count($result['errors']) > 0)
                <details class="smart-result-errors">
                    <summary>{{ count($result['errors']) }} skipped</summary>
                    <ul>
                        @foreach($result['errors'] as $err)
                        <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </details>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@elseif(session('smart_import_error'))
<div class="import-banner error" style="margin-bottom:20px;">
    <div class="import-banner-icon"><i class="fa-solid fa-circle-xmark"></i></div>
    <div class="import-banner-body">
        <div class="import-banner-title">{{ session('smart_import_error') }}</div>
    </div>
    <button class="import-banner-close" onclick="this.closest('.import-banner').remove()" title="Dismiss" aria-label="Dismiss error message">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
@endif

{{-- SMART IMPORT MODAL --}}
<div class="modal-overlay" id="smartImportModal" onclick="if(event.target===this)closeSmartImport()">
    <div class="modal-box smart-import-box" role="dialog" aria-modal="true" aria-labelledby="smartImportTitle">

        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div class="modal-header-text">
                    <div class="modal-header-title" id="smartImportTitle">Smart Import</div>
                    <div class="modal-header-sub">Upload any file — auto-detected &amp; routed to the right tables</div>
                </div>
                <button class="modal-close-btn" type="button" onclick="closeSmartImport()" title="Close" aria-label="Close smart import dialog">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="modal-body" style="padding:20px 24px;">

            {{-- Detection info --}}
            <div class="card is-nested is-compact smart-detect-info">
                <div class="smart-detect-label"><i class="fa-solid fa-microchip"></i> Auto-detects any of these types</div>
                <div class="smart-detect-badges">
                    <span class="smart-detect-badge"><i class="fa-solid fa-building"></i> Buildings</span>
                    <span class="smart-detect-badge"><i class="fa-solid fa-layer-group"></i> Floors</span>
                    <span class="smart-detect-badge"><i class="fa-solid fa-door-open"></i> Units</span>
                    <span class="smart-detect-badge"><i class="fa-solid fa-user"></i> Tenants</span>
                    <span class="smart-detect-badge"><i class="fa-solid fa-file-contract"></i> Contracts</span>
                </div>
                <p class="smart-detect-note">
                    A lease contracts file automatically imports both <strong>Tenants</strong> and <strong>Contracts</strong> in one pass.
                    Duplicate records are skipped, not overwritten.
                </p>
            </div>

            {{-- Upload form --}}
            <form id="smartImportForm" method="POST" action="{{ route('import.smart') }}" enctype="multipart/form-data">
                @csrf
                <div class="import-drop-zone" id="smartImportDrop"
                     onclick="document.getElementById('smartImportFile').click()"
                     ondragover="importDragOver(event,'smartImportDrop')"
                     ondragleave="importDragLeave('smartImportDrop')"
                     ondrop="importDrop(event,'smartImportDrop','smartImportFile')">
                    <div class="import-drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <div class="import-drop-label" id="smartImportDropLabel">Drag &amp; drop your file here</div>
                    <div class="import-drop-sub">CSV or XLSX &mdash; max 10 MB</div>
                    <div class="import-file-name" id="smartImportFileName"></div>
                    <input type="file" id="smartImportFile" name="file"
                           accept=".csv,.xlsx,.xls,text/csv"
                           style="display:none;"
                           onchange="smartImportFileChosen(this)">
                </div>
            </form>

        </div>

        <div class="modal-footer" style="padding:14px 24px;border-top:1px solid var(--card-border);display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" class="btn btn-outline" onclick="closeSmartImport()">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" id="smartImportSubmit"
                    onclick="document.getElementById('smartImportForm').submit()" disabled>
                <i class="fa-solid fa-wand-magic-sparkles"></i> Import
            </button>
        </div>

    </div>
</div>

@endsection
