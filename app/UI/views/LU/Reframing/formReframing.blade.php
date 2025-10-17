<x-form-inline>
    <x-slot:fields>
        <x-hidden-field id="idLU" :value="$lu->idLU"></x-hidden-field>
            <div class="field w-20rem">
                <x-combobox.frame
                    id="idNewFrame"
                    label="Change Frame to (min 3 chars)"
                    :placeholder="$lu->frame->name"
                    onSelect="htmx.ajax('GET', '/reframing/edit/{{$lu->idLU}}/' + result.idFrame, '#reframingEdit');"
                ></x-combobox.frame>
            </div>
    </x-slot:fields>
    <x-slot:buttons>
    </x-slot:buttons>
</x-form-inline>
