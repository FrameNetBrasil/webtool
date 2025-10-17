<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['',$page]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                :title="$page"
                url="/annotation/browse/searchSentence"
                emptyMsg="Enter your search term above to find items."
                :data="$data"
            >
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
                                    name="idDocumentSentence"
                                    placeholder="Search #idSentence"
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
                                                    } else if (type === 'sentence') {
                                                        window.open(`{{$url}}/sentence/${event.detail.id}`, '_blank');
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("Annotation.treeSentences")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>
