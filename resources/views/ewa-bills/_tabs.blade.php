<div class="tab-bar">
    <a href="{{ route('ewa-bills.index') }}" class="tab-btn{{ request()->routeIs('ewa-bills.index') ? ' active' : '' }}">
        <i class="fa-solid fa-file-invoice-dollar"></i> Bills
    </a>
    <a href="{{ route('ewa-bills.summary.create') }}" class="tab-btn{{ request()->routeIs('ewa-bills.summary.*') ? ' active' : '' }}">
        <i class="fa-solid fa-layer-group"></i> Summary Import
    </a>
</div>
