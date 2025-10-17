<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['','Lemmas']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Lemmas"
                url="/lemma/search"
                emptyMsg="Enter your search term above to find Lemmas."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/lemma/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Lemma
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="field">
                        <div class="ui left icon input w-full">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="lemma"
                                placeholder="Search Lemma"
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
                                                    if (type === 'lemma') {
                                                        window.location.assign(`/lemma/${event.detail.id}`);
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("Lemma.tree")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>
