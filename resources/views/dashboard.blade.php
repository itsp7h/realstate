@extends('layouts.admin')

@section('title', 'Dashboard')
@section('topbar-title', 'Dashboard')

@push('styles')
<style>
/* ── STATS ─────────────────────────────────────────────── */
.dash-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.dash-stat {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 18px 20px;
    display: flex; align-items: center; gap: 14px;
    transition: box-shadow 0.2s, transform 0.2s;
}
.dash-stat:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
a.dash-stat { text-decoration: none; cursor: pointer; }
.dash-table tr[data-href] { cursor: pointer; }
.dash-stat-icon {
    width: 44px; height: 44px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.dash-stat-icon.gold   { background: var(--accent-dim); color: var(--accent); }
.dash-stat-icon.blue   { background: #EFF6FF; color: #3B82F6; }
.dash-stat-icon.green  { background: #ECFDF5; color: #10B981; }
.dash-stat-icon.purple { background: #F5F3FF; color: #7C3AED; }
.dash-stat-icon.rose   { background: #FFF1F2; color: #F43F5E; }
.dash-stat-val { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.dash-stat-lbl { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

/* ── HERO IMPORT/EXPORT ─────────────────────────────────── */
.data-hero {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 28px 32px;
    display: flex;
    align-items: center;
    gap: 28px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.data-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--accent-dim) 0%, transparent 60%);
    pointer-events: none;
}
.data-hero-icon {
    width: 64px; height: 64px; border-radius: 16px;
    background: var(--accent); border: 1px solid rgba(232,184,109,0.4);
    display: flex; align-items: center; justify-content: center;
    color: #0B1120; font-size: 26px; flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(232,184,109,0.3);
    position: relative; z-index: 1;
}
.data-hero-text { flex: 1; position: relative; z-index: 1; }
.data-hero-title {
    font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800;
    color: var(--text-primary); margin-bottom: 5px;
}
.data-hero-sub { font-size: 13px; color: var(--text-secondary); line-height: 1.5; }
.data-hero-actions { display: flex; gap: 10px; flex-shrink: 0; position: relative; z-index: 1; }

/* ── RECENT TABLES ──────────────────────────────────────── */
.dash-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}
@media (max-width: 900px) { .dash-grid { grid-template-columns: 1fr; } }
.dash-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    overflow: hidden;
}
.dash-card-head {
    padding: 14px 20px;
    border-bottom: 1px solid var(--card-border);
    display: flex; align-items: center; justify-content: space-between;
}
.dash-card-title {
    font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700;
    color: var(--text-primary); display: flex; align-items: center; gap: 8px;
}
.dash-card-title i { color: var(--accent); font-size: 13px; }
.dash-table { width: 100%; border-collapse: collapse; }
.dash-table th {
    padding: 8px 16px; font-size: 11px; font-weight: 700;
    color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;
    border-bottom: 1px solid var(--card-border); text-align: left;
    background: var(--page-bg);
}
.dash-table td {
    padding: 10px 16px; font-size: 13px; color: var(--text-secondary);
    border-bottom: 1px solid var(--card-border);
}
.dash-table tr:last-child td { border-bottom: none; }
.dash-table tr:hover td { background: var(--page-bg); }
.dash-code {
    font-family: 'Outfit', sans-serif; font-weight: 700;
    color: var(--text-primary); font-size: 13px;
}
.empty-dash { text-align: center; padding: 30px; color: var(--text-muted); font-size: 13px; }

/* ── MODAL BASE ─────────────────────────────────────────── */
.modal-overlay {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(11,17,32,0.55);
    backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
    opacity: 0; pointer-events: none;
    transition: opacity 0.25s ease;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal-box {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    box-shadow: 0 24px 60px rgba(0,0,0,0.18), 0 8px 24px rgba(0,0,0,0.10);
    width: 100%; max-width: 680px; max-height: 90vh;
    display: flex; flex-direction: column;
    transform: translateY(20px) scale(0.98);
    transition: transform 0.3s cubic-bezier(0.22,1,0.36,1);
    overflow: hidden;
}
.modal-overlay.open .modal-box { transform: translateY(0) scale(1); }
.modal-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid var(--card-border);
    flex-shrink: 0;
}
.modal-header-top { display: flex; align-items: flex-start; gap: 14px; }
.modal-header-icon {
    width: 42px; height: 42px; border-radius: 10px;
    background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.modal-header-text { flex: 1; }
.modal-header-title { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 800; color: var(--text-primary); }
.modal-header-sub { font-size: 13px; color: var(--text-muted); margin-top: 3px; }
.modal-close-btn {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: 16px;
    padding: 4px 6px; border-radius: 6px; transition: color 0.15s;
}
.modal-close-btn:hover { color: var(--text-primary); }
.modal-body { overflow-y: auto; flex: 1; }
.modal-footer { flex-shrink: 0; }

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
.import-drop-label { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 5px; }
.import-drop-sub { font-size: 12px; color: var(--text-muted); }
.import-file-name { margin-top: 12px; font-size: 13px; font-weight: 600; color: var(--accent); min-height: 18px; }

/* ── IMPORT BANNER (error) ──────────────────────────────── */
.import-banner {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 14px 18px; border-radius: var(--radius);
    border: 1px solid; animation: bannerSlide 0.3s ease both;
}
.import-banner.error { background: #FEF2F2; border-color: #FCA5A5; }
.import-banner-icon { font-size: 16px; flex-shrink: 0; padding-top: 2px; }
.import-banner.error .import-banner-icon { color: #DC2626; }
.import-banner-body { flex: 1; }
.import-banner-title { font-size: 13.5px; font-weight: 600; color: var(--text-primary); }
.import-banner-close {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: 13px; flex-shrink: 0;
    padding: 2px 4px; border-radius: 4px; transition: color 0.15s;
}
.import-banner-close:hover { color: var(--text-primary); }

/* ── SMART IMPORT RESULTS ───────────────────────────────── */
.smart-results-wrap {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 18px 22px;
    margin-bottom: 24px;
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
    font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700;
    color: var(--text-primary); display: flex; align-items: center; gap: 8px;
}
.smart-results-close {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: 14px; padding: 2px 6px;
    border-radius: 4px; transition: color 0.15s;
}
.smart-results-close:hover { color: var(--text-primary); }
.smart-results-grid {
    display: flex; flex-wrap: wrap; gap: 10px;
}
.smart-result-card {
    display: flex; align-items: flex-start; gap: 12px;
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    min-width: 160px; flex: 1;
}
.smart-result-card.has-errors { border-color: rgba(234,179,8,0.4); background: #FFFBEB; }
.smart-result-entity-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}
.smart-result-entity { font-size: 12px; font-weight: 700; color: var(--text-primary); text-transform: capitalize; }
.smart-result-count  { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
.smart-result-errors { margin-top: 6px; }
.smart-result-errors summary { font-size: 11px; color: #D97706; cursor: pointer; list-style: revert; }
.smart-result-errors ul { margin: 6px 0 0 14px; padding: 0; font-size: 11px; color: var(--text-secondary); line-height: 1.8; }

/* ── SMART IMPORT MODAL ─────────────────────────────────── */
.smart-import-box { max-width: 560px !important; }
.smart-detect-info {
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-bottom: 16px;
}
.smart-detect-label {
    font-size: 12px; font-weight: 700; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.05em;
    margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
}
.smart-detect-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.smart-detect-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 12px; font-weight: 600;
    padding: 4px 10px; border-radius: 20px;
    background: var(--accent-dim); color: var(--accent);
    border: 1px solid rgba(232,184,109,0.35);
}
.smart-detect-note {
    font-size: 12px; color: var(--text-muted); margin: 0; line-height: 1.6;
}

/* ── FINANCIAL OVERVIEW CHART ───────────────────────────── */
.finance-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    padding: 24px;
    margin-bottom: 28px;
}
.finance-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}
.finance-head-left { display: flex; align-items: center; gap: 14px; }
.finance-icon {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: var(--accent); color: #0B1120;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    box-shadow: 0 4px 14px var(--accent-glow);
}
.finance-title { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary); }
.finance-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.finance-year-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 20px;
    background: var(--page-bg); border: 1px solid var(--card-border);
    font-size: 12px; font-weight: 600; color: var(--text-secondary);
}
.finance-legend { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
.legend-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 6px 13px; border-radius: 20px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 12px; font-weight: 600;
    background: var(--page-bg); border: 1px solid var(--card-border);
    color: var(--text-secondary); cursor: pointer;
    transition: all 0.15s ease;
}
.legend-btn:hover { border-color: var(--card-border); background: #EEF2F7; }
.legend-btn.is-off { opacity: 0.45; text-decoration: line-through; }
.legend-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.finance-canvas-wrap { position: relative; width: 100%; height: 320px; }
.finance-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    height: 320px; color: var(--text-muted); font-size: 13px; gap: 10px;
}
.finance-empty i { font-size: 28px; opacity: 0.5; }

/* ── PROPERTY PERFORMANCE CARDS ──────────────────────────── */
.property-section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px;
}
.property-section-title {
    font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px;
    color: var(--text-primary); display: flex; align-items: center; gap: 9px;
}
.property-section-title i { color: var(--accent); }
.property-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}
.property-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    display: flex; flex-direction: column;
    cursor: pointer;
    transition: box-shadow 0.2s, transform 0.2s;
}
.property-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.property-carousel { position: relative; height: 190px; background: var(--page-bg); flex-shrink: 0; }
.property-carousel-track { display: flex; height: 100%; transition: transform 0.35s cubic-bezier(0.22,1,0.36,1); }
.property-carousel-track img { width: 100%; height: 100%; object-fit: cover; flex-shrink: 0; }
.property-carousel-empty {
    height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: var(--text-muted); gap: 6px;
}
.property-carousel-empty i { font-size: 26px; opacity: 0.45; }
.property-carousel-empty span { font-size: 11.5px; }
.property-carousel-btn {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 30px; height: 30px; border-radius: 50%; border: none;
    background: rgba(11,17,32,0.45); color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; transition: background 0.15s;
    backdrop-filter: blur(2px);
}
.property-carousel-btn:hover { background: rgba(11,17,32,0.7); }
.property-carousel-btn.prev { left: 10px; }
.property-carousel-btn.next { right: 10px; }
.property-carousel-dots {
    position: absolute; bottom: 10px; left: 0; right: 0;
    display: flex; justify-content: center; gap: 5px;
}
.property-carousel-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: rgba(255,255,255,0.55); border: none; padding: 0; cursor: pointer;
}
.property-carousel-dot.active { background: #fff; width: 16px; border-radius: 3px; }
.property-body { padding: 20px 22px 22px; display: flex; flex-direction: column; flex: 1; }
.property-title-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 3px; }
.property-name { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 17px; color: var(--text-primary); }
.property-address { font-size: 12.5px; color: var(--text-muted); margin-bottom: 18px; }
.property-address i { margin-right: 4px; }
.property-period-label {
    font-size: 11px; font-weight: 700; color: var(--text-secondary);
    text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px;
}
.property-stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 18px; }
.property-stat-box { border-radius: var(--radius-sm); padding: 10px 8px; text-align: center; border: 1px solid; }
.property-stat-box.income { background: #ECFDF5; border-color: #A7F3D0; }
.property-stat-box.net    { background: #EFF6FF; border-color: #BFDBFE; }
.property-stat-box.occ    { background: var(--page-bg); border-color: var(--card-border); }
.property-stat-label { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
.property-stat-box.income .property-stat-label { color: #065F46; }
.property-stat-box.net .property-stat-label    { color: #1E40AF; }
.property-stat-box.occ .property-stat-label    { color: var(--text-secondary); }
.property-stat-value { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 15px; margin-top: 2px; }
.property-stat-box.income .property-stat-value { color: #047857; }
.property-stat-box.net .property-stat-value    { color: #1D4ED8; }
.property-stat-box.occ .property-stat-value    { color: var(--text-primary); }
.property-stat-sub { font-size: 9.5px; color: var(--text-muted); margin-top: 1px; }
.property-expense-title {
    font-size: 10.5px; font-weight: 700; color: var(--text-secondary);
    text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;
}
.property-expense-list {
    background: var(--page-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius-sm); padding: 12px 14px; margin-top: auto;
}
.property-expense-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 4px 0; font-size: 12.5px;
}
.property-expense-row + .property-expense-row { border-top: 1px dashed var(--card-border); }
.property-expense-row .label { color: var(--text-primary); font-weight: 600; display: flex; align-items: center; gap: 8px; }
.property-expense-row .label i { width: 14px; text-align: center; }
.property-expense-row .value { font-weight: 700; color: var(--text-primary); }
.property-expense-empty { text-align: center; font-size: 12px; color: var(--text-muted); padding: 6px 0; }

/* ── MOBILE PHOTO BADGE (overlaid on carousel, hidden on desktop) ── */
.property-photo-badge { display: none; }

/* ── MOBILE DASHBOARD (hidden on desktop) ──────────────────── */
.m-dash { display: none; }

/* ── MOBILE DASHBOARD (Promoseven RE mobile spec) ──────────
     The desktop dashboard sections below are replaced wholesale by the
     .m-dash-* markup on mobile — not squeezed responsively — since the
     mobile spec is a different screen, not a narrower desktop one. ── */
@media (max-width: 768px) {
    .page-header, .dash-stats, .data-hero,
    .finance-card, .property-section-head, .property-grid, .dash-grid {
        display: none !important;
    }

    @keyframes m-drift-1 { from { transform: translate(0,0) scale(1); } to { transform: translate(-45px,35px) scale(1.18); } }
    @keyframes m-drift-2 { from { transform: translate(0,0) scale(1); } to { transform: translate(55px,-28px) scale(1.12); } }
    @keyframes m-sheen   { 0% { transform: translateX(-140%) skewX(-18deg); } 100% { transform: translateX(320%) skewX(-18deg); } }

    .m-dash { display: block; font-family: 'Poppins', sans-serif; color: var(--m-ink); }

    /* ── Hero ──────────────────────────────────────────── */
    .m-hero {
        background: linear-gradient(150deg, var(--m-navy), var(--m-navy-2));
        border-radius: 0 0 30px 30px;
        padding: calc(20px + env(safe-area-inset-top)) 20px 56px;
        display: flex; flex-direction: column; gap: 20px;
        position: relative; overflow: hidden;
    }
    .m-hero-fx { position: absolute; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
    .m-hero-fx span { position: absolute; border-radius: 50%; filter: blur(12px); }
    .m-hero-fx .b1 { width: 280px; height: 280px; top: -90px; right: -70px; background: radial-gradient(circle,rgba(231,178,102,.30),transparent 65%); animation: m-drift-1 9s ease-in-out infinite alternate; }
    .m-hero-fx .b2 { width: 240px; height: 240px; bottom: -110px; left: -80px; background: radial-gradient(circle,rgba(74,125,240,.22),transparent 65%); animation: m-drift-2 12s ease-in-out infinite alternate; }
    .m-hero-fx .sheen { top: 0; bottom: 0; width: 90px; background: linear-gradient(90deg,transparent,rgba(255,255,255,.05),transparent); animation: m-sheen 7s ease-in-out infinite; }
    .m-hero-top { display: flex; align-items: center; gap: 12px; position: relative; z-index: 1; }
    .m-hero-icon-btn {
        width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,.08);
        border: none; display: flex; align-items: center; justify-content: center;
        color: #C6D0E2; cursor: pointer; flex-shrink: 0; position: relative;
    }
    .m-hero-greeting { flex: 1; }
    .m-hero-greeting .g1 { font-size: 11.5px; color: var(--m-navy-text); }
    .m-hero-greeting .g2 { font-size: 16px; font-weight: 700; color: #fff; }
    .m-hero-avatar {
        width: 40px; height: 40px; border-radius: 50%; background: var(--m-gold-deep);
        color: #fff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; flex-shrink: 0;
    }
    .m-hero-net { display: flex; flex-direction: column; gap: 4px; position: relative; z-index: 1; }
    .m-hero-net-label { font-size: 10.5px; letter-spacing: 1.2px; font-weight: 600; color: var(--m-navy-text); }
    .m-hero-net-val { font-size: 34px; font-weight: 800; color: #fff; }
    .m-hero-net-val span { font-size: 12px; color: var(--m-navy-text); font-weight: 500; margin-left: 10px; }
    .m-hero-tiles { display: flex; gap: 10px; position: relative; z-index: 1; }
    .m-hero-tile { flex: 1; background: rgba(255,255,255,.07); border-radius: 14px; padding: 12px 14px; display: flex; flex-direction: column; gap: 1px; }
    .m-hero-tile-label { font-size: 10px; color: var(--m-navy-text); font-weight: 600; }
    .m-hero-tile-val { font-size: 16px; font-weight: 700; }

    /* ── Stat strip ────────────────────────────────────── */
    .m-strip {
        margin: -32px 18px 0; background: var(--m-card); border-radius: 20px; padding: 16px 4px;
        display: flex; box-shadow: var(--m-shadow-float); position: relative; z-index: 1;
    }
    .m-strip-item {
        flex: 1; display: flex; flex-direction: column; align-items: center; gap: 2px;
        border: none; background: none; cursor: pointer; font-family: 'Poppins', sans-serif;
        border-right: 1px solid var(--m-line); text-decoration: none; min-height: 44px; justify-content: center;
    }
    .m-strip-item:last-child { border-right: none; }
    .m-strip-item .v { font-size: 19px; font-weight: 800; color: var(--m-ink); }
    .m-strip-item.is-green .v { color: var(--m-green); }
    .m-strip-item .l { font-size: 10.5px; color: var(--m-muted); }

    /* ── Section heads ─────────────────────────────────── */
    .m-sec-head { display: flex; align-items: center; padding: 22px 18px 10px; }
    .m-sec-head .t { font-size: 14px; font-weight: 700; flex: 1; }
    .m-sec-head a, .m-sec-head button {
        font-size: 11.5px; font-weight: 600; color: var(--m-gold-text); border: none;
        background: none; cursor: pointer; font-family: 'Poppins', sans-serif; text-decoration: none;
        min-height: 44px; display: flex; align-items: center;
    }

    /* ── Quick actions ─────────────────────────────────── */
    .m-quick { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; padding: 22px 18px 0; }
    .m-quick-item { display: flex; flex-direction: column; align-items: center; gap: 7px; text-decoration: none; }
    .m-quick-tile { width: 56px; height: 56px; border-radius: 17px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .m-quick-item .l { font-size: 10px; font-weight: 600; color: #4A5568; }

    /* ── Activity list ─────────────────────────────────── */
    .m-activity-list { display: flex; flex-direction: column; gap: 9px; padding: 0 18px 4px; }
    .m-activity-row {
        background: var(--m-card); border-radius: 15px; padding: 13px 15px; display: flex;
        align-items: center; gap: 12px; box-shadow: var(--m-shadow);
    }
    .m-activity-icon { width: 38px; height: 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 15px; }
    .m-activity-body { flex: 1; min-width: 0; }
    .m-activity-title { font-size: 12.5px; font-weight: 600; }
    .m-activity-sub { font-size: 10.5px; color: var(--m-muted); }

    /* ── Finance card ───────────────────────────────────── */
    .m-finance-card {
        margin: 18px 18px 0; background: var(--m-card); border-radius: var(--m-r-card);
        padding: 18px; display: flex; flex-direction: column; gap: 14px; box-shadow: var(--m-shadow);
    }
    .m-finance-head { display: flex; align-items: center; gap: 12px; }
    .m-finance-icon { width: 42px; height: 42px; border-radius: 13px; background: var(--m-gold); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 12px rgba(231,178,102,.3); color: var(--m-gold-on); font-size: 16px; }
    .m-finance-title { font-size: 14px; font-weight: 700; }
    .m-finance-sub { font-size: 10.5px; color: var(--m-muted); }
    .m-finance-year { padding: 6px 12px; border-radius: 10px; background: var(--m-line); font-size: 11.5px; font-weight: 600; }
    .m-finance-canvas { height: 110px; }
    .m-finance-legend { display: flex; gap: 8px; overflow-x: auto; }
    .m-finance-chip {
        display: flex; align-items: center; gap: 6px; padding: 8px 13px; border-radius: 10px;
        font-size: 11.5px; font-family: 'Poppins', sans-serif; cursor: pointer; flex-shrink: 0;
        border: 1.5px solid transparent; background: #F7F8FB; color: var(--m-muted); font-weight: 500;
    }
    .m-finance-chip .dot { width: 8px; height: 8px; border-radius: 50%; }
    .m-finance-chip.active { font-weight: 700; }

    /* ── Property performance ──────────────────────────── */
    .m-perf-scroll { display: flex; gap: 12px; overflow-x: auto; padding: 0 18px 4px; }
    .m-perf-card {
        flex-shrink: 0; width: 246px; background: var(--m-card); border-radius: var(--m-r-card);
        box-shadow: var(--m-shadow); padding: 16px; display: flex; flex-direction: column; gap: 13px;
        text-decoration: none; color: inherit;
    }
    .m-perf-top { display: flex; align-items: center; gap: 12px; }
    .m-perf-name { font-size: 13.5px; font-weight: 700; color: var(--m-ink); line-height: 1.25; }
    .m-perf-chips { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 5px; }
    .m-perf-chip { padding: 3px 8px; border-radius: 7px; font-size: 9.5px; font-weight: 700; }
    .m-perf-foot { display: flex; gap: 8px; padding-top: 11px; border-top: 1px solid var(--m-line); }
    .m-perf-foot > div { flex: 1; display: flex; flex-direction: column; }
    .m-perf-foot .l { font-size: 9px; font-weight: 600; color: var(--m-faint); letter-spacing: .4px; }
    .m-perf-foot .v { font-size: 12.5px; font-weight: 800; color: var(--m-ink); }

    /* ── Recent lists ───────────────────────────────────── */
    .m-list-card { margin: 0 18px; background: var(--m-card); border-radius: var(--m-r-card); box-shadow: var(--m-shadow); overflow: hidden; }
    .m-list-card + .m-list-card { margin-top: 16px; }
    .m-list-head { display: flex; align-items: center; gap: 10px; padding: 15px 16px; border-bottom: 1px solid var(--m-line); }
    .m-list-head-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .m-list-head-title { font-size: 13.5px; font-weight: 700; flex: 1; }
    .m-list-head a { height: 34px; padding: 0 13px; border: 1.5px solid var(--m-border); border-radius: 10px; background: var(--m-card); color: #4A5568; font-weight: 600; font-size: 11.5px; font-family: 'Poppins', sans-serif; cursor: pointer; text-decoration: none; display: flex; align-items: center; }
    .m-list-row {
        display: flex; align-items: center; gap: 12px; width: 100%; padding: 12px 16px;
        border: none; border-bottom: 1px solid var(--m-line); background: none; cursor: pointer;
        font-family: 'Poppins', sans-serif; text-align: left; min-height: 48px; text-decoration: none; color: inherit;
    }
    .m-list-row:last-child { border-bottom: none; }
    .m-list-code { padding: 5px 9px; border-radius: 8px; background: var(--m-gold-tint); color: var(--m-gold-text); font-size: 10px; font-weight: 700; flex-shrink: 0; min-width: 42px; text-align: center; }
    .m-list-name { font-size: 12.5px; font-weight: 600; color: var(--m-ink); flex: 1; }
    .m-list-meta { font-size: 11px; font-weight: 500; }
    .m-empty-row { padding: 24px 16px; text-align: center; font-size: 12px; color: var(--m-muted); }

    .m-dash-bottom-space { height: 26px; }
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
    document.getElementById('smartImportFileName').textContent = '📄 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('smartImportDropLabel').textContent = 'File selected — ready to import';
    document.getElementById('smartImportSubmit').disabled = false;
}

/* ── CLICKABLE PROPERTY CARDS ──────────────────────────────── */
document.querySelectorAll('.property-card[data-href]').forEach(function (card) {
    card.addEventListener('click', function () {
        window.location = card.dataset.href;
    });
});

/* ── PROPERTY PHOTO CAROUSELS ─────────────────────────────── */
document.querySelectorAll('.property-carousel').forEach(function (carousel) {
    const track = carousel.querySelector('.property-carousel-track');
    const slides = carousel.querySelectorAll('.property-carousel-track img');
    const dots = carousel.querySelectorAll('.property-carousel-dot');
    if (!track || slides.length <= 1) return;
    let index = 0;

    function render() {
        track.style.transform = 'translateX(-' + (index * 100) + '%)';
        dots.forEach((d, i) => d.classList.toggle('active', i === index));
    }
    carousel.querySelector('.prev')?.addEventListener('click', () => {
        index = (index - 1 + slides.length) % slides.length;
        render();
    });
    carousel.querySelector('.next')?.addEventListener('click', () => {
        index = (index + 1) % slides.length;
        render();
    });
    dots.forEach((dot, i) => dot.addEventListener('click', () => { index = i; render(); }));
});

/* ── PORTFOLIO FINANCIAL CHART ────────────────────────────── */
(function () {
    const canvas = document.getElementById('portfolioChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const chartData = @json($chartData);
    const ctx = canvas.getContext('2d');

    function gradient(hex) {
        const g = ctx.createLinearGradient(0, 0, 0, 320);
        g.addColorStop(0, hex + 'E6');
        g.addColorStop(1, hex + '1A');
        return g;
    }

    const portfolioChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Income',   data: chartData.income,   backgroundColor: gradient('#10b981'), hoverBackgroundColor: '#10b981', borderRadius: 6, borderSkipped: false, barPercentage: 0.65, categoryPercentage: 0.85 },
                { label: 'Expenses', data: chartData.expenses, backgroundColor: gradient('#ef4444'), hoverBackgroundColor: '#ef4444', borderRadius: 6, borderSkipped: false, barPercentage: 0.65, categoryPercentage: 0.85 },
                { label: 'Credits',  data: chartData.credits,  backgroundColor: gradient('#0ea5e9'), hoverBackgroundColor: '#0ea5e9', borderRadius: 6, borderSkipped: false, barPercentage: 0.65, categoryPercentage: 0.85 },
                { label: 'Debits',   data: chartData.debits,   backgroundColor: gradient('#f59e0b'), hoverBackgroundColor: '#f59e0b', borderRadius: 6, borderSkipped: false, barPercentage: 0.65, categoryPercentage: 0.85 },
                { label: 'Profit', data: chartData.profit, type: 'line', borderColor: '#8b5cf6', borderWidth: 3, tension: 0.4, fill: false, pointRadius: 4, pointHoverRadius: 7, pointBackgroundColor: '#fff', pointBorderColor: '#8b5cf6', pointBorderWidth: 2 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0B1120', titleFont: { family: 'Outfit', size: 13, weight: '700' },
                    bodyFont: { family: 'Plus Jakarta Sans', size: 12.5, weight: '500' },
                    padding: 12, cornerRadius: 10, boxPadding: 6, usePointStyle: true,
                    borderColor: '#1A2540', borderWidth: 1,
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
                    border: { display: false },
                    grid: { color: 'rgba(148,163,184,0.15)', tickLength: 0, borderDash: [4, 4] },
                    ticks: {
                        padding: 10, color: '#64748b', font: { family: 'Plus Jakarta Sans', size: 11.5, weight: '500' },
                        callback: (v) => (Math.abs(v) >= 1000 ? 'BHD ' + (v / 1000) + 'k' : 'BHD ' + v),
                    },
                },
                x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans', size: 11.5, weight: '600' } } },
            },
        },
    });

    document.querySelectorAll('.legend-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const i = Number(btn.dataset.dataset);
            const meta = portfolioChart.getDatasetMeta(i);
            meta.hidden = meta.hidden === null ? !portfolioChart.data.datasets[i].hidden : !meta.hidden;
            btn.classList.toggle('is-off', meta.hidden === true);
            portfolioChart.update();
        });
    });
})();

/* ── MOBILE DASHBOARD: hero menu button + single-series finance chart ── */
(function () {
    document.getElementById('mHeroMenuBtn')?.addEventListener('click', function () {
        document.getElementById('menuBtn')?.click();
    });

    const canvas = document.getElementById('mobileChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const chartData = @json($chartData);
    const seriesColor = { income: '#17A96C', expenses: '#D64545', credits: '#4A7DF0', debits: '#E7B266', profit: '#7A5AF8' };
    const seriesStyle = {
        income: { bg: '#E6F6EE', fg: '#0B6E47' }, expenses: { bg: '#FCEBEB', fg: '#B03030' },
        credits: { bg: '#E9F0FD', fg: '#2C5BD0' }, debits: { bg: '#FBF3E4', fg: '#8A6118' },
        profit: { bg: '#EFEBFD', fg: '#5A3BD8' },
    };

    let activeSeries = 'income';
    const mobileChart = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                data: chartData[activeSeries],
                backgroundColor: chartData[activeSeries].map((v) => v === null ? '#F3F5F9' : (v < 0 ? '#D64545' : seriesColor[activeSeries])),
                borderRadius: 5, borderSkipped: false, barPercentage: 0.7, categoryPercentage: 0.9,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: {
                backgroundColor: '#0B1120', padding: 10, cornerRadius: 8,
                callbacks: { label: (c) => 'BHD ' + new Intl.NumberFormat('en-US').format(c.parsed.y) },
            } },
            scales: {
                y: { display: false, beginAtZero: true },
                x: { grid: { display: false }, ticks: { color: '#A7B0C0', font: { family: 'Poppins', size: 7, weight: '600' } } },
            },
        },
    });

    document.querySelectorAll('#mFinanceLegend .m-finance-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            activeSeries = chip.dataset.series;
            mobileChart.data.datasets[0].data = chartData[activeSeries];
            mobileChart.data.datasets[0].backgroundColor = chartData[activeSeries].map((v) => v === null ? '#F3F5F9' : (v < 0 ? '#D64545' : seriesColor[activeSeries]));
            mobileChart.update();

            document.querySelectorAll('#mFinanceLegend .m-finance-chip').forEach(function (c) {
                const on = c === chip;
                const style = seriesStyle[c.dataset.series];
                c.classList.toggle('active', on);
                c.style.background = on ? style.bg : '#F7F8FB';
                c.style.color = on ? style.fg : 'var(--m-muted)';
                c.style.borderColor = on ? seriesColor[c.dataset.series] : 'transparent';
            });
        });
    });
})();
</script>
@endpush

@section('content')

@php
    $portfolioIncome = $buildingPerformance->sum('total_income');
    $portfolioNet = $buildingPerformance->sum('net_income');
    $portfolioExpense = $portfolioIncome - $portfolioNet;
    $greetingHour = now()->hour;
    $greeting = $greetingHour < 12 ? 'Good morning' : ($greetingHour < 18 ? 'Good afternoon' : 'Good evening');
    $activityMeta = [
        'created' => ['icon' => 'fa-circle-plus', 'bg' => 'var(--m-green-tint)', 'fg' => 'var(--m-green)'],
        'imported' => ['icon' => 'fa-file-arrow-up', 'bg' => 'var(--m-blue-tint)', 'fg' => 'var(--m-blue)'],
        'updated' => ['icon' => 'fa-pen', 'bg' => 'var(--m-gold-tint)', 'fg' => 'var(--m-gold-text)'],
        'deleted' => ['icon' => 'fa-trash', 'bg' => 'var(--m-red-tint)', 'fg' => 'var(--m-red)'],
    ];
@endphp

{{-- ═══════════════════════════════ MOBILE DASHBOARD ═══════════════════════════════ --}}
<div class="m-dash">
    <div class="m-hero">
        <div class="m-hero-fx"><span class="b1"></span><span class="b2"></span><span class="sheen"></span></div>
        <div class="m-hero-top">
            <button type="button" class="m-hero-icon-btn" id="mHeroMenuBtn"><i class="fa-solid fa-bars"></i></button>
            <div class="m-hero-greeting">
                <div class="g1">{{ $greeting }}</div>
                <div class="g2">{{ auth()->user()->name ?? 'there' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="m-hero-avatar" title="Sign out">{{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}</button>
            </form>
            <button type="button" class="m-hero-icon-btn"><i class="fa-regular fa-bell"></i></button>
        </div>
        <div class="m-hero-net">
            <div class="m-hero-net-label">{{ $chartYear }} &middot; NET POSITION</div>
            <div class="m-hero-net-val">BHD {{ number_format($portfolioNet, 0) }}<span>{{ now()->format('F') }}: BHD {{ number_format($portfolioNet, 0) }}</span></div>
        </div>
        <div class="m-hero-tiles">
            <div class="m-hero-tile">
                <div class="m-hero-tile-label">INCOME &middot; {{ $chartYear }}</div>
                <div class="m-hero-tile-val" style="color:#7ED8AC;">BHD {{ number_format($portfolioIncome, 0) }}</div>
            </div>
            <div class="m-hero-tile">
                <div class="m-hero-tile-label">EXPENSES &middot; {{ $chartYear }}</div>
                <div class="m-hero-tile-val" style="color:#F0A3A3;">BHD {{ number_format($portfolioExpense, 0) }}</div>
            </div>
        </div>
    </div>

    <div class="m-strip">
        <a href="{{ route('buildings.index') }}" class="m-strip-item"><span class="v">{{ $stats['buildings'] }}</span><span class="l">Buildings</span></a>
        <a href="{{ route('floors.global') }}" class="m-strip-item"><span class="v">{{ $stats['floors'] }}</span><span class="l">Floors</span></a>
        <a href="{{ route('property-units.index') }}" class="m-strip-item"><span class="v">{{ $stats['units'] }}</span><span class="l">Units</span></a>
        <a href="{{ route('property-units.index') }}" class="m-strip-item is-green"><span class="v">{{ $stats['occupied'] }}</span><span class="l">Occupied</span></a>
    </div>

    <div class="m-sec-head"><div class="t">Quick actions</div></div>
    <div class="m-quick">
        <a href="{{ route('property-units.index') }}" class="m-quick-item">
            <div class="m-quick-tile" style="background:var(--m-gold);box-shadow:0 6px 14px rgba(231,178,102,.35);color:var(--m-gold-on);"><i class="fa-solid fa-plus"></i></div>
            <div class="l">Add unit</div>
        </a>
        <a href="{{ route('invoices.index') }}" class="m-quick-item">
            <div class="m-quick-tile" style="background:var(--m-green-tint);color:var(--m-green);"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div class="l">Invoice</div>
        </a>
        <a href="{{ route('tenants.index') }}" class="m-quick-item">
            <div class="m-quick-tile" style="background:var(--m-blue-tint);color:var(--m-blue);"><i class="fa-solid fa-users"></i></div>
            <div class="l">Tenant</div>
        </a>
        <a href="{{ route('maintenance.index') }}" class="m-quick-item">
            <div class="m-quick-tile" style="background:var(--m-red-tint);color:var(--m-red);"><i class="fa-solid fa-wrench"></i></div>
            <div class="l">Repair</div>
        </a>
    </div>

    <div class="m-sec-head"><div class="t">Recent activity</div><a href="{{ route('admin.audit-log') }}">See all</a></div>
    <div class="m-activity-list">
        @forelse($recentActivity as $entry)
            @php $meta = $activityMeta[$entry->action] ?? ['icon' => 'fa-circle-info', 'bg' => 'var(--m-line)', 'fg' => 'var(--m-muted)']; @endphp
            <div class="m-activity-row">
                <div class="m-activity-icon" style="background:{{ $meta['bg'] }};color:{{ $meta['fg'] }};"><i class="fa-solid {{ $meta['icon'] }}"></i></div>
                <div class="m-activity-body">
                    <div class="m-activity-title">{{ ucfirst($entry->action) }} {{ $entry->entity_type }}</div>
                    <div class="m-activity-sub">{{ $entry->entity_name ?? '—' }} &middot; {{ $entry->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <div class="m-empty-row">No recent activity yet</div>
        @endforelse
    </div>

    @if(collect($chartData['income'])->filter(fn($v) => $v !== null)->isNotEmpty())
    <div class="m-finance-card">
        <div class="m-finance-head">
            <div class="m-finance-icon"><i class="fa-solid fa-chart-line"></i></div>
            <div style="flex:1;">
                <div class="m-finance-title">Portfolio Financial Overview</div>
                <div class="m-finance-sub">{{ $chartYear }} &middot; Income, expenses, profit, credits &amp; debits</div>
            </div>
            <div class="m-finance-year">{{ $chartYear }}</div>
        </div>
        <div class="m-finance-canvas"><canvas id="mobileChart"></canvas></div>
        <div class="m-finance-legend" id="mFinanceLegend">
            <button type="button" class="m-finance-chip active" data-series="income" style="border-color:#17A96C;background:#E6F6EE;color:#0B6E47;"><span class="dot" style="background:#17A96C;"></span>Income</button>
            <button type="button" class="m-finance-chip" data-series="expenses"><span class="dot" style="background:#D64545;"></span>Expenses</button>
            <button type="button" class="m-finance-chip" data-series="credits"><span class="dot" style="background:#4A7DF0;"></span>Credits</button>
            <button type="button" class="m-finance-chip" data-series="debits"><span class="dot" style="background:#E7B266;"></span>Debits</button>
            <button type="button" class="m-finance-chip" data-series="profit"><span class="dot" style="background:#7A5AF8;"></span>Profit</button>
        </div>
    </div>
    @endif

    @if($buildingPerformance->isNotEmpty())
    <div class="m-sec-head"><div class="t">Property performance</div><a href="{{ route('buildings.index') }}">View all</a></div>
    <div class="m-perf-scroll">
        @foreach($buildingPerformance as $perf)
            @php
                $b = $perf['building'];
                $pct = min(100, max(0, (int) $perf['occupancy_percent']));
                $dash = round($pct * 1.508, 1) . ' 151';
                $hasType = !empty($b->property_type);
            @endphp
            <a href="{{ route('buildings.show', $b) }}" class="m-perf-card">
                <div class="m-perf-top">
                    <svg width="62" height="62" viewBox="0 0 60 60" style="flex-shrink:0;">
                        <circle cx="30" cy="30" r="24" fill="none" stroke="#F1F3F8" stroke-width="7"></circle>
                        <circle cx="30" cy="30" r="24" fill="none" stroke="#E7B266" stroke-width="7" stroke-linecap="round" stroke-dasharray="{{ $dash }}" transform="rotate(-90 30 30)"></circle>
                        <text x="30" y="34" text-anchor="middle" font-family="Poppins" font-size="12" font-weight="800" fill="#17203A">{{ $pct }}%</text>
                    </svg>
                    <div style="flex:1;min-width:0;">
                        <div class="m-perf-name">{{ $b->property_name }}</div>
                        <div class="m-perf-chips">
                            <span class="m-perf-chip" style="background:var(--m-gold-tint);color:var(--m-gold-text);">{{ $b->property_code }}</span>
                            <span class="m-perf-chip" style="background:{{ $hasType ? 'var(--m-blue-tint)' : 'var(--m-line)' }};color:{{ $hasType ? 'var(--m-blue)' : '#6B7688' }};">{{ $hasType ? strtoupper($b->property_type) : 'ACTIVE' }}</span>
                        </div>
                    </div>
                </div>
                <div class="m-perf-foot">
                    <div><span class="l">INCOME</span><span class="v" style="color:var(--m-green);">BHD {{ number_format($perf['total_income'], 0) }}</span></div>
                    <div><span class="l">NET</span><span class="v">BHD {{ number_format($perf['net_income'], 0) }}</span></div>
                    <div><span class="l">TENANTS</span><span class="v">{{ $perf['tenant_count'] }}</span></div>
                </div>
            </a>
        @endforeach
    </div>
    @endif

    <div class="m-sec-head"><div class="t">Recent Buildings</div></div>
    <div class="m-list-card">
        <div class="m-list-head">
            <div class="m-list-head-icon" style="background:var(--m-gold-tint);"><i class="fa-solid fa-building" style="color:var(--m-gold-text);font-size:14px;"></i></div>
            <div class="m-list-head-title">Recent Buildings</div>
            <a href="{{ route('buildings.index') }}">View all</a>
        </div>
        @forelse($recentBuildings->take(4) as $b)
            <a href="{{ route('buildings.show', $b) }}" class="m-list-row">
                <span class="m-list-code">{{ $b->property_code }}</span>
                <span class="m-list-name">{{ $b->property_name }}</span>
                <span class="m-list-meta" style="color:{{ $b->property_type ? 'var(--m-blue)' : 'var(--m-faint)' }};">{{ $b->property_type ?? '—' }}</span>
            </a>
        @empty
            <div class="m-empty-row">No buildings yet</div>
        @endforelse
    </div>

    <div class="m-sec-head" style="padding-top:16px;"><div class="t">Recent Units</div></div>
    <div class="m-list-card">
        <div class="m-list-head">
            <div class="m-list-head-icon" style="background:var(--m-green-tint);"><i class="fa-solid fa-door-open" style="color:var(--m-green);font-size:14px;"></i></div>
            <div class="m-list-head-title">Recent Units</div>
            <a href="{{ route('property-units.index') }}">View all</a>
        </div>
        @forelse($recentUnits->take(5) as $u)
            <a href="{{ route('property-units.show', $u) }}" class="m-list-row">
                <span class="m-list-name" style="font-weight:700;">{{ $u->unit_name }}</span>
                <span class="m-list-code" style="background:var(--m-line);color:#6B7688;">{{ optional($u->building)->property_code ?? '—' }}</span>
                <span class="m-list-meta" style="min-width:64px;text-align:right;color:{{ $u->unit_condition ? 'var(--m-purple)' : 'var(--m-faint)' }};">{{ $u->unit_condition ?? '—' }}</span>
            </a>
        @empty
            <div class="m-empty-row">No units yet</div>
        @endforelse
    </div>

    <div class="m-dash-bottom-space"></div>
</div>

<div class="page-header">
    <div>
        <h1 class="page-header-title">Dashboard</h1>
        <p class="page-header-sub">Overview of your real estate portfolio</p>
    </div>
</div>
{{-- STATS --}}
<div class="dash-stats">
    <a href="{{ route('buildings.index') }}" class="dash-stat">
        <div class="dash-stat-icon gold"><i class="fa-solid fa-building"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['buildings'] }}</div>
            <div class="dash-stat-lbl">Buildings</div>
        </div>
    </a>
    <a href="{{ route('floors.global') }}" class="dash-stat">
        <div class="dash-stat-icon blue"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['floors'] }}</div>
            <div class="dash-stat-lbl">Floors</div>
        </div>
    </a>
    <a href="{{ route('property-units.index') }}" class="dash-stat">
        <div class="dash-stat-icon green"><i class="fa-solid fa-door-open"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['units'] }}</div>
            <div class="dash-stat-lbl">Total Units</div>
        </div>
    </a>
    <a href="{{ route('property-units.index', ['unit_condition' => 'Furnished']) }}" class="dash-stat">
        <div class="dash-stat-icon purple"><i class="fa-solid fa-couch"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['furnished'] }}</div>
            <div class="dash-stat-lbl">Furnished</div>
        </div>
    </a>
    <a href="{{ route('property-units.index', ['unit_condition' => 'Fitted']) }}" class="dash-stat">
        <div class="dash-stat-icon rose"><i class="fa-solid fa-hammer"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['fitted'] }}</div>
            <div class="dash-stat-lbl">Fitted</div>
        </div>
    </a>
</div>

{{-- IMPORT / EXPORT HERO --}}
<div class="data-hero">
    <div class="data-hero-icon">
        <i class="fa-solid fa-arrows-rotate"></i>
    </div>
    <div class="data-hero-text">
        <div class="data-hero-title">Import &amp; Export Data</div>
        <div class="data-hero-sub">
            Bulk import buildings, floors, and units from a single spreadsheet — or export all records to Excel.
        </div>
    </div>
    <div class="data-hero-actions">
        <a href="{{ route('data.export') }}" class="btn btn-success">
            <i class="fa-solid fa-file-excel"></i> Export All
        </a>
        <button type="button" class="btn btn-primary" onclick="openSmartImport()">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Smart Import
        </button>
    </div>
</div>

{{-- PORTFOLIO FINANCIAL OVERVIEW --}}
<div class="finance-card">
    <div class="finance-card-head">
        <div class="finance-head-left">
            <div class="finance-icon"><i class="fa-solid fa-chart-line"></i></div>
            <div>
                <div class="finance-title">Portfolio Financial Overview</div>
                <div class="finance-sub">{{ $chartYear }} &middot; Combined income, expenses, profit, credits &amp; debits</div>
            </div>
        </div>
        <span class="finance-year-badge"><i class="fa-regular fa-calendar"></i> {{ $chartYear }}</span>
    </div>

    @if(collect($chartData['income'])->filter(fn($v) => $v !== null)->isEmpty())
        <div class="finance-empty">
            <i class="fa-solid fa-chart-line"></i>
            No financial activity recorded for {{ $chartYear }} yet
        </div>
    @else
        <div class="finance-legend">
            <button type="button" class="legend-btn" data-dataset="0"><span class="legend-dot" style="background:#10b981;"></span> Income</button>
            <button type="button" class="legend-btn" data-dataset="1"><span class="legend-dot" style="background:#ef4444;"></span> Expenses</button>
            <button type="button" class="legend-btn" data-dataset="2"><span class="legend-dot" style="background:#0ea5e9;"></span> Credits</button>
            <button type="button" class="legend-btn" data-dataset="3"><span class="legend-dot" style="background:#f59e0b;"></span> Debits</button>
            <button type="button" class="legend-btn" data-dataset="4"><span class="legend-dot" style="background:#8b5cf6;"></span> Profit</button>
        </div>
        <div class="finance-canvas-wrap">
            <canvas id="portfolioChart"></canvas>
        </div>
    @endif
</div>

{{-- INDIVIDUAL PROPERTY PERFORMANCE --}}
@if($buildingPerformance->isNotEmpty())
<div class="property-section-head">
    <h2 class="property-section-title"><i class="fa-solid fa-building"></i> Individual Property Performance</h2>
</div>
<div class="property-grid">
    @foreach($buildingPerformance as $perf)
    @php $building = $perf['building']; @endphp
    <div class="property-card" data-href="{{ route('buildings.show', $building) }}">
        <div class="property-carousel">
            <span class="property-photo-badge badge badge-gold">{{ $building->property_type ?? 'Active' }}</span>
            @if($building->images->isNotEmpty())
                <div class="property-carousel-track">
                    @foreach($building->images as $image)
                        <img src="{{ $image->url }}" alt="{{ $building->property_name }}">
                    @endforeach
                </div>
                @if($building->images->count() > 1)
                <button type="button" class="property-carousel-btn prev" onclick="event.stopPropagation()"><i class="fa-solid fa-chevron-left"></i></button>
                <button type="button" class="property-carousel-btn next" onclick="event.stopPropagation()"><i class="fa-solid fa-chevron-right"></i></button>
                <div class="property-carousel-dots" onclick="event.stopPropagation()">
                    @foreach($building->images as $i => $image)
                        <button type="button" class="property-carousel-dot {{ $i === 0 ? 'active' : '' }}"></button>
                    @endforeach
                </div>
                @endif
            @else
                <div class="property-carousel-empty">
                    <i class="fa-solid fa-building"></i>
                    <span>No photos yet</span>
                </div>
            @endif
        </div>

        <div class="property-body">
            <div class="property-title-row">
                <span class="property-name">{{ $building->property_name }}</span>
                <span class="badge badge-gold">{{ $building->property_type ?? 'Active' }}</span>
            </div>
            <div class="property-address">
                <i class="fa-solid fa-location-dot"></i>{{ $building->full_address ?? $building->property_code }}
            </div>

            <div class="property-period-label">Current Month ({{ now()->format('F Y') }}) Overview</div>

            <div class="property-stats-row">
                <div class="property-stat-box income">
                    <div class="property-stat-label">Total Income</div>
                    <div class="property-stat-value">{{ number_format($perf['total_income'], 0) }}</div>
                </div>
                <div class="property-stat-box net">
                    <div class="property-stat-label">Net Income</div>
                    <div class="property-stat-value">{{ number_format($perf['net_income'], 0) }}</div>
                </div>
                <div class="property-stat-box occ">
                    <div class="property-stat-label">Occupancy</div>
                    <div class="property-stat-value">{{ $perf['occupancy_percent'] }}%</div>
                    <div class="property-stat-sub">{{ $perf['tenant_count'] }} Tenants</div>
                </div>
            </div>

            <div class="property-expense-title">Expense Breakdown</div>
            <div class="property-expense-list">
                @if(array_sum($perf['expenses']) <= 0)
                    <div class="property-expense-empty">No expenses recorded this month</div>
                @else
                    <div class="property-expense-row">
                        <span class="label"><i class="fa-solid fa-bolt" style="color:var(--warning);"></i>Electricity</span>
                        <span class="value">BHD {{ number_format($perf['expenses']['electricity'], 0) }}</span>
                    </div>
                    <div class="property-expense-row">
                        <span class="label"><i class="fa-solid fa-droplet" style="color:var(--info);"></i>Water</span>
                        <span class="value">BHD {{ number_format($perf['expenses']['water'], 0) }}</span>
                    </div>
                    <div class="property-expense-row">
                        <span class="label"><i class="fa-solid fa-wrench" style="color:var(--danger);"></i>Maintenance</span>
                        <span class="value">BHD {{ number_format($perf['expenses']['maintenance'], 0) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- SMART IMPORT RESULTS --}}
@if(session('smart_import_results'))
<div class="smart-results-wrap" id="smartResultsWrap">
    <div class="smart-results-top">
        <div class="smart-results-heading">
            @php
                $totalImported = collect(session('smart_import_results'))->sum('imported');
                $totalErrors   = collect(session('smart_import_results'))->sum(fn($r) => count($r['errors']));
            @endphp
            <i class="fa-solid {{ $totalErrors > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check' }}" style="color:{{ $totalErrors > 0 ? 'var(--accent)' : '#10B981' }}"></i>
            Import complete &mdash; {{ $totalImported }} record(s) saved
            @if($totalErrors > 0), {{ $totalErrors }} skipped @endif
        </div>
        <button class="smart-results-close" onclick="document.getElementById('smartResultsWrap').remove()">
            <i class="fa-solid fa-xmark"></i>
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
    <button class="import-banner-close" onclick="this.closest('.import-banner').remove()">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
@endif

{{-- SMART IMPORT MODAL --}}
<div class="modal-overlay" id="smartImportModal" onclick="if(event.target===this)closeSmartImport()">
    <div class="modal-box smart-import-box">

        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div class="modal-header-text">
                    <div class="modal-header-title">Smart Import</div>
                    <div class="modal-header-sub">Upload any file — auto-detected &amp; routed to the right tables</div>
                </div>
                <button class="modal-close-btn" type="button" onclick="closeSmartImport()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="modal-body" style="padding:20px 24px;">

            {{-- Detection info --}}
            <div class="smart-detect-info">
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

{{-- RECENT RECORDS --}}
<div class="dash-grid">

    <div class="dash-card">
        <div class="dash-card-head">
            <div class="dash-card-title"><i class="fa-solid fa-building"></i> Recent Buildings</div>
            <a href="{{ route('buildings.index') }}" class="btn btn-outline btn-sm">View all</a>
        </div>
        @if($recentBuildings->isEmpty())
            <div class="empty-dash"><i class="fa-solid fa-building" style="font-size:24px;display:block;margin-bottom:8px;"></i> No buildings yet</div>
        @else
        <table class="dash-table">
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
        @endif
    </div>

    <div class="dash-card">
        <div class="dash-card-head">
            <div class="dash-card-title"><i class="fa-solid fa-door-open"></i> Recent Units</div>
            <a href="{{ route('property-units.index') }}" class="btn btn-outline btn-sm">View all</a>
        </div>
        @if($recentUnits->isEmpty())
            <div class="empty-dash"><i class="fa-solid fa-door-open" style="font-size:24px;display:block;margin-bottom:8px;"></i> No units yet</div>
        @else
        <table class="dash-table">
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
        @endif
    </div>

</div>

@endsection
