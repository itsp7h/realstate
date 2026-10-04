{{-- A row's actions, behind one trigger. See app-core §4.1b.

     Pass $items as an array; each entry is one of:

       ['label' => 'View',  'icon' => 'fa-eye',   'url'    => route(...)]
       ['label' => 'PDF',   'icon' => 'fa-file-pdf', 'onclick' => "fn('x')"]
       ['label' => 'Delete','icon' => 'fa-trash', 'action' => route(...),
        'method' => 'DELETE', 'confirm' => 'Delete X?', 'tone' => 'danger']

     A 'sep' => true entry draws a divider. Falsy entries are skipped, so a
     caller can inline a condition without building the array in two passes.

     $label overrides the trigger's accessible name (default "Row actions"). --}}
@php
    $items = collect($items ?? [])->filter()->values();
    $menuId = 'rowmenu-'.\Illuminate\Support\Str::random(8);
@endphp

@if($items->isNotEmpty())
<div class="row-menu">
    <button type="button" class="btn btn-outline btn-sm btn-icon" data-rowmenu-toggle
            aria-haspopup="true" aria-expanded="false" aria-controls="{{ $menuId }}"
            title="{{ $label ?? 'Actions' }}" aria-label="{{ $label ?? 'Row actions' }}">
        <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
    </button>

    <div class="row-menu-panel" id="{{ $menuId }}" hidden>
        @foreach($items as $item)
            @if($item['sep'] ?? false)
                <div class="row-menu-sep" role="separator"></div>
            @elseif(! empty($item['action']))
                {{-- Destructive actions keep their form, CSRF token and method
                     spoofing: moving one into a menu must not make it a GET. --}}
                <form method="POST" action="{{ $item['action'] }}"
                      @if(! empty($item['confirm'])) onsubmit="return confirm('{{ addslashes($item['confirm']) }}')" @endif>
                    @csrf
                    @if(($item['method'] ?? 'POST') !== 'POST')
                        @method($item['method'])
                    @endif
                    <button type="submit" class="row-menu-item {{ ($item['tone'] ?? '') === 'danger' ? 'is-danger' : '' }}">
                        <i class="fa-solid {{ $item['icon'] ?? 'fa-circle' }}" aria-hidden="true"></i>
                        {{ $item['label'] }}
                    </button>
                </form>
            @elseif(! empty($item['onclick']))
                <button type="button" class="row-menu-item {{ ($item['tone'] ?? '') === 'danger' ? 'is-danger' : '' }}"
                        onclick="{{ $item['onclick'] }}">
                    <i class="fa-solid {{ $item['icon'] ?? 'fa-circle' }}" aria-hidden="true"></i>
                    {{ $item['label'] }}
                </button>
            @else
                <a class="row-menu-item {{ ($item['tone'] ?? '') === 'danger' ? 'is-danger' : '' }}"
                   href="{{ $item['url'] }}">
                    <i class="fa-solid {{ $item['icon'] ?? 'fa-circle' }}" aria-hidden="true"></i>
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</div>
@endif
