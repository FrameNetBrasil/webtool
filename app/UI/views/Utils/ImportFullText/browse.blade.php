<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['','Import fulltext']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Import fulltext"
                url="/corpus/browse/search"
                emptyMsg="Enter your search term above to find Corpus or Documents."
                :data="$data"
            >
                <x-slot:fields>
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
                </x-slot:fields>

                <x-slot:tree>
                    <div
                        x-data
                        class="w-full"
                        @tree-item-selected.document="(event) => {
                                                    let type =  event.detail.type;
                                                    let idNode = type + '_' + event.detail.id;
                                                    console.log(event.detail);
                                                    if (type === 'corpus') {
                                                        event.detail.tree.toggleNodeState(idNode);
                                                    }
                                                    if (type === 'document') {
                                                        window.location.assign(`/utils/importFullText/${event.detail.id}`);
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("Corpus.tree")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
