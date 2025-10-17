<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','Project/Dataset']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Project/Dataset"
                url="/project/search"
                emptyMsg="Enter your search term above to find Projects/Datasets."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/project/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Project
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="two fields">
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="project"
                                    placeholder="Search Project"
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="dataset"
                                    placeholder="Search Dataset"
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
                                                    if (type === 'project') {
                                                        window.location.assign(`/project/${event.detail.id}/edit`);
                                                    }
                                                    if (type === 'dataset') {
                                                        window.location.assign(`/dataset/${event.detail.id}/edit`);
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("Project.tree")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>