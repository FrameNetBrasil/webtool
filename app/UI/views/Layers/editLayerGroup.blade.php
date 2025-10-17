<x-layout.object>
    <x-slot:name>
        <span>{{$layerGroup->name}}</span>
        <div class="ui label wt-tag-type">
            LayerGroup
        </div>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label wt-tag-id">
            #{{$layerGroup->idLayerGroup}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing LayerGroup '{{$layerGroup->name}}'.`, '/layers/layergroup/{{$layerGroup->idLayerGroup}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>

    </x-slot:description>
    <x-slot:main>
        @include("Layers.menuLayerGroup")
    </x-slot:main>
</x-layout.object>
