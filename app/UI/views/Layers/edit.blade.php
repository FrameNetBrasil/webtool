<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/admin','Admin'],['/layers','Layer/GenericLabel'],['', 'Layer Group #' . $layerGroup->idLayerGroup]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_layergroup">{{$layerGroup->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$layerGroup->idLayerGroup}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Layer Group '{{$layerGroup?->name}}'.`, '/layers/{{$layerGroup->idLayerGroup}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$layerGroup->type}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="layerGroupTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/layers/'.$layerGroup->idLayerGroup.'/formEdit'],
                            'layertypes' => ['id' => 'layertypes', 'label' => 'Layer Types', 'url' => '/layers/'.$layerGroup->idLayerGroup.'/layertypes']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
