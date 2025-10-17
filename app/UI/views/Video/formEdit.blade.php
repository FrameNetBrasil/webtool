<x-form
    title="Edit video"
    hx-post="/video"
>
    <x-slot:fields>
        <x-hidden-field
            id="idVideo"
            :value="$video->idVideo"
        ></x-hidden-field>
        <div class="field">
            <x-text-field
                label="Title"
                id="title"
                :value="$video->title"
            ></x-text-field>
        </div>
        <div class="field">
            <x-text-field
                label="Original File"
                id="originalFile"
                :value="$video->originalFile"
            ></x-text-field>
        </div>
        <div class="field">
            <label>SHA1 Name</label>
        </div>
        <div class="field">
            <div>{{$video->sha1Name}}</div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Save"></x-submit>
    </x-slot:buttons>
</x-form>
