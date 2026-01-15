<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/admin','Admin'],['/layers','Layer/GenericLabel'],['', 'Layer Type #' . $layerType->idLayerType]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_layertype">{{$layerType->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$layerType->idLayerType}}
                            </div>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$layerType->entry}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="layerTypeTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/layertype/'.$layerType->idLayerType.'/formEdit'],
                            'genericlabels' => ['id' => 'genericlabels', 'label' => 'Generic Labels', 'url' => '/layertype/'.$layerType->idLayerType.'/genericlabels']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
