{{-- ═══════════════════════ THE BELL'S COUNT ═══════════════════════
     One badge, rendered from one number. Every bell in the app includes
     this rather than writing the `@if > 0` / `> 99 ? '99+'` pair itself —
     that pair was duplicated at three call sites and the dashboard's copy
     was counting something different from the other two.

     The number always comes from App\Services\AttentionFeed, shared onto
     the layout as $attentionCount by AppServiceProvider. Nothing else may
     compute it.

     Usage: @include('partials.bell-badge', ['count' => $attentionCount]) --}}
@php $bellCount = (int) ($count ?? 0); @endphp
@if($bellCount > 0)
    {{-- data-bell-count is how the layout's "pop on increment" helper finds
         this and reads the number: the class name is the CSS's business, and
         one test asserts that no other view so much as mentions it. --}}
    <span class="shell-bell-badge" data-bell-count="{{ $bellCount }}">{{ $bellCount > 99 ? '99+' : $bellCount }}</span>
@endif
