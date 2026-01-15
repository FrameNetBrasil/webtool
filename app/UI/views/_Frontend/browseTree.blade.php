{{--Layout for browse records with search input --}}
{{--Goal: Browse records using a tree component --}}
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Title"
                url="/url/to/search"
                emptyMsg="Enter your search term above to find records."
                :data="$data"
            >
                <x-slot:actions>
                    {{-- Buttons for actions over the entity --}}
                    <a href="/url/for/action"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        Action
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    {{-- Input search fields --}}
                    <div class="fields">
                        <div class="field">
                            <div class="field">
                                <div class="ui left icon input w-full">
                                    <i class="search icon"></i>
                                    <input
                                        type="search"
                                        name="fieldName"
                                        placeholder="Search Entity"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot:fields>

                <x-slot:tree>
                    {{--  Tree showing records/result --}}
                    <div
                        x-data
                        class="w-full"
                        @tree-item-selected.document="(event) => {
                                                    let type =  event.detail.type;
                                                    let idNode = type + '_' + event.detail.id;
                                                    if (type === 'x') {
                                                        window.location.assign(`/url/for/${event.detail.id}/edit`);
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("_Frontend.tree")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
