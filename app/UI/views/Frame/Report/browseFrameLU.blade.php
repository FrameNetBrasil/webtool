<div class="ui container browse-page">
    <div class="app-search">
        <div class="search-section"
             x-data="searchFormComponent()"
             @htmx:before-request="onSearchStart"
             @htmx:after-request="onSearchComplete"
             @htmx:after-swap="onResultsUpdated"
        >
            <div class="search-input-group">
                <form class="ui form"
                      hx-post="/report/frame_lu/search"
                      hx-target=".page-content"
                      hx-swap="innerHTML"
                      hx-trigger="submit, input delay:500ms"
                >
                    <div class="two fields">
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="frame"
                                    placeholder="Search Frame"
                                    autocomplete="off"
                                    value="{{$frame}}"
                                >
                            </div>
                        </div>
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="lu"
                                    placeholder="Search LU"
                                    autocomplete="off"
                                    value="{{$lu}}"
                                >
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="gridArea">
            @fragment("search")
                <div class="results-container view-cards">
                    <div class="results-wrapper d-flex gap-2">
                        {{--                        <div class="tree-view" x-transition>--}}
                        <div class="w-1/2 h-full overflow-auto p-1">
                            @if(count($frames) > 0)
                                <table class="ui selectable striped compact table">
                                    <tbody>
                                    @foreach($frames as $frame)
                                        <tr
                                            class="cursor-pointer"
                                            @click="window.location.assign('/report/frame/{{$frame['id']}}')"
                                        >
                                            <td>{!! $frame['text'] !!}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="empty-state" id="emptyState">
                                    <i class="search icon empty-icon"></i>
                                    <h3 class="empty-title">No results found.</h3>
                                </div>
                            @endif
                        </div>
                        <div class="w-1/2 h-full overflow-auto p-1">
                            @if(count($lus) > 0)
                                <table class="ui selectable striped compact table">
                                    <tbody>
                                    @foreach($lus as $lu)
                                        <tr
                                            class="cursor-pointer"
                                            @click="window.location.assign('/report/lu/{{$lu['id']}}')"
                                        >
                                            <td>{!! $lu['text'] !!}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="empty-state" id="emptyState">
                                    <i class="search icon empty-icon"></i>
                                    <h3 class="empty-title">No results found.</h3>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                {{--                        </div>--}}
        </div>
    </div>
    @endfragment
</div>

