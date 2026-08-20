@extends('layouts.admin')

@section('title', 'Users')
@section('topbar-title', 'Users')

@push('styles')
<style>
</style>
@endpush

@section('content')

@section('page-title', 'Users')
@section('page-subtitle', 'Accounts that can sign in to this system, and what each one is allowed to do')
@section('page-actions')
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add User
    </a>
@endsection

@include('partials.access-tabs', ['active' => 'accounts'])

<div class="table-card">

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
                    <td data-label="Name" style="font-weight:600">{{ $user->name }}</td>
                    <td data-label="Email" style="color:var(--text-secondary)">{{ $user->email }}</td>
                    <td data-label="Role"><span class="status-badge {{ $user->role }}">{{ $user->role_label }}</span></td>
                    <td data-label="Joined" style="font-size:12.5px;color:var(--text-muted)">{{ $user->created_at->format('d M Y') }}</td>
                    <td data-label="Actions" onclick="event.stopPropagation()">
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
    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</strong>
            of <strong>{{ number_format($users->total()) }}</strong> users
        </div>
        {{ $users->links() }}
    </div>
    @endif
</div>

@endsection
