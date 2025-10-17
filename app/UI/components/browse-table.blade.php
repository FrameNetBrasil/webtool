<div class="ui container page-browse">
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                {{$title}}
            </div>
        </div>
    </div>
    @if(isset($actions))
        <div class="page-actions">
        {{$actions}}
        </div>
    @endif
    <div class="page-content">
        <div class="search-container">
            <div class="search-input-section"
                 x-data="searchFormComponent()"
                 @htmx:before-request="onSearchStart"
                 @htmx:after-request="onSearchComplete"
                 @htmx:after-swap="onResultsUpdated"
            >
                <div class="search-input-group">
                    <form class="ui form"
                          hx-post="{{$url}}"
                          hx-target=".search-result-section"
                          hx-swap="innerHTML"
                          hx-trigger="submit, input delay:500ms"
                    >
                        {{$fields}}
                    </form>
                </div>
            </div>

            <div class="search-result-section">
                @fragment("search")
                    @if(count($data) > 0)
                        <div class="search-result-data">
                            {{$table}}
                        </div>
                    @else
                        <div class="search-result-empty" id="emptyState">
                            <i class="search icon empty-icon"></i>
                            <h3 class="empty-title">No results found.</h3>
                            <p class="empty-description">
                                {{$emptyMsg}}
                            </p>
                        </div>
                    @endif
                @endfragment
            </div>
        </div>
    </div>
</div>

