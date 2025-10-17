<x-layout.object>
    <x-slot:name>
        <span>{{$semanticType?->name}}</span>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label tag wt-tag-id">
            #{{$semanticType->idSemanticType}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing SemanticType '{{$semanticType?->name}}'.`, '/semanticType/{{$semanticType->idSemanticType}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>
        {{$semanticType->description}}
    </x-slot:description>
    <x-slot:main>
        @include("SemanticType.menu")
    </x-slot:main>
</x-layout.object>
