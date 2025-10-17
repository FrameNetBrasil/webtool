<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','Task/User']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Task/User"
                url="/task/search"
                emptyMsg="Enter your search term above to find Tasks/Users."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/task/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Task
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="two fields">
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="task"
                                    placeholder="Search Task"
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="user"
                                    placeholder="Search User"
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
                                                    if (type === 'task') {
                                                        window.location.assign(`/task/${event.detail.id}/edit`);
                                                    }
                                                    if (type === 'user') {
                                                        window.location.assign(`/usertask/${event.detail.id}/edit`);
                                                    }
                                                }"
                    >
                        <div id="treeArea">
                            @include("Task.tree")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>