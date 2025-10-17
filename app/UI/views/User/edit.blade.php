<x-layout.object>
    <x-slot:name>
        <span>{{$user->login}}</span>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label wt-tag-id">
            #{{$user->idUser}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing User '{{$user?->login}}'.`, '/user/{{$user->idUser}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>
        {{$user->email}} [{{$user->name}}]
    </x-slot:description>
    <x-slot:main>
        @include("User.menu")
    </x-slot:main>
</x-layout.object>
