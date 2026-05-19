@extends('layouts.admin')

@section('title', 'Edit ' . $title)
@section('topbar-title', 'Edit ' . $title)

@push('styles')
<style>
    .fc-edit-layout {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 20px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .fc-edit-layout { grid-template-columns: 1fr; }
    }

    /* Field list */
    .field-list {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .field-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--card-border);
        background: var(--card-bg);
        cursor: grab;
        transition: background 0.15s, box-shadow 0.15s;
        user-select: none;
    }
    .field-row:last-child { border-bottom: none; }
    .field-row:hover { background: #FAFBFC; }
    .field-row.sortable-ghost {
        background: var(--accent-dim);
        opacity: 0.7;
        box-shadow: 0 4px 16px var(--accent-glow);
        border-radius: var(--radius-sm);
    }
    .field-row.sortable-chosen { box-shadow: var(--shadow-md); }
    .field-row.hidden-field { opacity: 0.55; }

    .drag-handle {
        color: var(--text-muted);
        font-size: 12px;
        cursor: grab;
        padding: 2px 4px;
        flex-shrink: 0;
    }
    .drag-handle:active { cursor: grabbing; }

    /* Toggle switch */
    .toggle-wrap {
        position: relative;
        width: 36px;
        height: 20px;
        flex-shrink: 0;
    }
    .toggle-wrap input {
        opacity: 0;
        width: 0; height: 0;
        position: absolute;
    }
    .toggle-slider {
        position: absolute;
        inset: 0;
        background: #CBD5E1;
        border-radius: 20px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .toggle-slider::before {
        content: '';
        position: absolute;
        width: 14px; height: 14px;
        left: 3px; top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .toggle-wrap input:checked + .toggle-slider {
        background: var(--accent);
    }
    .toggle-wrap input:checked + .toggle-slider::before {
        transform: translateX(16px);
    }

    .field-label-col {
        flex: 1;
        min-width: 0;
    }
    .field-label-text {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-primary);
        line-height: 1.3;
    }
    .field-name-tag {
        display: inline-block;
        font-family: 'Courier New', monospace;
        font-size: 10.5px;
        color: var(--text-muted);
        background: var(--page-bg);
        border: 1px solid var(--card-border);
        border-radius: 4px;
        padding: 1px 6px;
        margin-top: 2px;
    }
    .field-section-tag {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
        background: var(--page-bg);
        border: 1px solid var(--card-border);
        border-radius: 4px;
        padding: 2px 7px;
        flex-shrink: 0;
    }
    .badge-required {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 7px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        background: #FEF2F2;
        color: var(--danger);
        flex-shrink: 0;
    }

    /* Preview panel */
    .preview-card {
        position: sticky;
        top: 88px;
    }
    .preview-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
        max-height: 500px;
        overflow-y: auto;
        padding: 4px 0;
    }
    .preview-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        border-radius: var(--radius-sm);
        font-size: 13px;
        color: var(--text-primary);
        background: var(--page-bg);
        transition: background 0.12s;
    }
    .preview-item i {
        font-size: 11px;
        color: var(--success);
        flex-shrink: 0;
    }
    .preview-empty {
        text-align: center;
        padding: 24px 16px;
        color: var(--text-muted);
        font-size: 13px;
    }

    /* Actions bar */
    .fc-actions-bar {
        margin-top: 20px;
        padding: 16px 22px;
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        box-shadow: var(--shadow-sm);
    }

    /* Section separator rows */
    .section-header-row {
        padding: 8px 16px 6px;
        background: var(--page-bg);
        border-bottom: 1px solid var(--card-border);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Custom field delete button */
    .btn-delete-custom {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--danger);
        background: #FEF2F2;
        color: var(--danger);
        font-size: 11px;
        cursor: pointer;
        flex-shrink: 0;
        transition: background 0.15s, color 0.15s;
        padding: 0;
    }
    .btn-delete-custom:hover {
        background: var(--danger);
        color: #fff;
    }

    /* Custom field badge */
    .badge-custom {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 7px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        background: #FEF3C7;
        color: #92400E;
        flex-shrink: 0;
    }

    /* Modal overlay */
    .cf-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .cf-modal-overlay.active {
        display: flex;
    }
    .cf-modal {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius);
        box-shadow: 0 20px 60px rgba(0,0,0,0.35);
        width: 100%;
        max-width: 520px;
        max-height: 90vh;
        overflow-y: auto;
        animation: cfModalIn 0.2s ease;
    }
    @keyframes cfModalIn {
        from { opacity:0; transform: translateY(-16px) scale(0.97); }
        to   { opacity:1; transform: translateY(0) scale(1); }
    }
    .cf-modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 18px 22px 16px;
        border-bottom: 1px solid var(--card-border);
    }
    .cf-modal-header-icon {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-sm);
        background: var(--accent-dim);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        font-size: 15px;
        flex-shrink: 0;
    }
    .cf-modal-header h4 {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 2px;
    }
    .cf-modal-header p {
        font-size: 12px;
        color: var(--text-muted);
        margin: 0;
    }
    .cf-modal-body {
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .cf-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 14px 22px;
        border-top: 1px solid var(--card-border);
    }
    .cf-form-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .cf-form-group input,
    .cf-form-group select {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid var(--card-border);
        border-radius: var(--radius-sm);
        background: var(--page-bg);
        color: var(--text-primary);
        font-size: 13.5px;
        transition: border 0.15s;
        box-sizing: border-box;
    }
    .cf-form-group input:focus,
    .cf-form-group select:focus {
        outline: none;
        border-color: var(--accent);
    }
    .cf-options-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .cf-option-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cf-option-row input {
        flex: 1;
    }
    .cf-option-remove {
        width: 28px;
        height: 28px;
        border: 1px solid var(--card-border);
        border-radius: var(--radius-sm);
        background: var(--page-bg);
        color: var(--text-muted);
        cursor: pointer;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        padding: 0;
        transition: border-color 0.15s, color 0.15s;
    }
    .cf-option-remove:hover { border-color: var(--danger); color: var(--danger); }
    .cf-required-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .cf-required-row label {
        margin: 0;
        text-transform: none;
        letter-spacing: 0;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-primary);
        cursor: pointer;
    }

    /* Toast */
    .cf-toast {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 99999;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        border-radius: var(--radius-sm);
        font-size: 13.5px;
        font-weight: 600;
        box-shadow: 0 8px 24px rgba(0,0,0,0.18);
        animation: cfToastIn 0.25s ease;
        pointer-events: none;
    }
    @keyframes cfToastIn {
        from { opacity:0; transform: translateY(12px); }
        to   { opacity:1; transform: translateY(0); }
    }
    .cf-toast.success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
    .cf-toast.error   { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
</style>
@endpush

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('form-configs.index') }}">Form & Template Management</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Edit {{ $title }}</span>
        </div>
        <h1 class="page-header-title">Edit {{ $title }}</h1>
        <p class="page-header-sub">Drag to reorder, toggle to show/hide fields</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('form-configs.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        @if($configType === 'form')
        <button type="button" class="btn btn-primary" id="openAddFieldModal">
            <i class="fa-solid fa-plus"></i> Add New Field
        </button>
        @endif
    </div>
</div>

<div class="fc-edit-layout">

    {{-- LEFT: Sortable field list --}}
    <div>
        <div class="card" style="overflow:hidden;">
            <div class="card-header">
                <div class="card-header-icon">
                    @if($configType === 'template')
                        <i class="fa-solid fa-file-export"></i>
                    @else
                        <i class="fa-solid fa-wpforms"></i>
                    @endif
                </div>
                <div>
                    <h3>{{ $title }} Fields</h3>
                    <p>Drag to reorder — toggle to show or hide each field</p>
                </div>
                <div style="margin-left:auto;display:flex;gap:8px;">
                    <button type="button" class="btn btn-outline btn-sm" id="selectAll">
                        <i class="fa-solid fa-eye"></i> Show All
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" id="deselectAll">
                        <i class="fa-solid fa-eye-slash"></i> Hide All
                    </button>
                </div>
            </div>

            {{-- Group fields by section --}}
            @php
                $sections = collect($fields)->groupBy('section');
            @endphp

            <div id="sortableList" class="field-list">
                @foreach($sections as $sectionName => $sectionFields)
                    <div class="section-header-row" data-section-header="1">
                        <i class="fa-solid fa-folder-open" style="font-size:9px;"></i>
                        {{ $sectionName }}
                        <span style="margin-left:auto;font-size:9px;font-weight:500;opacity:0.7;">{{ count($sectionFields) }} fields</span>
                    </div>
                    @foreach($sectionFields as $field)
                    @php
                        $isCustom  = !empty($field['custom']);
                        $customDef = $isCustom ? $customFieldDefs->firstWhere('name', $field['name']) : null;
                        $customId  = $customDef?->id ?? '';
                    @endphp
                    <div class="field-row {{ !($field['visible'] ?? true) ? 'hidden-field' : '' }}"
                         data-name="{{ $field['name'] }}"
                         data-label="{{ $field['label'] }}"
                         data-required="{{ ($field['required'] ?? false) ? '1' : '0' }}"
                         data-section="{{ $field['section'] ?? '' }}"
                         data-custom="{{ $isCustom ? '1' : '0' }}"
                         data-custom-id="{{ $customId }}">

                        <span class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></span>

                        <label class="toggle-wrap" title="{{ ($field['visible'] ?? true) ? 'Visible — click to hide' : 'Hidden — click to show' }}">
                            <input type="checkbox"
                                   class="field-toggle"
                                   data-name="{{ $field['name'] }}"
                                   {{ ($field['visible'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>

                        <div class="field-label-col">
                            <div class="field-label-text">{{ $field['label'] }}</div>
                            <span class="field-name-tag">{{ $field['name'] }}</span>
                        </div>

                        <span class="field-section-tag">{{ $field['section'] ?? '' }}</span>

                        @if($isCustom)
                            <span class="badge-custom"><i class="fa-solid fa-puzzle-piece" style="font-size:8px;"></i> Custom</span>
                        @endif

                        @if(!empty($field['required']))
                            <span class="badge-required"><i class="fa-solid fa-asterisk" style="font-size:8px;"></i> Required</span>
                        @endif

                        @if($isCustom && $customId)
                            <button type="button"
                                    class="btn-delete-custom"
                                    data-custom-id="{{ $customId }}"
                                    title="Remove this custom field">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        @endif

                    </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- SAVE FORM + ACTIONS BAR --}}
        <form method="POST"
              action="{{ route('form-configs.update', [$formType, $configType]) }}"
              id="saveForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="fields" id="fieldsPayload">

            <div class="fc-actions-bar">
                <div style="font-size:13px;color:var(--text-muted);">
                    <i class="fa-solid fa-circle-info" style="color:var(--accent);margin-right:4px;"></i>
                    Changes apply immediately to the
                    @if($configType === 'form') add/edit form @else import/export template @endif
                    after saving.
                </div>
                <div style="display:flex;gap:10px;">
                    <a href="{{ route('form-configs.index') }}" class="btn btn-outline">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                        <i class="fa-solid fa-floppy-disk"></i> Save Configuration
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- RIGHT: Preview panel --}}
    <div class="preview-card">
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-eye"></i></div>
                <div>
                    <h3>Preview</h3>
                    <p>Currently visible fields</p>
                </div>
                <span id="visibleCount" class="badge badge-gold" style="margin-left:auto;">
                    {{ count(array_filter($fields, fn($f) => $f['visible'] ?? true)) }}
                </span>
            </div>
            <div class="card-body" style="padding:12px 16px;">
                <div class="preview-list" id="previewList">
                    @foreach($fields as $field)
                        @if($field['visible'] ?? true)
                        <div class="preview-item" data-preview="{{ $field['name'] }}">
                            <i class="fa-solid fa-check-circle"></i>
                            <span>{{ $field['label'] }}</span>
                        </div>
                        @endif
                    @endforeach
                    @if(count(array_filter($fields, fn($f) => $f['visible'] ?? true)) === 0)
                        <div class="preview-empty" id="emptyMsg">
                            <i class="fa-solid fa-eye-slash" style="font-size:20px;margin-bottom:8px;display:block;"></i>
                            No fields visible
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="card" style="margin-top:16px;">
            <div class="card-body" style="padding:14px 18px;">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                        <span style="color:var(--text-secondary);">Total fields</span>
                        <strong>{{ count($fields) }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                        <span style="color:var(--text-secondary);">Visible</span>
                        <span id="statVisible" style="color:var(--success);font-weight:700;">
                            {{ count(array_filter($fields, fn($f) => $f['visible'] ?? true)) }}
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                        <span style="color:var(--text-secondary);">Hidden</span>
                        <span id="statHidden" style="color:var(--text-muted);font-weight:700;">
                            {{ count(array_filter($fields, fn($f) => !($f['visible'] ?? true))) }}
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
                        <span style="color:var(--text-secondary);">Required</span>
                        <span style="color:var(--danger);font-weight:700;">
                            {{ count(array_filter($fields, fn($f) => !empty($f['required']))) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ADD NEW FIELD MODAL --}}
@if($configType === 'form')
<div class="cf-modal-overlay" id="addFieldModal" role="dialog" aria-modal="true" aria-labelledby="cfModalTitle">
    <div class="cf-modal">
        <div class="cf-modal-header">
            <div class="cf-modal-header-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
            <div>
                <h4 id="cfModalTitle">Add New Field</h4>
                <p>Add a custom field to the <strong>{{ $formType }}</strong> form</p>
            </div>
            <button type="button" id="closeAddFieldModal" style="margin-left:auto;background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:16px;padding:4px;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="cf-modal-body">
            <div class="cf-form-group">
                <label for="cfLabel">Label <span style="color:var(--danger);">*</span></label>
                <input type="text" id="cfLabel" placeholder="e.g. Parking Level" autocomplete="off" maxlength="255">
                <span id="cfLabelError" style="display:none;font-size:11.5px;color:var(--danger);margin-top:4px;display:block;"></span>
            </div>
            <div class="cf-form-group">
                <label for="cfFieldType">Field Type <span style="color:var(--danger);">*</span></label>
                <select id="cfFieldType">
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="select">Select (Dropdown)</option>
                    <option value="textarea">Textarea</option>
                </select>
            </div>
            <div class="cf-form-group" id="cfOptionsSection" style="display:none;">
                <label>Options <span style="color:var(--danger);">*</span></label>
                <div class="cf-options-list" id="cfOptionsList">
                    <div class="cf-option-row">
                        <input type="text" placeholder="Option 1" class="cf-option-input">
                        <button type="button" class="cf-option-remove" title="Remove"><i class="fa-solid fa-minus"></i></button>
                    </div>
                </div>
                <button type="button" id="cfAddOption" class="btn btn-outline btn-sm" style="margin-top:8px;">
                    <i class="fa-solid fa-plus"></i> Add Option
                </button>
            </div>
            <div class="cf-required-row">
                <label class="toggle-wrap" style="position:relative;width:36px;height:20px;flex-shrink:0;" for="cfIsRequired">
                    <input type="checkbox" id="cfIsRequired">
                    <span class="toggle-slider"></span>
                </label>
                <label for="cfIsRequired">Required field</label>
            </div>
        </div>
        <div class="cf-modal-footer">
            <button type="button" class="btn btn-outline" id="cancelAddFieldModal">Cancel</button>
            <button type="button" class="btn btn-primary" id="submitAddField">
                <i class="fa-solid fa-plus"></i> Add Field
            </button>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    const list = document.getElementById('sortableList');
    const previewList = document.getElementById('previewList');
    const visibleCount = document.getElementById('visibleCount');
    const statVisible = document.getElementById('statVisible');
    const statHidden = document.getElementById('statHidden');
    const fieldsPayload = document.getElementById('fieldsPayload');
    const saveForm = document.getElementById('saveForm');

    // Initialize SortableJS — skip section header rows
    Sortable.create(list, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        filter: '[data-section-header]',
        onEnd: () => updatePreview(),
    });

    // Toggle visible/hidden state on row when checkbox changes
    list.addEventListener('change', function (e) {
        if (!e.target.classList.contains('field-toggle')) return;
        const row = e.target.closest('.field-row');
        if (!row) return;
        if (e.target.checked) {
            row.classList.remove('hidden-field');
        } else {
            row.classList.add('hidden-field');
        }
        updatePreview();
    });

    // Show All / Hide All buttons
    document.getElementById('selectAll').addEventListener('click', function () {
        list.querySelectorAll('.field-toggle').forEach(cb => {
            cb.checked = true;
            cb.closest('.field-row').classList.remove('hidden-field');
        });
        updatePreview();
    });

    document.getElementById('deselectAll').addEventListener('click', function () {
        list.querySelectorAll('.field-toggle').forEach(cb => {
            // Don't hide required fields
            const row = cb.closest('.field-row');
            if (row && row.dataset.required === '1') return;
            cb.checked = false;
            row.classList.add('hidden-field');
        });
        updatePreview();
    });

    function buildFieldsArray() {
        const rows = list.querySelectorAll('.field-row[data-name]');
        return Array.from(rows).map(row => ({
            name:     row.dataset.name,
            label:    row.dataset.label,
            visible:  row.querySelector('.field-toggle').checked,
            required: row.dataset.required === '1',
            section:  row.dataset.section || '',
            custom:   row.dataset.custom === '1',
        }));
    }

    function updatePreview() {
        const fieldArr = buildFieldsArray();
        const visible  = fieldArr.filter(f => f.visible);

        // Rebuild preview list
        previewList.innerHTML = '';
        if (visible.length === 0) {
            previewList.innerHTML = `
                <div class="preview-empty">
                    <i class="fa-solid fa-eye-slash" style="font-size:20px;margin-bottom:8px;display:block;"></i>
                    No fields visible
                </div>`;
        } else {
            visible.forEach(f => {
                const el = document.createElement('div');
                el.className = 'preview-item';
                el.dataset.preview = f.name;
                el.innerHTML = `<i class="fa-solid fa-check-circle"></i><span>${escHtml(f.label)}</span>`;
                previewList.appendChild(el);
            });
        }

        // Update counts
        const hiddenCount = fieldArr.length - visible.length;
        visibleCount.textContent = visible.length;
        statVisible.textContent  = visible.length;
        statHidden.textContent   = hiddenCount;
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // On form submit, serialize fields to JSON hidden input
    saveForm.addEventListener('submit', function (e) {
        fieldsPayload.value = JSON.stringify(buildFieldsArray());
    });

    // Initial preview sync
    updatePreview();

    // ── Delete custom fields ──────────────────────────────────────────────
    list.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-custom');
        if (!btn) return;
        const row = btn.closest('.field-row');
        const id  = btn.dataset.customId;
        const lbl = row ? row.dataset.label : 'this field';

        if (!confirm(`Remove the custom field "${lbl}"? This will hide it from the form. Existing data will be preserved.`)) return;

        fetch(`/custom-fields/${id}`, {
            method:  'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept':       'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                row.remove();
                updatePreview();
                showToast('Custom field removed.', 'success');
            } else {
                showToast('Could not remove field.', 'error');
            }
        })
        .catch(() => showToast('Network error.', 'error'));
    });

    @if($configType === 'form')
    // ── Add new field modal ───────────────────────────────────────────────
    const modal          = document.getElementById('addFieldModal');
    const openBtn        = document.getElementById('openAddFieldModal');
    const closeBtn       = document.getElementById('closeAddFieldModal');
    const cancelBtn      = document.getElementById('cancelAddFieldModal');
    const submitBtn      = document.getElementById('submitAddField');
    const cfLabel        = document.getElementById('cfLabel');
    const cfFieldType    = document.getElementById('cfFieldType');
    const cfOptionsSection = document.getElementById('cfOptionsSection');
    const cfOptionsList  = document.getElementById('cfOptionsList');
    const cfAddOption    = document.getElementById('cfAddOption');
    const cfIsRequired   = document.getElementById('cfIsRequired');

    function openModal() {
        cfLabel.value      = '';
        cfFieldType.value  = 'text';
        cfIsRequired.checked = false;
        cfOptionsSection.style.display = 'none';
        cfOptionsList.innerHTML = `
            <div class="cf-option-row">
                <input type="text" placeholder="Option 1" class="cf-option-input">
                <button type="button" class="cf-option-remove" title="Remove"><i class="fa-solid fa-minus"></i></button>
            </div>`;
        modal.classList.add('active');
        cfLabel.focus();
    }
    function closeModal() {
        modal.classList.remove('active');
    }

    openBtn && openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    // Show/hide options section based on field type
    cfFieldType.addEventListener('change', function () {
        cfOptionsSection.style.display = this.value === 'select' ? 'block' : 'none';
    });

    // Add/remove options
    cfAddOption.addEventListener('click', function () {
        const count = cfOptionsList.querySelectorAll('.cf-option-row').length + 1;
        const div = document.createElement('div');
        div.className = 'cf-option-row';
        div.innerHTML = `
            <input type="text" placeholder="Option ${count}" class="cf-option-input">
            <button type="button" class="cf-option-remove" title="Remove"><i class="fa-solid fa-minus"></i></button>`;
        cfOptionsList.appendChild(div);
        div.querySelector('input').focus();
    });

    cfOptionsList.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.cf-option-remove');
        if (!removeBtn) return;
        const rows = cfOptionsList.querySelectorAll('.cf-option-row');
        if (rows.length <= 1) return; // keep at least one
        removeBtn.closest('.cf-option-row').remove();
    });

    // Submit
    submitBtn.addEventListener('click', function () {
        const label     = cfLabel.value.trim();
        const fieldType = cfFieldType.value;
        const required  = cfIsRequired.checked;

        if (!label) {
            cfLabel.style.borderColor = 'var(--danger)';
            cfLabel.focus();
            return;
        }
        cfLabel.style.borderColor = '';

        let options = null;
        if (fieldType === 'select') {
            options = Array.from(cfOptionsList.querySelectorAll('.cf-option-input'))
                          .map(i => i.value.trim())
                          .filter(v => v.length > 0);
            if (options.length === 0) {
                showToast('Please add at least one option.', 'error');
                return;
            }
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding…';

        fetch('{{ route('custom-fields.store') }}', {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept':        'application/json',
            },
            body: JSON.stringify({
                form_type:   '{{ $formType }}',
                label:       label,
                field_type:  fieldType,
                options:     options,
                is_required: required,
            }),
        })
        .then(r => r.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Add Field';

            if (data.success) {
                closeModal();
                addCustomFieldRow(data.id, data.name, label, required);
                showToast(`Custom field "${label}" added successfully.`, 'success');
            } else {
                showToast('Failed to add field.', 'error');
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Add Field';
            showToast('Network error.', 'error');
        });
    });

    function addCustomFieldRow(id, name, label, required) {
        // Find or create the "Custom Fields" section header
        let sectionHeader = Array.from(list.querySelectorAll('.section-header-row'))
            .find(el => el.textContent.trim().startsWith('Custom Fields'));

        if (!sectionHeader) {
            sectionHeader = document.createElement('div');
            sectionHeader.className = 'section-header-row';
            sectionHeader.dataset.sectionHeader = '1';
            sectionHeader.innerHTML = `
                <i class="fa-solid fa-puzzle-piece" style="font-size:9px;"></i>
                Custom Fields
                <span style="margin-left:auto;font-size:9px;font-weight:500;opacity:0.7;">1 fields</span>`;
            list.appendChild(sectionHeader);
        } else {
            // Update count
            const countSpan = sectionHeader.querySelector('span');
            if (countSpan) {
                const current = parseInt(countSpan.textContent) || 0;
                countSpan.textContent = (current + 1) + ' fields';
            }
        }

        const row = document.createElement('div');
        row.className = 'field-row';
        row.dataset.name     = name;
        row.dataset.label    = label;
        row.dataset.required = required ? '1' : '0';
        row.dataset.section  = 'Custom Fields';
        row.dataset.custom   = '1';
        row.dataset.customId = id;

        row.innerHTML = `
            <span class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></span>
            <label class="toggle-wrap" title="Visible — click to hide">
                <input type="checkbox" class="field-toggle" data-name="${escHtml(name)}" checked>
                <span class="toggle-slider"></span>
            </label>
            <div class="field-label-col">
                <div class="field-label-text">${escHtml(label)}</div>
                <span class="field-name-tag">${escHtml(name)}</span>
            </div>
            <span class="field-section-tag">Custom Fields</span>
            <span class="badge-custom"><i class="fa-solid fa-puzzle-piece" style="font-size:8px;"></i> Custom</span>
            ${required ? '<span class="badge-required"><i class="fa-solid fa-asterisk" style="font-size:8px;"></i> Required</span>' : ''}
            <button type="button" class="btn-delete-custom" data-custom-id="${id}" title="Remove this custom field">
                <i class="fa-solid fa-trash"></i>
            </button>`;

        list.appendChild(row);
        updatePreview();
    }
    @endif

    // ── Toast ─────────────────────────────────────────────────────────────
    function showToast(msg, type) {
        const existing = document.querySelectorAll('.cf-toast');
        existing.forEach(t => t.remove());

        const toast = document.createElement('div');
        toast.className = `cf-toast ${type}`;
        toast.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'circle-exclamation'}"></i> ${escHtml(msg)}`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }
})();
</script>
@endpush
