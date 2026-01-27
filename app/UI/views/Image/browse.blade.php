<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['','Image/Document']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Image/Document"
                url="/image/browse/searchImage"
                emptyMsg="Enter your search term above to find items."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/image/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Image
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="three fields">
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
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="image"
                                    placeholder="Search image"
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
                                                    if ((type === 'corpus') || (type === 'document')) {
                                                        event.detail.tree.toggleNodeState(idNode);
                                                    } else if (type === 'image') {
                                                        window.open(`/image/${event.detail.id}/edit`);
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("Image.tree")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
