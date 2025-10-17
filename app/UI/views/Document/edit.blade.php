<x-layout.page>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['/corpus','Corpus/Document'],['',$document->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div class="ui container h-full">
            <x-layout.object>
                <x-slot:name>
                    <span>{{$document->name}}</span>
                </x-slot:name>
                <x-slot:detail>
                    <div class="ui label tag wt-tag-id">
                        #{{$document->idDocument}}
                    </div>
                    <x-button
                        label="Delete"
                        color="danger"
                        onclick="messenger.confirmDelete(`Removing Document '{{$document->name}}'.`, '/document/{{$document->idDocument}}')"
                    ></x-button>
                </x-slot:detail>
                <x-slot:description>
                    {{$document->description}}
                </x-slot:description>
                <x-slot:main>
                    @include("Document.menu")
                </x-slot:main>
            </x-layout.object>
        </div>
    </x-slot:main>
</x-layout.page>
