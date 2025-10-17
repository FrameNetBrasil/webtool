<x-layout.object>
    <x-slot:name>
        <span>{{$video->title}}</span>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label tag wt-tag-id">
            #{{$video->idVideo}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing Video '{{$video->title}}'.`, '/video/{{$video->idVideo}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>

    </x-slot:description>
    <x-slot:main>
        @include("Video.menu")
    </x-slot:main>
</x-layout.object>
