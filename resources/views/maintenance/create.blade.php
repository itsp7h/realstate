@extends('layouts.admin')

@section('title', $record ? 'Edit Maintenance Request' : 'New Maintenance Request')
@section('topbar-title', $record ? 'Edit Maintenance Request' : 'New Maintenance Request')

@section('page-breadcrumb')
    <a href="{{ route('maintenance.index') }}">Maintenance</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>{{ $record ? $record->job_order : 'New Request' }}</span>
@endsection
@section('page-title')
    {{ $record ? 'Edit Request' : 'New Maintenance Request' }}
@endsection
@section('page-actions')
    <a href="{{ route('maintenance.index') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
@endsection

@push('styles')
<style>
/* ── SECTION CARDS ───────────────────────────────────────── */

/* ── FORM GRID ───────────────────────────────────────────── */

@media (max-width: 900px) {

}
@media (max-width: 600px) {

}
.span-2 { grid-column: span 2; }
.span-3 { grid-column: span 3; }

/* ── JOB LINES TABLE ─────────────────────────────────────── */
.job-lines-table { width: 100%; border-collapse: collapse; }
.remove-line-btn {
    position: relative; background: none; border: none; color: var(--tone-danger-fg); cursor: pointer;
    font-size: var(--fs-base); padding: 6px; border-radius: 6px; transition: background 0.15s;
}
.remove-line-btn:hover { background: var(--tone-danger-bg); }
@media (max-width: 768px) {
    /* Invisible 44px hit area — the visible icon stays compact in the table row. */
    .remove-line-btn::after {
        content: ''; position: absolute; inset: 50% auto auto 50%;
        width: var(--h-control-lg); height: var(--h-control-lg);
        transform: translate(-50%, -50%);
    }
}

/* ── QUOTATION INLINE ATTACHMENT ─────────────────────────── */
.quot-input-wrap { position: relative; }
.quot-input-wrap input[type="number"] { padding-right: 36px; width: 100%; }
.quot-clip-btn {
    position: absolute; right: 0; top: 0; bottom: 0;
    width: 34px; display: flex; align-items: center; justify-content: center;
    background: none; border: none; border-left: 1.5px solid var(--input-border);
    border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
    color: var(--text-muted); cursor: pointer; font-size: var(--fs-sm);
    transition: color 0.15s, background 0.15s;
}
.quot-clip-btn:hover { color: var(--accent); background: var(--accent-dim); }
.quot-clip-btn.has-file { color: var(--accent); background: var(--accent-dim); }
.quot-existing-file {
    display: flex; align-items: center; gap: 6px; margin-top: 5px;
    padding: 3px 8px 3px 6px; background: var(--accent-dim);
    border-radius: var(--radius-pill); font-size: var(--fs-xs); font-weight: 600; max-width: 100%;
}
.quot-existing-file a { color: var(--accent); text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0; }
.quot-existing-file a:hover { text-decoration: underline; }
.quot-remove-label { display: flex; align-items: center; gap: 4px; color: var(--tone-danger-fg); cursor: pointer; white-space: nowrap; flex-shrink: 0; }
.quot-remove-label input { accent-color: var(--tone-danger-fg); }

/* ── READONLY NOTE ───────────────────────────────────────── */
.section-note {
    font-size: var(--fs-xs); color: var(--text-muted);
    background: var(--page-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius-sm); padding: 8px 12px; margin-bottom: 14px;
    display: flex; gap: 6px; align-items: center;
}
</style>
@endpush

@section('content')


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
      action="{{ $record ? route('maintenance.update', $record) : route('maintenance.store') }}"
      enctype="multipart/form-data">
    @csrf
    @if($record) @method('PUT') @endif

    {{-- ── SECTION 1: REQUEST HEADER ─────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fa-solid fa-clipboard"></i></div>
            <div>
                <div class="card-title">Request Details</div>
            </div>
            @if($record)
            <div style="margin-left:auto">
                <select name="status" style="padding:6px 12px;font-size:12px;border:1.5px solid var(--input-border);border-radius:var(--radius-sm);background:var(--input-bg);color:var(--text-primary);outline:none;cursor:pointer">
                    @foreach(['waiting_supervisor','waiting_approval','approved','in_progress','completed','cancelled'] as $s)
                    <option value="{{ $s }}" {{ old('status', $record->status) === $s ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_',' ',$s)) }}
                    </option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="status" value="waiting_supervisor">
            @endif
        </div>
        <div class="card-body">
            <div class="form-grid cols-3">
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
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fa-solid fa-list-check"></i></div>
            <div class="card-title">Job Lines</div>
            <button type="button" class="btn btn-outline btn-sm" style="margin-left:auto" onclick="addJobLine()">
                <i class="fa-solid fa-plus"></i> Add Line
            </button>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="is-editable">
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
                            <td data-label="Location"><input type="text" name="job_lines[{{ $i }}][location]" value="{{ $line['location'] ?? '' }}" placeholder="e.g. Kitchen, Bathroom"></td>
                            <td data-label="Description of Work"><textarea name="job_lines[{{ $i }}][description]" placeholder="Describe the issue or work needed">{{ $line['description'] ?? '' }}</textarea></td>
                            <td data-label="Supervisor Comment"><textarea name="job_lines[{{ $i }}][supervisor_comment]" placeholder="Supervisor notes">{{ $line['supervisor_comment'] ?? '' }}</textarea></td>
                            <td style="text-align:center">
                                <button type="button" class="remove-line-btn" onclick="removeJobLine(this)" title="Remove" aria-label="Remove job line">
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
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fa-solid fa-user-tie"></i></div>
            <div class="card-title">Supervisor</div>
        </div>
        <div class="card-body">
            <div class="form-grid cols-2">
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
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fa-solid fa-wrench"></i></div>
            <div>
                <div class="card-title">Maintenance Use Only</div>
            </div>
        </div>
        <div class="card-body">
            <div class="form-group" style="margin-bottom:16px">
                <label>Job Assessment</label>
                <textarea name="job_assessment" rows="3" placeholder="Assessment notes and findings…">{{ old('job_assessment', $record?->job_assessment) }}</textarea>
            </div>
            <div class="form-grid cols-3" style="margin-bottom:16px">
                @foreach([1,2,3] as $n)
                @php $fileField = "quotation_{$n}_file"; $existingFile = $record?->$fileField; @endphp
                <div class="form-group">
                    <label>Quotation {{ $n }} (BHD)</label>
                    <div class="quot-input-wrap">
                        <input type="number" name="quotation_{{ $n }}" value="{{ old('quotation_'.$n, $record?->{'quotation_'.$n}) }}" step="0.001" min="0" placeholder="0.000">
                        <label for="quot_file_{{ $n }}" class="quot-clip-btn {{ $existingFile ? 'has-file' : '' }}" title="{{ $existingFile ? 'Replace attachment' : 'Attach file' }}">
                            <i class="fa-solid fa-paperclip"></i>
                        </label>
                        <input type="file" id="quot_file_{{ $n }}" name="{{ $fileField }}"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                               class="quot-file-input" data-index="{{ $n }}" style="display:none">
                    </div>
                    @if($existingFile)
                    <div class="quot-existing-file" id="quot_existing_{{ $n }}">
                        <i class="fa-solid fa-paperclip" style="flex-shrink:0"></i>
                        <a href="{{ Storage::url($existingFile) }}" target="_blank" title="{{ basename($existingFile) }}">{{ basename($existingFile) }}</a>
                        <label class="quot-remove-label">
                            <input type="checkbox" name="remove_{{ $fileField }}" value="1" style="margin:0">
                            <i class="fa-solid fa-xmark" style="font-size:10px"></i>
                        </label>
                    </div>
                    @endif
                    <div class="file-chip" id="quot_pill_{{ $n }}">
                        <i class="fa-solid fa-paperclip" style="flex-shrink:0;font-size:10px"></i>
                        <span id="quot_fname_{{ $n }}"></span>
                        <button type="button" data-index="{{ $n }}" class="quot-pill-clear" title="Remove">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    @error($fileField)
                    <div style="font-size:12px;color:var(--tone-danger-fg);margin-top:4px"><i class="fa-solid fa-triangle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>
                @endforeach
            </div>
            <div class="form-group">
                <label>Maintenance Remarks</label>
                <textarea name="maintenance_remarks" rows="3" placeholder="Additional remarks…">{{ old('maintenance_remarks', $record?->maintenance_remarks) }}</textarea>
            </div>
        </div>
    </div>

    {{-- ── SECTION 5: APPROVAL ──────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fa-solid fa-signature"></i></div>
            <div class="card-title">Approval</div>
        </div>
        <div class="card-body">
            <div class="form-grid cols-2">
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
            <button type="button" class="remove-line-btn" onclick="removeJobLine(this)" title="Remove" aria-label="Remove job line">
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
        row.querySelectorAll('input, textarea').forEach(el => el.value = '');
    }
}

// Quotation inline file inputs
document.querySelectorAll('.quot-file-input').forEach(input => {
    const n    = input.dataset.index;
    const clip = input.previousElementSibling;
    const pill = document.getElementById('quot_pill_' + n);
    const name = document.getElementById('quot_fname_' + n);

    input.addEventListener('change', () => {
        if (input.files.length) {
            const f    = input.files[0];
            const size = f.size < 1024 * 1024
                ? (f.size / 1024).toFixed(1) + ' KB'
                : (f.size / 1024 / 1024).toFixed(1) + ' MB';
            name.textContent = f.name + ' (' + size + ')';
            pill.classList.add('show');
            clip.classList.add('has-file');
        }
    });
});

document.querySelectorAll('.quot-pill-clear').forEach(btn => {
    btn.addEventListener('click', () => {
        const n     = btn.dataset.index;
        const input = document.getElementById('quot_file_' + n);
        const pill  = document.getElementById('quot_pill_' + n);
        const clip  = input.previousElementSibling;
        input.value = '';
        pill.classList.remove('show');
        clip.classList.remove('has-file');
    });
});
</script>
@endpush
