@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('topbar-title', 'Roles & Permissions')

@push('styles')
<style>
    /* Group divider inside the matrix — a labelled band, not a card of its
       own, so the five groups read as sections of one table rather than five
       tables stacked. */
    .rl-group th {
        padding: 14px 18px 7px;
        background: var(--page-bg-alt);
        font-family: var(--font-display);
        font-size: var(--fs-xs);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
        text-align: left;
        position: static;   /* not part of the sticky header row */
    }
    /* Verdict mark. Shape and colour both carry the meaning, and each mark
       ships its own screen-reader text — a matrix that reads as three columns
       of identical dots to a screen reader is not a matrix. */
    .rl-verdict { font-size: var(--fs-md); line-height: 1; }
    .rl-verdict.is-full    { color: var(--tone-success-fg); }
    .rl-verdict.is-partial { color: var(--tone-warning-fg); }
    .rl-verdict.is-none    { color: var(--text-faint); }
    /* Four verdict columns fit a desktop table beside the capability and the
       gate that enforces it; a fifth would start the .table-wrap scrolling,
       which is the correct failure and not a reason to shrink the type. */
    .rl-col { width: 104px; text-align: center; }
    /* Legend beside the table's title. */
    .rl-legend {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        font-size: var(--fs-xs);
        color: var(--text-muted);
    }
    .rl-legend span { display: inline-flex; align-items: center; gap: 5px; }
    /* The limit itself, under the capability it applies to. */
    .rl-limit { color: var(--tone-warning-fg); }
    @media (max-width: 768px) {
        /* In card mode every cell is on its own row, so a fixed column width
           and centring would put the mark under its label instead of beside
           it. */
        .rl-col { width: auto; text-align: left; }
    }
</style>
@endpush

@section('content')

@section('page-title', 'Roles & Permissions')
@section('page-subtitle', 'Every role in this system, and exactly what each one is allowed to reach')

@include('partials.access-tabs', ['active' => 'roles'])

{{-- ── The roles ─────────────────────────────────────────────────────────── --}}
{{-- Column count follows the catalog: three roles sat comfortably at is-3,
     and a fourth would have stretched them across the row. --}}
<div class="card-grid is-{{ count($roles) > 3 ? 4 : 3 }}">
    @foreach($roles as $role)
        @php
            $t = $tally[$role['key']];
            $pct = $t['total'] > 0 ? round($t['granted'] / $t['total'] * 100) : 0;
            $accounts = (int) ($counts[$role['key']] ?? 0);
        @endphp
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon {{ $role['tone'] === 'accent' ? '' : 'is-'.$role['tone'] }}">
                    <i class="fa-solid {{ $role['icon'] }}"></i>
                </div>
                <div class="card-header-text">
                    <div class="card-title">{{ $role['label'] }}</div>
                    <div class="card-subtitle">{{ $role['scope'] }}</div>
                </div>
                <div class="card-header-actions">
                    <span class="status-badge {{ $role['key'] }}">
                        {{ $accounts }} {{ Str::plural('account', $accounts) }}
                    </span>
                </div>
            </div>
            <div class="card-body is-stack">
                <p class="section-note" style="margin-top:0">{{ $role['summary'] }}</p>
                <div class="meter-row" style="margin-top:auto">
                    <div class="meter-head">
                        <span class="meter-label">Capabilities granted</span>
                        @if($t['partial'] > 0)
                            <span class="rl-limit">+{{ $t['partial'] }} limited</span>
                        @endif
                        <span class="meter-val">{{ $t['granted'] }} / {{ $t['total'] }}</span>
                    </div>
                    <div class="meter-track">
                        <div class="meter-fill" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ── The matrix ────────────────────────────────────────────────────────── --}}
<div class="table-card">
    <div class="card-header">
        <div class="card-header-icon is-soft"><i class="fa-solid fa-table-list"></i></div>
        <div class="card-header-text">
            <div class="card-title">Capability matrix</div>
            <div class="card-subtitle">Every gate in the application, and the code that enforces it</div>
        </div>
        <div class="card-header-actions">
            <div class="rl-legend">
                <span><i class="fa-solid fa-circle-check rl-verdict is-full"></i> Granted</span>
                <span><i class="fa-solid fa-circle-half-stroke rl-verdict is-partial"></i> Limited</span>
                <span><i class="fa-solid fa-minus rl-verdict is-none"></i> Refused</span>
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Capability</th>
                    @foreach($roles as $role)
                        <th class="rl-col">{{ $role['label'] }}</th>
                    @endforeach
                    <th>Enforced by</th>
                </tr>
            </thead>
            <tbody>
                @foreach($capabilities as $group => $rows)
                    <tr class="rl-group">
                        <th colspan="{{ count($roleKeys) + 2 }}" scope="colgroup">{{ $group }}</th>
                    </tr>
                    @foreach($rows as $row)
                        <tr>
                            <td data-label="Capability">
                                <div class="cell-title">{{ $row['label'] }}</div>
                                <div class="cell-sub">{{ $row['detail'] }}</div>
                                @foreach($row['notes'] ?? [] as $noteRole => $note)
                                    <div class="cell-sub rl-limit">
                                        <i class="fa-solid fa-circle-half-stroke"></i>
                                        <strong>{{ ucfirst($noteRole) }}</strong> — {{ $note }}
                                    </div>
                                @endforeach
                            </td>
                            @foreach($roleKeys as $key)
                                @php
                                    $verdict = $row['verdicts'][$key] ?? \App\Support\RoleCatalog::NONE;
                                    $mark = match ($verdict) {
                                        \App\Support\RoleCatalog::FULL    => ['fa-circle-check',       'is-full',    'Granted'],
                                        \App\Support\RoleCatalog::PARTIAL => ['fa-circle-half-stroke', 'is-partial', 'Limited'],
                                        default                           => ['fa-minus',              'is-none',    'Refused'],
                                    };
                                @endphp
                                <td data-label="{{ ucfirst($key) }}" class="rl-col">
                                    <span class="rl-verdict {{ $mark[1] }}" title="{{ $mark[2] }}">
                                        <i class="fa-solid {{ $mark[0] }}"></i>
                                        <span class="sr-only">{{ $mark[2] }}</span>
                                    </span>
                                </td>
                            @endforeach
                            <td data-label="Enforced by" class="cell-muted wrap-anywhere">{{ $row['by'] }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info">
    <i class="fa-solid fa-circle-info"></i>
    <div>
        Roles in this system are defined in code, not in the database — there is no roles or
        permissions table to edit, so this page is a reference rather than a form. To change
        <strong>who holds a role</strong>, edit the account under
        <a href="{{ route('users.index') }}">Users</a>. To change <strong>what a role can
        reach</strong>, the gate named in the last column is the thing to change, and
        <code>App\Support\RoleCatalog</code> is what keeps this page in step with it.
    </div>
</div>

@endsection
