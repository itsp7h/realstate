{{-- Help panel — the contents of the "?" popover in the top bar.
     Included twice: once in the desktop shell top bar and once in the ≤768px
     legacy top bar, which is a separate layer (the shell is display:none
     there), so each instance needs its own id.

     $id         — element id, referenced by the trigger's aria-controls
     $shortcuts  — show the keyboard block. False on mobile: there is no
                   keyboard and the command palette is not rendered below
                   769px, so listing ⌘K there would be a lie.

     Nothing in here is invented: every row is a shortcut that the layout's
     own keydown handlers implement, or a link to a route that exists. The
     two role-gated links are hidden rather than shown-and-403'd. --}}
@php($shortcuts = $shortcuts ?? true)

@php($heading = $shortcuts ? 'Help & shortcuts' : 'Help')

<div class="shell-pop shell-helppop" id="{{ $id }}" hidden role="dialog" aria-label="{{ $heading }}">
    <div class="shell-pop-head">
        <span>{{ $heading }}</span>
    </div>

    @if($shortcuts)
        <div class="shell-keys">
            <div class="shell-keys-label">Keyboard</div>
            <div class="shell-key-row">
                <span>Search anything</span>
                <kbd class="shell-kbd">⌘K</kbd>
            </div>
            <div class="shell-key-row">
                <span>Close a panel or dialog</span>
                <kbd class="shell-kbd">Esc</kbd>
            </div>
        </div>
    @endif

    <p class="shell-helptip">
        <i class="fa-regular fa-lightbulb" aria-hidden="true"></i>
        <span>{{ $shortcuts ? 'Click' : 'Tap' }} any row in a list to open that record — the edit and delete buttons still work on their own.</span>
    </p>

    <a class="shell-menu-item" href="{{ route('dashboard') }}">
        <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
        Start from the dashboard
    </a>

    @if(auth()->user()?->canViewReports())
        <a class="shell-menu-item" href="{{ route('reports.index') }}">
            <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
            Reports &amp; statements
        </a>
    @endif

    @if(auth()->user()?->isAdmin())
        <a class="shell-menu-item" href="{{ route('roles.index') }}">
            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
            Who can see what
        </a>
    @endif
</div>
