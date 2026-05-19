@extends('layouts.admin')

@section('title', 'Form / Template Management')
@section('topbar-title', 'Form / Template Management')

@push('styles')
<style>
    .tab-bar {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid var(--card-border);
        margin-bottom: 24px;
    }
    .tab-btn {
        padding: 11px 22px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-muted);
        border: none;
        background: none;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: color 0.18s, border-color 0.18s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .tab-btn:hover { color: var(--text-primary); }
    .tab-btn.active {
        color: var(--accent);
        border-bottom-color: var(--accent);
    }
    .tab-btn .tab-count {
        background: var(--accent-dim);
        color: var(--accent);
        font-size: 10px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 20px;
    }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    .fc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }
    .fc-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        transition: box-shadow 0.2s, transform 0.2s;
        overflow: hidden;
    }
    .fc-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
    .fc-card-header {
        padding: 20px 22px 16px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        border-bottom: 1px solid var(--card-border);
    }
    .fc-icon {
        width: 44px; height: 44px;
        border-radius: var(--radius-sm);
        background: var(--accent-dim);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        color: var(--accent);
        flex-shrink: 0;
    }
    .fc-icon.tpl { background: #EFF6FF; color: var(--info); }
    .fc-title { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary); }
    .fc-desc { font-size: 12px; color: var(--text-muted); margin-top: 3px; line-height: 1.5; }
    .fc-card-body { padding: 16px 22px; flex: 1; display: flex; flex-direction: column; gap: 10px; }
    .fc-meta { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); }
    .fc-meta i { width: 16px; text-align: center; color: var(--text-muted); font-size: 12px; }
    .fc-card-footer {
        padding: 14px 22px;
        border-top: 1px solid var(--card-border);
        background: var(--page-bg);
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Form / Template Management</span>
        </div>
        <h1 class="page-header-title">Form / Template Management</h1>
        <p class="page-header-sub">Configure which fields appear in forms and export templates</p>
    </div>
</div>

{{-- TABS --}}
<div class="tab-bar">
    <button class="tab-btn" id="tab-forms" onclick="switchTab('forms')">
        <i class="fa-solid fa-wpforms"></i>
        Forms Management
        <span class="tab-count">2</span>
    </button>
    <button class="tab-btn" id="tab-templates" onclick="switchTab('templates')">
        <i class="fa-solid fa-file-export"></i>
        Template Management
        <span class="tab-count">2</span>
    </button>
</div>

{{-- FORMS PANEL --}}
<div class="tab-panel" id="panel-forms">
    <div class="fc-grid">

        @php
            $bFormFields = $configs['building']['form'];
            $bFormCount  = $bFormFields ? count(array_filter($bFormFields, fn($f) => !empty($f['visible']))) : null;
            $uFormFields = $configs['unit']['form'];
            $uFormCount  = $uFormFields ? count(array_filter($uFormFields, fn($f) => !empty($f['visible']))) : null;
        @endphp

        {{-- Building Form --}}
        <div class="fc-card">
            <div class="fc-card-header">
                <div class="fc-icon"><i class="fa-solid fa-building"></i></div>
                <div>
                    <div class="fc-title">Building Form</div>
                    <div class="fc-desc">Controls which fields appear in the add/edit building form</div>
                </div>
            </div>
            <div class="fc-card-body">
                <div class="fc-meta">
                    <i class="fa-solid fa-circle-dot"></i>
                    <span>Status:</span>
                    @if($bFormFields !== null)
                        <span class="badge badge-green"><i class="fa-solid fa-check"></i> Configured</span>
                    @else
                        <span class="badge badge-gray">Not configured</span>
                    @endif
                </div>
                @if($bFormCount !== null)
                <div class="fc-meta">
                    <i class="fa-solid fa-list-check"></i>
                    <span>{{ $bFormCount }} visible field{{ $bFormCount !== 1 ? 's' : '' }} selected</span>
                </div>
                @endif
            </div>
            <div class="fc-card-footer">
                <a href="{{ route('form-configs.edit', ['building', 'form']) }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Configuration
                </a>
            </div>
        </div>

        {{-- Unit Form --}}
        <div class="fc-card">
            <div class="fc-card-header">
                <div class="fc-icon"><i class="fa-solid fa-door-open"></i></div>
                <div>
                    <div class="fc-title">Unit Form</div>
                    <div class="fc-desc">Controls which fields appear in the add/edit unit form</div>
                </div>
            </div>
            <div class="fc-card-body">
                <div class="fc-meta">
                    <i class="fa-solid fa-circle-dot"></i>
                    <span>Status:</span>
                    @if($uFormFields !== null)
                        <span class="badge badge-green"><i class="fa-solid fa-check"></i> Configured</span>
                    @else
                        <span class="badge badge-gray">Not configured</span>
                    @endif
                </div>
                @if($uFormCount !== null)
                <div class="fc-meta">
                    <i class="fa-solid fa-list-check"></i>
                    <span>{{ $uFormCount }} visible field{{ $uFormCount !== 1 ? 's' : '' }} selected</span>
                </div>
                @endif
            </div>
            <div class="fc-card-footer">
                <a href="{{ route('form-configs.edit', ['unit', 'form']) }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Configuration
                </a>
            </div>
        </div>

    </div>
</div>

{{-- TEMPLATES PANEL --}}
<div class="tab-panel" id="panel-templates">
    <div class="fc-grid">

        @php
            $bTplFields = $configs['building']['template'];
            $bTplCount  = $bTplFields ? count(array_filter($bTplFields, fn($f) => !empty($f['visible']))) : null;
            $uTplFields = $configs['unit']['template'];
            $uTplCount  = $uTplFields ? count(array_filter($uTplFields, fn($f) => !empty($f['visible']))) : null;
        @endphp

        {{-- Building Template --}}
        <div class="fc-card">
            <div class="fc-card-header">
                <div class="fc-icon tpl"><i class="fa-solid fa-file-export"></i></div>
                <div>
                    <div class="fc-title">Building Template</div>
                    <div class="fc-desc">Defines columns used for building import &amp; export</div>
                </div>
            </div>
            <div class="fc-card-body">
                <div class="fc-meta">
                    <i class="fa-solid fa-circle-dot"></i>
                    <span>Status:</span>
                    @if($bTplFields !== null)
                        <span class="badge badge-green"><i class="fa-solid fa-check"></i> Configured</span>
                    @else
                        <span class="badge badge-gray">Not configured</span>
                    @endif
                </div>
                @if($bTplCount !== null)
                <div class="fc-meta">
                    <i class="fa-solid fa-list-check"></i>
                    <span>{{ $bTplCount }} column{{ $bTplCount !== 1 ? 's' : '' }} selected</span>
                </div>
                @endif
            </div>
            <div class="fc-card-footer">
                <a href="{{ route('form-configs.edit', ['building', 'template']) }}" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Configuration
                </a>
            </div>
        </div>

        {{-- Unit Template --}}
        <div class="fc-card">
            <div class="fc-card-header">
                <div class="fc-icon tpl"><i class="fa-solid fa-file-export"></i></div>
                <div>
                    <div class="fc-title">Unit Template</div>
                    <div class="fc-desc">Defines columns used for unit import &amp; export</div>
                </div>
            </div>
            <div class="fc-card-body">
                <div class="fc-meta">
                    <i class="fa-solid fa-circle-dot"></i>
                    <span>Status:</span>
                    @if($uTplFields !== null)
                        <span class="badge badge-green"><i class="fa-solid fa-check"></i> Configured</span>
                    @else
                        <span class="badge badge-gray">Not configured</span>
                    @endif
                </div>
                @if($uTplCount !== null)
                <div class="fc-meta">
                    <i class="fa-solid fa-list-check"></i>
                    <span>{{ $uTplCount }} column{{ $uTplCount !== 1 ? 's' : '' }} selected</span>
                </div>
                @endif
            </div>
            <div class="fc-card-footer">
                <a href="{{ route('form-configs.edit', ['unit', 'template']) }}" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Configuration
                </a>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
        history.replaceState(null, '', '?tab=' + tab);
    }

    // Activate tab from URL or default to forms
    const urlTab = new URLSearchParams(window.location.search).get('tab');
    switchTab(urlTab === 'templates' ? 'templates' : 'forms');
</script>
@endpush
