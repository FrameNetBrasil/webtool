<x-layout.browser>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Corpus']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div class="ui container h-full">
            <div class="ui card h-full w-full p-2">
                <div class="flex-grow-0 content h-4rem">
                    <div class="flex align-items-center justify-content-between">
                        <div><h2 class="ui header">Corpus/Document</h2></div>
                        <div>
                            <a
                                class="ui secondary button"
                                href="/corpus/new"
                            >New Corpus</a>
                            <a
                                class="ui secondary button"
                                href="/document/new"
                            >New Document</a>
                        </div>
                    </div>
                </div>
                <div class="app-search">
                    <div class="search-section"
                         x-data="searchFormComponent()"
                         @htmx:before-request="onSearchStart"
                         @htmx:after-request="onSearchComplete"
                         @htmx:after-swap="onResultsUpdated"
                    >
                        <div class="search-input-group">
                            <form class="ui form"
                                  hx-post="/corpus/browse/search"
                                  hx-target="#treeArea"
                                  hx-swap="innerHTML"
                                  hx-trigger="submit, input delay:500ms"
                            >
                                <div class="two fields">
                                    <div class="field">
                                        <div class="ui left icon input w-full">
                                            <i class="search icon"></i>
                                            <input
                                                type="search"
                                                name="corpus"
                                                placeholder="Search Corpus"
                                                autocomplete="off"
                                            >
                                        </div>
                                    </div>
                                    <div class="field">
                                        <div class="ui left icon input w-full">
                                            <i class="search icon"></i>
                                            <input
                                                type="search"
                                                name="document"
                                                placeholder="Search Document"
                                                autocomplete="off"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="gridArea">
                        <div class="results-container view-cards">
                            <div class="results-wrapper">
                                <div class="tree-view" x-transition>
                                    <div
                                        class="search-results-tree"
                                        x-data
                                        @tree-item-selected.document="(event) => {
                                                    let type =  event.detail.type;
                                                    let idNode = type + '_' + event.detail.id;
                                                    if (type === 'corpus') {
                                                        window.location.assign(`corpus/${event.detail.id}/edit`);
                                                    } else if (type === 'document') {
                                                        window.location.assign(`document/${event.detail.id}/edit`);
                                                    }
                                                }"
                                    >
                                        <div id="treeArea">
                                            @include("Corpus.tree")
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot:main>
</x-layout.browser>
