<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/report','Report'],['','LU']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Lexicon"
                url="/lexicon3/search"
                emptyMsg="Enter your search term above to find Lemmas/Forms."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/lexicon3/lemma/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Lemma
                    </a>
                    <a href="/lexicon3/form/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Form
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="two fields">
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
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="form"
                                    placeholder="Search Form"
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
                                                    if (type === 'lemma') {
                                                        window.location.assign(`/lexicon3/lemma/${event.detail.id}`);
                                                    }
                                                    if (type === 'form') {
                                                        window.location.assign(`/lexicon3/form/${event.detail.id}`);
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("Lexicon3.tree")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>
