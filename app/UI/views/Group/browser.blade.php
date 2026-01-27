<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['','Groups']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-table
                title="Groups"
                url="/group/search"
                emptyMsg="Enter your search term above to find Groups."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/group/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Group
                    </a>
                </x-slot:actions>
                <x-slot:fields>
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
                </x-slot:fields>

                <x-slot:table>
                    <div
                        x-data
                        class="w-full"
                    >
                        <div id="gridArea">
                            @include("Group.grid")
                        </div>
                    </div>
                </x-slot:table>
            </x-ui::browse-table>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
