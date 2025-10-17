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
                          hx-target="#treeArea"
                          hx-swap="innerHTML"
                          hx-trigger="submit, input delay:500ms"
                    >
                        {{$fields}}
                    </form>
                </div>
            </div>

            <div class="search-result-section">
                {{$tree}}
            </div>
        </div>
    </div>
</div>

