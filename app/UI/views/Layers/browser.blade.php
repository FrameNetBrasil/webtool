<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['','Layer/GenericLabel']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Layer Groups / Layer Types / Generic Labels"
                url="/layers/search"
                emptyMsg="Enter your search term above to find Layer Groups, Layer Types, or Generic Labels."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/layers/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Layer Group
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="three fields">
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="layerGroup"
                                    placeholder="Search Layer Group"
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="layerType"
                                    placeholder="Search Layer Type"
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="genericLabel"
                                    placeholder="Search Generic Label"
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                    </div>
                </x-slot:fields>

                <x-slot:tree>
                    <div
                        x-data
                        class="w-full h-full"
                        @tree-item-selected.document="(event) => {
                                                    let type = event.detail.type;
                                                    let id = event.detail.id;
                                                    console.log(event.detail);
                                                    if (type === 'layergroup') {
                                                        window.location.assign(`/layers/${id}/edit`);
                                                    }
                                                    if (type === 'layertype') {
                                                        window.location.assign(`/layertype/${id}/edit`);
                                                    }
                                                    if (type === 'genericlabel') {
                                                        window.location.assign(`/genericlabel/${id}/edit`);
                                                    }
                                                }"
                    >
                        <div id="treeArea" class="h-full">
                            @include("Layers.tree", ['title' => '', 'data' => $data])
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
