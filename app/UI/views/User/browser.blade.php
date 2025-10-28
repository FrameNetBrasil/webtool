<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['','Group/User']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Group/User"
                url="/user/search"
                emptyMsg="Enter your search term above to find Groups or Users."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/group/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Group
                    </a>
                    <a href="/user/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New User
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="two fields">
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="group"
                                    placeholder="Search Group"
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
                                    placeholder="Search User (login/email/name)"
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
                                                    let type =  event.detail.type;
                                                    let idNode = type + '_' + event.detail.id;
                                                    console.log(event.detail);
                                                    if (type === 'group') {
                                                        window.location.assign(`/group/${event.detail.id}/edit`);
                                                    }
                                                    if (type === 'user') {
                                                        window.location.assign(`/user/${event.detail.id}/edit`);
                                                    }
                                                }"
                    >
                        <div id="treeArea" class="h-full">
                            @include("User.tree", ['title' => '', 'data' => $data])
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>
