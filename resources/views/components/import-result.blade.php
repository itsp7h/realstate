@if(session()->has('import_counts'))
@php
    $counts = session('import_counts');
    $errs   = session('import_errors', []);
    $total  = array_sum($counts);
    $type   = $total > 0 && count($errs) > 0 ? 'partial' : ($total > 0 ? 'success' : 'error');
@endphp
<div class="import-banner {{ $type }}" style="margin-bottom:18px;">
    <div class="import-banner-icon">
        <i class="fa-solid {{ $type === 'error' ? 'fa-circle-xmark' : ($type === 'partial' ? 'fa-triangle-exclamation' : 'fa-circle-check') }}"></i>
    </div>
    <div class="import-banner-body">
        <div class="import-banner-title">
            @if($total === 0)
                Nothing was imported
            @else
                @php
                    $parts = [];
                    if ($counts['buildings'] > 0) $parts[] = $counts['buildings'] . ' building(s)';
                    if ($counts['floors'] > 0)    $parts[] = $counts['floors'] . ' floor(s)';
                    if ($counts['units'] > 0)     $parts[] = $counts['units'] . ' unit(s)';
                @endphp
                {{ implode(', ', $parts) }} imported successfully
            @endif
            @if(count($errs) > 0)
                — {{ count($errs) }} row(s) skipped
            @endif
        </div>
        @if(count($errs) > 0)
        <details class="import-errors-details">
            <summary>View {{ count($errs) }} error(s)</summary>
            <ul class="import-errors-list">
                @foreach($errs as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </details>
        @endif
    </div>
    <button class="import-banner-close" onclick="this.closest('.import-banner').remove()">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
@endif

@if(session('import_error'))
<div class="import-banner error" style="margin-bottom:18px;">
    <div class="import-banner-icon"><i class="fa-solid fa-circle-xmark"></i></div>
    <div class="import-banner-body">
        <div class="import-banner-title">{{ session('import_error') }}</div>
    </div>
    <button class="import-banner-close" onclick="this.closest('.import-banner').remove()">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
@endif
