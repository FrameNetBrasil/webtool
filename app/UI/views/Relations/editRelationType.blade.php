<x-layout.object>
    <x-slot:name>
        <span>{{$relationType->name}}</span>
        <div class="ui label wt-tag-type">
            RelationType
        </div>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label wt-tag-id">
            #{{$relationType->idRelationType}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing RelationType '{{$relationType->nameCanonical}}'.`, '/relations/relationtype/{{$relationType->idRelationType}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>

    </x-slot:description>
    <x-slot:main>
        @include("Relations.menuRelationType")
    </x-slot:main>
</x-layout.object>
