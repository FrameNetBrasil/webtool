<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/annotation','Annotation'],['','Session']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <x-ui::browse-tree
                title="Session Report"
                url="/annotation/session/search"
                emptyMsg="Enter your search term above to find items."
                :data="$data"
            >
                <x-slot:fields>
                    <div class="fields">
                        <div class="field">
                            <div
                                class="ui floating dropdown labeled search icon button"
                                x-init="$($el).dropdown({
                                    onChange: (value, text, $selectedItem) => {
                                        console.log(value, text, $selectedItem);
                                        htmx.trigger($($el).closest('form')[0], 'submit');
                                    }
                                })"
                            >
                                <input type="hidden" name="idUser">
                                <i class="user icon"></i>
                                <span class="text">Select User</span>
                                <div class="menu">
                                    <div class="item" data-value="">all annotator</div>
                                    @foreach($annotators as $annotator)
                                        <div class="item" data-value="{{$annotator->idUser}}">{{$annotator->email}}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot:fields>

                <x-slot:tree>
                    <div
                        x-data
                        class="w-full"
                        @tree-item-selected.document="(event) => {
                            let type = event.detail.type;
                            let idNode = type + '_' + event.detail.id;
                            console.log(event.detail);
                            if (type === 'user' || type === 'sentence') {
                                event.detail.tree.toggleNodeState(idNode);
                            }
                        }"
                    >
                        <div id="treeArea">
                            @include("Annotation.Session.tree")
                        </div>
                    </div>
                </x-slot:tree>
            </x-ui::browse-tree>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>
