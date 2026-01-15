<x-layout.object>
    <x-slot:name>
        <span>{{$domain?->name}}</span>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label tag wt-tag-id">
            #{{$domain->idDomain}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing Domain '{{$domain?->name}}'.`, '/domain/{{$domain->idDomain}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>
        {{$domain->description}}
    </x-slot:description>
    <x-slot:main>
        @include("Domain.menu")
    </x-slot:main>
</x-layout.object>
