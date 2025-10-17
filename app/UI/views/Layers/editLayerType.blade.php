<x-layout.object>
    <x-slot:name>
        <span>{{$layerType->name}}</span>
        <div class="ui label wt-tag-type">
            LayerType
        </div>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label wt-tag-id">
            #{{$layerType->idLayerType}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing LayerType '{{$layerType->name}}'.`, '/layers/layertype/{{$layerType->idLayerType}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>

    </x-slot:description>
    <x-slot:main>
        @include("Layers.menuLayerType")
    </x-slot:main>
</x-layout.object>
