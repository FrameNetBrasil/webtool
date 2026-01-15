<x-layout::index>
    @fragment("post")
        <div class="app-layout">
            <x-partial::header></x-partial::header>
            <x-partial::breadcrumb
                :sections="[['/','Home'],['/report','Report'],['','Frame/LU']]"
            ></x-partial::breadcrumb>
            <main class="app-main">
                <div class="page-content slide">
                    <div
                        class="ui container page"
                        x-init="$('.item').tab()"
                    >
                        <div class="ui tab h-full" data-tab="browse">
                            <div class="ui container page">
                                <div class="page-header">
                                    <div class="page-header-content">
                                        <div class="page-title">
                                            Report Frame/LU
                                        </div>
                                    </div>
                                </div>
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
                                                      hx-post="/report/frame_lu/search"
                                                      hx-target="body"
                                                      hx-swap="innerHTML"
                                                      hx-trigger="submit, input delay:500ms"
                                                >
                                                    <div class="field">
                                                        <div class="ui left icon input w-full">
                                                            <i class="search icon"></i>
                                                            <input
                                                                type="search"
                                                                name="frame"
                                                                placeholder="Search Frame/LU"
                                                                autocomplete="off"
                                                                value="{{$frame}}"
                                                                autofocus
                                                            >
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="search-result-section d-flex gap-1">
                                            @fragment("search")
                                                <div class="h-full overflow-auto flex-1">
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
                                                        <div class="search-result-empty" id="emptyState">
                                                            <i class="search icon empty-icon"></i>
                                                            <h3 class="empty-title">No results found.</h3>
                                                            <p class="empty-description">
                                                                Enter your search term above to find Frame.
                                                            </p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="h-full overflow-auto flex-1">
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
                                                        <div class="search-result-empty" id="emptyState">
                                                            <i class="search icon empty-icon"></i>
                                                            <h3 class="empty-title">No results found.</h3>
                                                            <p class="empty-description">
                                                                Enter your search term above to find LU.
                                                            </p>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endfragment
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ui tab report h-full" data-tab="report">
                        </div>
                    </div>
                </div>

            </main>
            <x-partial::footer></x-partial::footer>
        </div>
    @endfragment
</x-layout::index>
