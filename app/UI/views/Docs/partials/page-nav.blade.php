@if(isset($previousPage) || isset($nextPage))
    <nav class="docs-page-nav">
        <div class="page-nav-container">
            @if(isset($previousPage))
                <a href="/docs/{{ $previousPage['path'] }}" class="page-nav-link page-nav-prev">
                    <div class="page-nav-label">
                        <i class="angle left icon"></i>
                        Previous
                    </div>
                    <div class="page-nav-title">{{ $previousPage['title'] }}</div>
                </a>
            @else
                <div class="page-nav-spacer"></div>
            @endif

            @if(isset($nextPage))
                <a href="/docs/{{ $nextPage['path'] }}" class="page-nav-link page-nav-next">
                    <div class="page-nav-label">
                        Next
                        <i class="angle right icon"></i>
                    </div>
                    <div class="page-nav-title">{{ $nextPage['title'] }}</div>
                </a>
            @else
                <div class="page-nav-spacer"></div>
            @endif
        </div>
    </nav>
@endif
