<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/admin','Admin'],['/layers','Layer/GenericLabel'],['', 'Generic Label #' . $genericLabel->idGenericLabel]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_genericlabel">{{$genericLabel->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$genericLabel->idGenericLabel}}
                            </div>
                        </div>
                    </div>
                    @if($genericLabel->definition)
                    <div class="page-subtitle">
                        {{$genericLabel->definition}}
                    </div>
                    @endif
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="genericLabelTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/genericlabel/'.$genericLabel->idGenericLabel.'/formEdit']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
