<x-layout.object>
    <x-slot:name>
        <span class="color_image">{{$sentence->idSentence}}</span>
    </x-slot:name>
    <x-slot:detail>
        <div class="ui label tag wt-tag-id">
            #{{$sentence->idSentence}}
        </div>
        <x-button
            label="Delete"
            color="danger"
            onclick="messenger.confirmDelete(`Removing Sentence '{{$sentence->idSentence}}'.`, '/sentence/{{$sentence->idSentence}}')"
        ></x-button>
    </x-slot:detail>
    <x-slot:description>
    </x-slot:description>
    <x-slot:main>
        @include("Sentence.menu")
    </x-slot:main>
</x-layout.object>
