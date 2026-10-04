{{-- KPI strip: one bordered row of equal cells split by hairlines.

     @param array $items  [['label' => 'Units', 'value' => '65'], …]
                          A null/'' value renders "—" in grey rather than a
                          fabricated 0 — a total over no data is unknown. --}}
@props(['items' => []])

<div class="pdf-summary-wrap">
<table class="pdf-summary">
    <tr>
        @foreach($items as $item)
            @php $value = $item['value'] ?? null; @endphp
            <td>
                <div class="pdf-summary-label">{{ $item['label'] }}</div>
                <div class="pdf-summary-value @if($value === null || $value === '') is-empty @endif">
                    {{ $value === null || $value === '' ? '—' : $value }}
                </div>
            </td>
        @endforeach
    </tr>
</table>
</div>
