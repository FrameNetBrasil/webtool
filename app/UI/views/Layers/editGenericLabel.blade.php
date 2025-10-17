<x-layout.object>
    <x-slot:name>
        <span>{{$genericLabel->name}}</span>
        <div class="ui label wt-tag-type">
            GenericLabel
        </div>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label wt-tag-id">
            #{{$genericLabel->idGenericLabel}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing GenericLabel '{{$genericLabel->name}}'.`, '/layers/genericlabel/{{$genericLabel->idGenericLabel}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>

    </x-slot:description>
    <x-slot:main>
        @include("Layers.menuGenericLabel")
    </x-slot:main>
</x-layout.object>
