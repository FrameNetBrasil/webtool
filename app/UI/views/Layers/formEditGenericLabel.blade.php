<x-form
    hx-put="/layers/genericlabel"
>
    <x-slot:fields>
        <x-hidden-field id="idGenericLabel" value="{{$genericLabel->idGenericLabel}}"></x-hidden-field>
        <div class="field">
            <x-text-field
                label="Name"
                id="name"
                value="{{$genericLabel->name}}"
            ></x-text-field>
        </div>
        <div class="field">
            <x-multiline-field
                label="Definition"
                id="definition"
                value="{{$genericLabel->definition}}"
            ></x-multiline-field>
        </div>
        <div class="three fields">
            <div class="field">
                <x-combobox.language
                    id="idLanguage"
                    label="Language"
                    :value="$genericLabel->idLanguage"
                ></x-combobox.language>
            </div>
            <div class="field">
                <x-combobox.color
                    id="idColor"
                    label="Color"
                    :value="$genericLabel->idColor"
                    placeholder="Color"
                ></x-combobox.color>
            </div>
            <div class="field">
                <x-combobox.layer-type
                    label="LayerType"
                    id="idLayerType"
                    :value="$genericLabel->idLayerType"
                ></x-combobox.layer-type>
            </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Save"></x-submit>
    </x-slot:buttons>
</x-form>
