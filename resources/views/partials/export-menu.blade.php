{{-- Export → XLSX | PDF, for a page-header action.

     Every list export now serves both formats, and a single link can only ask
     for one — so the button opens a two-item menu instead of guessing. Reuses
     the top bar's [data-pop] machinery (see admin.blade.php), which gives
     click-away, Esc and focus-return with no JS of its own.

     The trigger is <x-export-button>, and the class is not a parameter: this
     partial used to take one so each page could pick its own emphasis, which
     is exactly how Export ended up green on three pages and outline on three
     others. One button, one style, everywhere.

     @param string $route   route name, taking {format?} as its first segment
     @param array  $params  query/route params to carry (the applied filters)
     @param string $id      unique element id — several menus can share a page
     @param string $sub     one line describing what the XLSX contains
     @param string $pdfSub  the same for the PDF, when it is not the usual
                            flat table (the dashboard's nests by property) --}}
@php
    $exportId     = $id ?? 'export-menu-' . \Illuminate\Support\Str::slug($route);
    $exportParams = collect($params ?? [])->filter(fn ($v) => $v !== null && $v !== '')->all();
    $hasFilters   = ! empty(array_diff_key($exportParams, array_flip(['batch'])));
@endphp

<div class="shell-headmenu" data-pop>
    <x-export-button caret data-pop-toggle
                     aria-expanded="false" aria-controls="{{ $exportId }}" aria-haspopup="true" />

    <div class="shell-pop" id="{{ $exportId }}" hidden>
        {{-- Says whether the filters travel with the download. Exporting a
             filtered list and getting all 240 rows — or the reverse — is the
             mistake this line exists to prevent. --}}
        <div class="shell-pop-head">
            {{ $hasFilters ? 'Export these filtered results' : 'Export all records' }}
        </div>
        <a class="shell-menu-item has-sub" href="{{ route($route, array_merge($exportParams, ['format' => 'xlsx'])) }}">
            <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
            <span>
                Excel workbook
                <span class="shell-menu-item-sub">{{ $sub ?? 'Every field, import-ready' }}</span>
            </span>
        </a>
        <a class="shell-menu-item has-sub" href="{{ route($route, array_merge($exportParams, ['format' => 'pdf'])) }}">
            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
            <span>
                PDF document
                <span class="shell-menu-item-sub">{{ $pdfSub ?? 'Print-ready, key columns' }}</span>
            </span>
        </a>
    </div>
</div>
