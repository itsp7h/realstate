@extends('layouts.admin')

@section('title', $record ? 'Edit Revenue' : 'New Revenue')
@section('topbar-title', 'Revenue')

@push('styles')
<style>

.form-label .required { color: var(--tone-danger-fg); margin-left: 2px; }
.form-control {
    padding: 9px 13px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none;
    transition: border-color 0.18s; width: 100%; box-sizing: border-box;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.form-control:focus { border-color: var(--accent); }
.form-control.is-invalid { border-color: var(--tone-danger-border); }
.form-control.is-invalid ~ .invalid-feedback { display: block; }
textarea.form-control { resize: vertical; min-height: 80px; }
.amount-wrap { position: relative; }
.amount-wrap input { padding-right: 52px; font-family: 'Outfit', sans-serif; font-weight: 700; }
.amount-wrap::after {
    content: 'BHD'; position: absolute; right: 13px; top: 50%;
    transform: translateY(-50%); font-size: 11px; font-weight: 700;
    color: var(--text-muted); pointer-events: none;
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">{{ $record ? 'Edit Revenue' : 'New Revenue' }}</h1>
        <p class="page-header-sub">Record income against a building or unit</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('revenues.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Revenue
        </a>
    </div>
</div>

<form method="POST" action="{{ $record ? route('revenues.update', $record) : route('revenues.store') }}">
    @csrf
    @if($record) @method('PUT') @endif

    <div class="card is-roomy">
        <div class="card-header">
            <div class="card-header-icon is-success"><i class="fa-solid fa-sack-dollar"></i></div>
            <div class="card-header-text">
                <h3 class="card-title">Revenue Details</h3>
            </div>
        </div>
        <div class="card-body">
            <div class="form-grid cols-2">
                <div class="form-group">
                    <label class="form-label">Building <span class="required">*</span></label>
                    <select name="building_id" id="buildingSelect" class="form-control {{ $errors->has('building_id') ? 'is-invalid' : '' }}" onchange="filterUnitsByBuilding()" required>
                        <option value="">Select a building…</option>
                        @foreach($buildings as $b)
                        <option value="{{ $b->id }}" {{ (string) old('building_id', $record?->building_id) === (string) $b->id ? 'selected' : '' }}>
                            {{ $b->property_name }} ({{ $b->property_code }})
                        </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">{{ $errors->first('building_id') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Unit <span style="font-size:10px;color:var(--text-muted);font-weight:400;text-transform:none">(optional — leave blank for whole building)</span></label>
                    <select name="unit_id" id="unitSelect" class="form-control {{ $errors->has('unit_id') ? 'is-invalid' : '' }}">
                        <option value="">Whole building</option>
                        @foreach($units as $u)
                        <option value="{{ $u->id }}" data-building="{{ $u->building_id }}"
                            {{ (string) old('unit_id', $record?->unit_id) === (string) $u->id ? 'selected' : '' }}>
                            {{ $u->unit_name }}
                        </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">{{ $errors->first('unit_id') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Category <span class="required">*</span></label>
                    <select name="category" class="form-control {{ $errors->has('category') ? 'is-invalid' : '' }}" required>
                        <option value="">Select a category…</option>
                        @foreach($categories as $val => $label)
                        <option value="{{ $val }}" {{ old('category', $record?->category) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">{{ $errors->first('category') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Revenue Date <span class="required">*</span></label>
                    <input type="date" name="revenue_date" max="{{ now()->format('Y-m-d') }}"
                           class="form-control {{ $errors->has('revenue_date') ? 'is-invalid' : '' }}"
                           value="{{ old('revenue_date', $record?->revenue_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                    <div class="invalid-feedback">{{ $errors->first('revenue_date') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <div class="amount-wrap">
                        <input type="number" name="amount" step="0.001" min="0.001"
                               class="form-control {{ $errors->has('amount') ? 'is-invalid' : '' }}"
                               value="{{ old('amount', $record?->amount) }}" required>
                    </div>
                    <div class="invalid-feedback">{{ $errors->first('amount') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Source <span style="font-size:10px;color:var(--text-muted);font-weight:400;text-transform:none">(optional)</span></label>
                    <input type="text" name="source_name" maxlength="255"
                           class="form-control {{ $errors->has('source_name') ? 'is-invalid' : '' }}"
                           value="{{ old('source_name', $record?->source_name) }}">
                    <div class="invalid-feedback">{{ $errors->first('source_name') }}</div>
                </div>
            </div>
            <div class="form-group" style="margin-top:18px">
                <label class="form-label">Description <span style="font-size:10px;color:var(--text-muted);font-weight:400;text-transform:none">(optional)</span></label>
                <textarea name="description" class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}" rows="2" maxlength="500">{{ old('description', $record?->description) }}</textarea>
                <div class="invalid-feedback">{{ $errors->first('description') }}</div>
            </div>
        </div>
    </div>

    <div class="page-header-actions" style="justify-content:flex-end">
        <a href="{{ route('revenues.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-check"></i> {{ $record ? 'Save Changes' : 'Record Revenue' }}
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
function filterUnitsByBuilding() {
    const buildingId = document.getElementById('buildingSelect').value;
    const unitSelect  = document.getElementById('unitSelect');
    const currentValue = unitSelect.value;
    let stillValid = false;

    Array.from(unitSelect.options).forEach(opt => {
        if (!opt.dataset.building) { opt.hidden = false; return; }
        const matches = opt.dataset.building === buildingId;
        opt.hidden = !matches;
        if (matches && opt.value === currentValue) stillValid = true;
    });

    if (!stillValid) unitSelect.value = '';
}
document.addEventListener('DOMContentLoaded', filterUnitsByBuilding);
</script>
@endpush
