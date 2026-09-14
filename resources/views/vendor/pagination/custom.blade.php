{{-- Pagination view styled for the custom (non-Tailwind) app CSS. --}}
<nav class="pms-pagination" role="navigation" aria-label="Pagination"
    style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">

    @if ($paginator->onFirstPage())
        <span class="pms-page-btn" aria-disabled="true" style="opacity:.45; cursor:default;">
            &larr; Prev
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="pms-page-btn" rel="prev">
            &larr; Prev
        </a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="pms-page-btn" style="border:none; cursor:default;">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="pms-page-btn"
                        style="background:var(--primary-soft); color:var(--primary-dark); font-weight:600;">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="pms-page-btn">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="pms-page-btn" rel="next">
            Next &rarr;
        </a>
    @else
        <span class="pms-page-btn" aria-disabled="true" style="opacity:.45; cursor:default;">
            Next &rarr;
        </span>
    @endif

</nav>