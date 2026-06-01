@extends('layouts.admin')

@section('title', $record ? 'Edit Maintenance Request' : 'New Maintenance Request')
@section('topbar-title', $record ? 'Edit Maintenance Request' : 'New Maintenance Request')

@push('styles')
<style>
/* ── SECTION CARDS ───────────────────────────────────────── */
.maint-section {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 20px;
}
.maint-section-header {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--card-border);
    border-left: 3px solid var(--accent);
    background: linear-gradient(90deg, var(--accent-dim) 0%, transparent 60%);
}
.maint-section-icon {
    width: 34px; height: 34px; border-radius: var(--radius-sm);
    background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;
}
.maint-section-title {
    font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 800;
    color: var(--text-primary); letter-spacing: 0.02em;
}
.maint-section-body { padding: 20px; }

/* ── FORM GRID ───────────────────────────────────────────── */
.form-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.form-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
@media (max-width: 900px) {
    .form-grid-3 { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .form-grid-3, .form-grid-2 { grid-template-columns: 1fr; }
}
.span-2 { grid-column: span 2; }
.span-3 { grid-column: span 3; }

/* ── JOB LINES TABLE ─────────────────────────────────────── */
.job-lines-table { width: 100%; border-collapse: collapse; }
.job-lines-table th {
    padding: 9px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; color: var(--text-muted); background: var(--page-bg);
    border-bottom: 1px solid var(--card-border); text-align: left;
}
.job-lines-table td { padding: 8px 6px; border-bottom: 1px solid #F1F5F9; vertical-align: top; }
.job-lines-table tr:last-child td { border-bottom: none; }
.job-lines-table input, .job-lines-table textarea {
    width: 100%; padding: 7px 10px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none;
    transition: border-color 0.18s; font-family: 'Plus Jakarta Sans', sans-serif;
}
.job-lines-table input:focus, .job-lines-table textarea:focus { border-color: var(--accent); }
.job-lines-table textarea { resize: vertical; min-height: 60px; }
.remove-line-btn {
    background: none; border: none; color: #DC2626; cursor: pointer;
    font-size: 14px; padding: 6px; border-radius: 6px; transition: background 0.15s;
}
.remove-line-btn:hover { background: #FEF2F2; }

/* ── READONLY NOTE ───────────────────────────────────────── */
.section-note {
    font-size: 11px; color: var(--text-muted);
    background: var(--page-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius-sm); padding: 8px 12px; margin-bottom: 14px;
    display: flex; gap: 6px; align-items: center;
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ route('maintenance.index') }}">Maintenance</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>{{ $record ? $record->job_order : 'New Request' }}</span>
        </div>
        <h1 class="page-header-title">{{ $record ? 'Edit Request' : 'New Maintenance Request' }}</h1>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('maintenance.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:20px">
    <i class="fa-solid fa-circle-exclamation"></i>
    <div><strong>Please fix the following errors:</strong>
        <ul style="margin:6px 0 0 16px;font-size:13px">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST"
      action="{{ $record ? route('maintenance.update', $record) : route('maintenance.store') }}">
    @csrf
    @if($record) @method('PUT') @endif

    {{-- ── SECTION 1: REQUEST HEADER ─────────────────────── --}}
    <div class="maint-section">
        <div class="maint-section-header">
            <div class="maint-section-icon"><i class="fa-solid fa-clipboard"></i></div>
            <div>
                <div class="maint-section-title">Request Details</div>
            </div>
            @if($record)
            <div style="margin-left:auto">
                <select name="status" style="padding:6px 12px;font-size:12px;border:1.5px solid var(--input-border);border-radius:var(--radius-sm);background:var(--input-bg);color:var(--text-primary);outline:none;cursor:pointer">
                    @foreach(['open','in_progress','completed','cancelled'] as $s)
                    <option value="{{ $s }}" {{ old('status', $record->status) === $s ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_',' ',$s)) }}
                    </option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="status" value="open">
            @endif
        </div>
        <div class="maint-section-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label>Date <span class="required">*</span></label>
                    <input type="date" name="date" value="{{ old('date', $record?->date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" required class="{{ $errors->has('date') ? 'error' : '' }}">
                    @error('date')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Job Order #</label>
                    <input type="text" name="job_order" value="{{ old('job_order', $record?->job_order) }}" placeholder="Auto-generated if blank" class="{{ $errors->has('job_order') ? 'error' : '' }}">
                    @error('job_order')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Request Date</label>
                    <input type="date" name="request_date" value="{{ old('request_date', $record?->request_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}">
                </div>
                <div class="form-group span-2">
                    <label>Property <span class="required">*</span></label>
                    <input type="text" name="property" value="{{ old('property', $record?->property) }}" placeholder="Property name or address" required class="{{ $errors->has('property') ? 'error' : '' }}">
                    @error('property')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Flat / Unit <span class="required">*</span></label>
                    <input type="text" name="flat" value="{{ old('flat', $record?->flat) }}" placeholder="e.g. 3B" required class="{{ $errors->has('flat') ? 'error' : '' }}">
                    @error('flat')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group span-2">
                    <label>Tenant <span class="required">*</span></label>
                    <input type="text" name="tenant" value="{{ old('tenant', $record?->tenant) }}" placeholder="Tenant full name" required class="{{ $errors->has('tenant') ? 'error' : '' }}">
                    @error('tenant')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Contact No. <span class="required">*</span></label>
                    <input type="text" name="contact_no" value="{{ old('contact_no', $record?->contact_no) }}" placeholder="+973 XXXX XXXX" required class="{{ $errors->has('contact_no') ? 'error' : '' }}">
                    @error('contact_no')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group span-2">
                    <label>Available Date &amp; Time <span class="required">*</span></label>
                    <input type="datetime-local" name="available_datetime"
                           value="{{ old('available_datetime', $record?->available_datetime?->format('Y-m-d\TH:i')) }}"
                           required class="{{ $errors->has('available_datetime') ? 'error' : '' }}">
                    @error('available_datetime')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Apartment Status <span class="required">*</span></label>
                    <select name="apartment_status" required class="{{ $errors->has('apartment_status') ? 'error' : '' }}">
                        <option value="">— Select —</option>
                        @foreach(['occupied','vacant','furnished','other'] as $s)
                        <option value="{{ $s }}" {{ old('apartment_status', $record?->apartment_status) === $s ? 'selected' : '' }}>
                            {{ ucfirst($s) }}
                        </option>
                        @endforeach
                    </select>
                    @error('apartment_status')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION 2: JOB LINES ──────────────────────────── --}}
    <div class="maint-section">
        <div class="maint-section-header">
            <div class="maint-section-icon"><i class="fa-solid fa-list-check"></i></div>
            <div class="maint-section-title">Job Lines</div>
            <button type="button" class="btn btn-outline btn-sm" style="margin-left:auto" onclick="addJobLine()">
                <i class="fa-solid fa-plus"></i> Add Line
            </button>
        </div>
        <div class="maint-section-body" style="padding:0">
            <div class="table-wrap">
                <table class="job-lines-table">
                    <thead>
                        <tr>
                            <th style="width:28%">Location</th>
                            <th style="width:36%">Description of Work</th>
                            <th style="width:28%">Supervisor Comment</th>
                            <th style="width:8%"></th>
                        </tr>
                    </thead>
                    <tbody id="jobLinesBody">
                        @php $jobLines = old('job_lines', $record?->job_lines ?? [['location'=>'','description'=>'','supervisor_comment'=>'']]); @endphp
                        @foreach($jobLines as $i => $line)
                        <tr class="job-line-row">
                            <td><input type="text" name="job_lines[{{ $i }}][location]" value="{{ $line['location'] ?? '' }}" placeholder="e.g. Kitchen, Bathroom"></td>
                            <td><textarea name="job_lines[{{ $i }}][description]" placeholder="Describe the issue or work needed">{{ $line['description'] ?? '' }}</textarea></td>
                            <td><textarea name="job_lines[{{ $i }}][supervisor_comment]" placeholder="Supervisor notes">{{ $line['supervisor_comment'] ?? '' }}</textarea></td>
                            <td style="text-align:center">
                                <button type="button" class="remove-line-btn" onclick="removeJobLine(this)" title="Remove">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── SECTION 3: SUPERVISOR ─────────────────────────── --}}
    <div class="maint-section">
        <div class="maint-section-header">
            <div class="maint-section-icon"><i class="fa-solid fa-user-tie"></i></div>
            <div class="maint-section-title">Supervisor</div>
        </div>
        <div class="maint-section-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Supervisor Name</label>
                    <input type="text" name="supervisor_name" value="{{ old('supervisor_name', $record?->supervisor_name) }}" placeholder="Full name">
                </div>
                <div class="form-group">
                    <label>Supervisor Date &amp; Time</label>
                    <input type="datetime-local" name="supervisor_datetime"
                           value="{{ old('supervisor_datetime', $record?->supervisor_datetime?->format('Y-m-d\TH:i')) }}">
                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION 4: MAINTENANCE USE ONLY ──────────────── --}}
    <div class="maint-section">
        <div class="maint-section-header">
            <div class="maint-section-icon"><i class="fa-solid fa-wrench"></i></div>
            <div>
                <div class="maint-section-title">Maintenance Use Only</div>
            </div>
        </div>
        <div class="maint-section-body">
            <div class="form-group" style="margin-bottom:16px">
                <label>Job Assessment</label>
                <textarea name="job_assessment" rows="3" placeholder="Assessment notes and findings…">{{ old('job_assessment', $record?->job_assessment) }}</textarea>
            </div>
            <div class="form-grid-3" style="margin-bottom:16px">
                <div class="form-group">
                    <label>Quotation 1 (BHD)</label>
                    <input type="number" name="quotation_1" value="{{ old('quotation_1', $record?->quotation_1) }}" step="0.001" min="0" placeholder="0.000">
                </div>
                <div class="form-group">
                    <label>Quotation 2 (BHD)</label>
                    <input type="number" name="quotation_2" value="{{ old('quotation_2', $record?->quotation_2) }}" step="0.001" min="0" placeholder="0.000">
                </div>
                <div class="form-group">
                    <label>Quotation 3 (BHD)</label>
                    <input type="number" name="quotation_3" value="{{ old('quotation_3', $record?->quotation_3) }}" step="0.001" min="0" placeholder="0.000">
                </div>
            </div>
            <div class="form-group">
                <label>Maintenance Remarks</label>
                <textarea name="maintenance_remarks" rows="3" placeholder="Additional remarks…">{{ old('maintenance_remarks', $record?->maintenance_remarks) }}</textarea>
            </div>
        </div>
    </div>

    {{-- ── SECTION 5: APPROVAL ──────────────────────────── --}}
    <div class="maint-section">
        <div class="maint-section-header">
            <div class="maint-section-icon"><i class="fa-solid fa-signature"></i></div>
            <div class="maint-section-title">Approval</div>
        </div>
        <div class="maint-section-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Approved by Supervisor</label>
                    <input type="text" name="approved_supervisor" value="{{ old('approved_supervisor', $record?->approved_supervisor) }}" placeholder="Supervisor name / signature">
                </div>
                <div class="form-group">
                    <label>Approved by Dept. Head</label>
                    <input type="text" name="approved_dept_head" value="{{ old('approved_dept_head', $record?->approved_dept_head) }}" placeholder="Dept. head name / signature">
                </div>
            </div>
        </div>
    </div>

    {{-- SUBMIT --}}
    <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px">
        <a href="{{ route('maintenance.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-{{ $record ? 'floppy-disk' : 'paper-plane' }}"></i>
            {{ $record ? 'Save Changes' : 'Submit Request' }}
        </button>
    </div>

</form>
@endsection

@push('scripts')
<script>
let lineIndex = {{ count(old('job_lines', $record?->job_lines ?? [['location'=>'','description'=>'','supervisor_comment'=>'']]) ) }};

function addJobLine() {
    const i = lineIndex++;
    const row = document.createElement('tr');
    row.className = 'job-line-row';
    row.innerHTML = `
        <td><input type="text" name="job_lines[${i}][location]" placeholder="e.g. Kitchen, Bathroom"></td>
        <td><textarea name="job_lines[${i}][description]" placeholder="Describe the issue or work needed"></textarea></td>
        <td><textarea name="job_lines[${i}][supervisor_comment]" placeholder="Supervisor notes"></textarea></td>
        <td style="text-align:center">
            <button type="button" class="remove-line-btn" onclick="removeJobLine(this)" title="Remove">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    `;
    document.getElementById('jobLinesBody').appendChild(row);
    row.querySelector('input').focus();
}

function removeJobLine(btn) {
    const row = btn.closest('tr');
    const tbody = document.getElementById('jobLinesBody');
    if (tbody.querySelectorAll('tr').length > 1) {
        row.remove();
    } else {
        // Clear the last row instead of removing it
        row.querySelectorAll('input, textarea').forEach(el => el.value = '');
    }
}
</script>
@endpush
