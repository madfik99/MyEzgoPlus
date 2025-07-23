@if ($paginator->hasPages())
    <nav>
        <ul class="pagination pagination-sm justify-content-center mb-0">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled"><span class="page-link">{!! __('pagination2.previous') !!}</span></li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{!! __('pagination2.previous') !!}</a>
                </li>
            @endif

            @php
                $current = $paginator->currentPage();
                $last = $paginator->lastPage();
                $side = 2; // Number of pages to show on each side of current
                $window = $side * 2 + 1; // Total "window" size

                $pages = [];

                // Always show first 2 pages
                for ($i = 1; $i <= 2 && $i <= $last; $i++) {
                    $pages[] = $i;
                }

                // Main window
                $start = max(3, $current - $side);
                $end = min($last - 2, $current + $side);

                if ($start - 1 > 2) {
                    $pages[] = '...';
                }

                for ($i = $start; $i <= $end; $i++) {
                    if ($i > 2 && $i < $last - 1) {
                        $pages[] = $i;
                    }
                }

                if ($last - $end > 1) {
                    $pages[] = '...';
                }

                // Always show last 2 pages
                for ($i = max($last - 1, 1); $i <= $last; $i++) {
                    if ($i > 2) {
                        $pages[] = $i;
                    }
                }

                $pages = array_unique($pages);
            @endphp

            {{-- Render Pages --}}
            @foreach ($pages as $page)
                @if ($page === '...')
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @elseif ($page == $current)
                    <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">{!! __('pagination2.next') !!}</a>
                </li>
            @else
                <li class="page-item disabled"><span class="page-link">{!! __('pagination2.next') !!}</span></li>
            @endif
        </ul>

        {{-- Optional: Show item range --}}
        <div class="text-center small text-muted mt-1">
            {!! __('Showing') !!}
            <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
            {!! __('to') !!}
            <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
            {!! __('of') !!}
            <span class="fw-semibold">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </div>
    </nav>
@endif
