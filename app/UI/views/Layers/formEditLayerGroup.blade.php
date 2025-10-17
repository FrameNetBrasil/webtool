<x-form
    hx-put="/layers/layergroup"
>
    <x-slot:fields>
        <x-hidden-field id="idLayerGroup" value="{{$layerGroup->idLayerGroup}}"></x-hidden-field>
        <div class="field">
            <x-text-field
                label="Name"
                id="name"
                value="{{$layerGroup->name}}"
            ></x-text-field>
        </div>
        <div class="field">
            <x-combobox.options
                label="Type"
                id="type"
                :options="['Deixis' => 'Deixis','Text' => 'Text']"
                value="{{$layerGroup->type}}"
            ></x-combobox.options>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Save"></x-submit>
    </x-slot:buttons>
</x-form>
