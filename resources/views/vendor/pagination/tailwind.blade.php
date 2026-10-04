{{--
    The application's one paginator.

    Overrides Laravel's default view, which emits raw Tailwind-utility <a>/<span>
    links this app doesn't load Tailwind for — so any page calling {{ $x->links() }}
    rendered unstyled, dark-mode-broken text links instead of .page-btn markup.

    MUST be named tailwind.blade.php, not default.blade.php: on this Laravel
    version, Illuminate\Pagination\AbstractPaginator::$defaultView is
    'pagination::tailwind', so that is the view name the framework actually
    resolves via php artisan's view finder to resources/views/vendor/pagination/.
    A file named default.blade.php here is silently never invoked — confirmed by
    fixing exactly that dead file on 2026-08-19 (14 pages call ->links() directly;
    all 14 were affected whenever a result set had more than one page).

    Markup contract: .pagination > a.page-btn (+ .active) | span.page-btn.is-disabled
--}}
@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="page-btn is-disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="page-btn" rel="prev" aria-label="@lang('pagination.previous')">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="page-btn is-disabled" aria-disabled="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page-btn active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="page-btn" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="page-btn" rel="next" aria-label="@lang('pagination.next')">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
        @else
            <span class="page-btn is-disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </span>
        @endif
    </nav>
@endif
