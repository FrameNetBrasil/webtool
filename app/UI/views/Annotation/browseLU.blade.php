<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/annotation','Annotation'],['','AnnotationSets by LU']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                :title="$page"
                url="/annotation/browse/searchLU"
                emptyMsg="Enter your search term above to find LU."
                :data="$data"
            >
                <x-slot:fields>
                    <div class="field">
                        <div class="ui left icon input w-full">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="lu"
                                placeholder="Search LU"
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
                                                    if (type === 'lu') {
                                                        window.open(`{{$url}}/${event.detail.id}`, '_blank');
                                                    } else if (type === 'sentence') {
                                                        window.open(`{{$url}}/annotationset/${event.detail.id}`, '_blank');
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("Annotation.treeLU")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
