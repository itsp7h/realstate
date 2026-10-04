{{-- Shared by the board and the list, so one filter bar means one thing.
     The enclosing <form> carries the hidden `view` field, which is why the
     reset link below has to put it back. --}}
<div class="filter-group is-search">
    <label for="f_search">Search</label>
    <input type="search" id="f_search" name="search" value="{{ request('search') }}" placeholder="Search job order, property, tenant…">
</div>
<div class="filter-group">
    <label for="f_status">Status</label>
    <select id="f_status" name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        @foreach(['open' => 'Open','waiting_supervisor' => 'Pending Assessment','waiting_approval' => 'Pending Approval','approved' => 'Approved','in_progress' => 'In Progress','completed' => 'Completed','cancelled' => 'Cancelled'] as $val => $label)
        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="filter-group">
    <label for="f_date_from">From</label>
    <input type="date" id="f_date_from" name="date_from" value="{{ request('date_from') }}">
</div>
<div class="filter-group">
    <label for="f_date_to">To</label>
    <input type="date" id="f_date_to" name="date_to" value="{{ request('date_to') }}">
</div>
<div class="filter-actions">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    @if(request()->hasAny(['search','status','stage','date_from','date_to']))
    <a href="{{ route('maintenance.index', ['view' => $view]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
    @endif
</div>
