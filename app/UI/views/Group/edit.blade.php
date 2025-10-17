<x-layout.object>
    <x-slot:name>
        <span>{{$group->name}}</span>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label wt-tag-id">
            #{{$group->idGroup}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing Group '{{$group?->name}}'.`, '/group/{{$group->idGroup}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>
        {{$group->description}}
    </x-slot:description>
    <x-slot:main>
        @include("Group.menu")
    </x-slot:main>
</x-layout.object>
