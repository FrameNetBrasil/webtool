<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['','SemanticType']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="SemanticType"
                url="/semanticType/browse/search"
                emptyMsg="Enter your search term above to find SemanticTypes."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/semanticType/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New SemanticType
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="field">
                        <div class="ui left icon input w-full">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="semanticType"
                                placeholder="Search SemanticType"
                                autocomplete="off"
                            >
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
                                                    if (type === 'semanticType') {
                                                        window.location.assign(`/semanticType/${event.detail.id}/edit`);
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("SemanticType.tree")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
