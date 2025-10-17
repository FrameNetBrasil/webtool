<x-layout.object>
    <x-slot:name>
        <span class="color_image">{{$image->name}}</span>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label tag wt-tag-id">
            #{{$image->idImage}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing Image '{{$image->name}}'.`, '/image/{{$image->idImage}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>
    </x-slot:description>
    <x-slot:main>
        @include("Image.menu")
    </x-slot:main>
</x-layout.object>
