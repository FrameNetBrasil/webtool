<x-layout::index>
    @fragment("post")
        <div class="app-layout">
            <x-partial::header></x-partial::header>
            <x-partial::breadcrumb
                :sections="[['/','Home'],['/report','Report'],['','Frame']]"
            ></x-partial::breadcrumb>
            <main class="app-main">
                <div class="page-content slide">
                    <div
                        class="ui container page"
                        x-init="$('.item').tab()"
                    >
                        <div class="ui tab h-full browse" data-tab="browse">
                            <div class="ui container page">
                                <div class="page-header">
                                    <div class="page-header-content">
                                        <div class="page-title">
                                            Report Frame
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
                                                      hx-post="/report/frame/search"
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
                                                                placeholder="Search Frame"
                                                                autocomplete="off"
                                                                value="{{$frame ?? ''}}"
                                                                autofocus
                                                            >
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="search-result-section">
                                            @fragment("search")
                                                <div class="h-full overflow-auto flex-1">
                                                    @if(count($frames) > 0)
                                                        <table class="ui selectable striped compact table frame">
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

{{--<x-layout::index>--}}
{{--    <div class="app-layout">--}}
{{--        <x-partial::header></x-partial::header>--}}
{{--        <x-partial::breadcrumb--}}
{{--            :sections="[['/','Home'],['/report','Report'],['','Frame']]"--}}
{{--        ></x-partial::breadcrumb>--}}
{{--        <main class="app-main">--}}
{{--            <div class="page-content slide">--}}
{{--                <div--}}
{{--                    class="ui container page"--}}
{{--                    x-init="$('.item').tab()"--}}
{{--                >--}}
{{--                    <div class="ui tab h-full" data-tab="browse">--}}
{{--                        <x-ui::browse-table--}}
{{--                            title="Frame Report"--}}
{{--                            url="/report/frame/search"--}}
{{--                            emptyMsg="Enter your search term above to find frames."--}}
{{--                            :data="$data"--}}
{{--                        >--}}
{{--                            <x-slot:fields>--}}
{{--                                <div class="field">--}}
{{--                                    <div class="ui left icon input w-full">--}}
{{--                                        <i class="search icon"></i>--}}
{{--                                        <input--}}
{{--                                            type="search"--}}
{{--                                            name="frame"--}}
{{--                                            placeholder="Search Frame"--}}
{{--                                            autocomplete="off"--}}
{{--                                        >--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </x-slot:fields>--}}

{{--                            <x-slot:table>--}}
{{--                                <table--}}
{{--                                    x-data--}}
{{--                                    class="ui selectable striped compact table"--}}
{{--                                >--}}
{{--                                    <tbody>--}}
{{--                                    @foreach($data as $frame)--}}
{{--                                        <tr>--}}
{{--                                            <td>--}}
{{--                                                <div--}}
{{--                                                    hx-get="/report/frame/{{$frame['id']}}"--}}
{{--                                                    hx-target=".report"--}}
{{--                                                    hx-on::before-request="$.tab('change tab','report')"--}}
{{--                                                >--}}
{{--                                                    {!! $frame['text'] !!}--}}
{{--                                                </div>--}}
{{--                                            </td>--}}
{{--                                        </tr>--}}
{{--                                    @endforeach--}}
{{--                                    </tbody>--}}
{{--                                </table>--}}
{{--                            </x-slot:table>--}}
{{--                        </x-ui::browse-table>--}}

{{--                    </div>--}}
{{--                    <div class="ui tab report h-full" data-tab="report">--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--        </main>--}}
{{--        <x-partial::footer></x-partial::footer>--}}
{{--    </div>--}}
{{--</x-layout::index>--}}
