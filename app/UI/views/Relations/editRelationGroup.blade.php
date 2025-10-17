<x-layout.object>
    <x-slot:name>
        <span>{{$relationGroup->name}}</span>
        <div class="ui label wt-tag-type">
            RelationGroup
        </div>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label wt-tag-id">
            #{{$relationGroup->idRelationGroup}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing RelationGroup '{{$relationGroup->name}}'.`, '/relations/relationgroup/{{$relationGroup->idRelationGroup}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>

    </x-slot:description>
    <x-slot:main>
        @include("Relations.menuRelationGroup")
    </x-slot:main>
</x-layout.object>
