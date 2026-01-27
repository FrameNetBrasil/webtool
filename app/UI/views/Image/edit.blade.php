<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['','Image/Document'],['', 'Image #' . $image->idImage]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$image->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$image->idImage}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Image '{{$image->name}}'.`, '/image/{{$image->idImage}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="corpusTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/image/'.$image->idImage.'/formEdit'],
                            'documents' => ['id' => 'documents', 'label' => 'Documents', 'url' => '/image/'.$image->idImage.'/documents'],
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>

