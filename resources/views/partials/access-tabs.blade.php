{{-- Accounts and Roles & Permissions are two views of one job: who holds a
     role, and what a role can do. They are separate routes because the sidebar
     carries an entry for each, and because a 15-row capability matrix inside a
     filtered, paginated user table would bury both. The tab bar is the seam
     that makes them read as one module either way.

     Real links rather than JS panels — each side is its own page, so the back
     button, a bookmark and a middle-click all behave.

     Pass $active as 'accounts' or 'roles'. --}}
<nav class="tab-bar" aria-label="Access management">
    <a href="{{ route('users.index') }}"
       class="tab-btn {{ $active === 'accounts' ? 'active' : '' }}"
       @if($active === 'accounts') aria-current="page" @endif>
        <i class="fa-solid fa-user-shield"></i> Accounts
    </a>
    <a href="{{ route('roles.index') }}"
       class="tab-btn {{ $active === 'roles' ? 'active' : '' }}"
       @if($active === 'roles') aria-current="page" @endif>
        <i class="fa-solid fa-user-lock"></i> Roles &amp; Permissions
    </a>
</nav>
