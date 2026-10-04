{{-- Section header band for one entity — a property, a tenant, a batch.

     Gold rail on the left, rounded on the trailing side only, entity name left
     and its code in a navy badge right. page-break-inside/after: avoid on the
     band keeps it with the start of the table that follows.

     @param string      $name  entity name
     @param string|null $code  shown as the badge; omitted when absent
     @param string|null $meta  metadata line; "No metadata recorded" when empty --}}
@props(['name', 'code' => null, 'meta' => null])

<div class="pdf-section-head">
    <div class="pdf-section-row">
        <div class="pdf-section-name">{{ \Illuminate\Support\Str::limit($name, 60) }}</div>
        <div class="pdf-section-code-cell">
            @if($code)
                <span class="pdf-badge">{{ \Illuminate\Support\Str::limit($code, 16) }}</span>
            @endif
        </div>
    </div>
    <div class="pdf-section-meta">{{ $meta ? \Illuminate\Support\Str::limit($meta, 150) : 'No metadata recorded' }}</div>
</div>
