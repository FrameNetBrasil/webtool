<x-layout.edit>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['/cxn','Constructions'],['',$cxn?->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <x-layout.object>
            <x-slot:name>
                <span class="color_cxn">{{$cxn?->name}}</span>
            </x-slot:name>
            <x-slot:description>

            </x-slot:description>
            <x-slot:detail>
                <div class="ui label tag wt-tag-id">
                    #{{$cxn->idConstruction}}
                </div>
                @if(session('isAdmin'))
                    <x-button
                        label="Delete"
                        color="danger"
                        onclick="messenger.confirmDelete(`Removing Construction '{{$cxn?->name}}'.`, '/cxn/{{$cxn->idConstruction}}')"
                    ></x-button>
                @endif
            </x-slot:detail>
            <x-slot:description>
                {{$cxn->description}}
            </x-slot:description>
            <x-slot:main>
                @include("Construction.menu")
            </x-slot:main>
        </x-layout.object>
    </x-slot:main>
</x-layout.edit>

