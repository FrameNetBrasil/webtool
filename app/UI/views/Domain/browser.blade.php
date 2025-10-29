<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['','Domain/SemanticType']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Domain/SemanticType"
                url="/domain/search"
                emptyMsg="Enter your search term above to find Domains/SemanticTypes."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/domain/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Domain
                    </a>
                    <a href="/semanticType/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New SemanticType
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="two fields">
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="domain"
                                    placeholder="Search Domain"
                                    autocomplete="off"
                                >
                            </div>
                        </div>
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
                    </div>
                </x-slot:fields>

                <x-slot:tree>
                    <div
                        x-data
                        class="w-full"
                        @tree-item-selected.document="(event) => {
                                                    let type =  event.detail.type;
                                                    let idNode = type + '_' + event.detail.id;
                                                    console.log('Tree item selected:', event.detail);

                                                    if (type === 'domain') {
                                                        // Domains can be expanded or clicked to edit
                                                        // Toggle if it has children, otherwise navigate
                                                        event.detail.tree.toggleNodeState(idNode);
                                                    } else if (type === 'semanticType') {
                                                        // SemanticTypes: toggle if has children, navigate if leaf
                                                        if (event.detail.leaf) {
                                                            // Leaf node: navigate to edit page
                                                            window.location.assign(`/semanticType/${event.detail.id}/edit`);
                                                        } else {
                                                            // Has children: toggle to show/hide subtypes
                                                            event.detail.tree.toggleNodeState(idNode);
                                                        }
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("Domain.tree")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>
