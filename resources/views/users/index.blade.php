@extends('layouts.admin')

@section('title', 'Users')
@section('topbar-title', 'Users')

@push('styles')
<style>
.filter-bar {
    display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap;
    padding: 16px 20px;
    background: var(--page-bg);
    border-bottom: 1px solid var(--card-border);
}
.filter-group { display: flex; flex-direction: column; gap: 5px; min-width: 170px; }
.filter-group label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
.filter-group input, .filter-group select {
    padding: 8px 12px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--card-bg); color: var(--text-primary);
    font-family: 'Plus Jakarta Sans', sans-serif;
    outline: none;
}
.filter-group input:focus, .filter-group select:focus { border-color: var(--accent); }
.filter-actions { margin-left: auto; }

.action-btns { display: flex; gap: 6px; }

.role-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
    padding: 3px 10px; border-radius: 20px;
}
.role-badge.admin       { background: var(--accent-dim); color: var(--accent); }
.role-badge.user        { background: #EFF6FF; color: var(--info); }
.role-badge.maintenance { background: var(--page-bg); color: var(--text-muted); border: 1px solid var(--card-border); }

.empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.empty-state i { font-size: 32px; display: block; margin-bottom: 10px; opacity: 0.3; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Users</h1>
        <p class="page-header-sub">Accounts that can sign in to this system, and what each one is allowed to do</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add User
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
@endif

<div class="card" style="overflow:hidden;">

    <form method="GET" action="{{ route('users.index') }}">
        <div class="filter-bar">
            <div class="filter-group" style="flex:1;">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email…">
            </div>
            <div class="filter-group">
                <label>Role</label>
                <select name="role" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
                    <option value="maintenance" {{ request('role') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
            </div>
            <div class="filter-actions" style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                @if(request()->hasAny(['search', 'role']))
                <a href="{{ route('users.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Clear</a>
                @endif
            </div>
        </div>
    </form>

    @if($users->isEmpty())
    <div class="empty-state"><i class="fa-solid fa-users"></i>No users match this filter.</div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr data-href="{{ route('users.edit', $user) }}" style="cursor:pointer">
                    <td style="font-weight:600">{{ $user->name }}</td>
                    <td style="color:var(--text-secondary)">{{ $user->email }}</td>
                    <td><span class="role-badge {{ $user->role }}">{{ $user->role_label }}</span></td>
                    <td style="font-size:12.5px;color:var(--text-muted)">{{ $user->created_at->format('d M Y') }}</td>
                    <td onclick="event.stopPropagation()">
                        <div class="action-btns" style="justify-content:flex-end;">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:16px 20px;">{{ $users->links() }}</div>
    @endif
</div>

@endsection
