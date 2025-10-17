<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idFrame"
            :value="$idFrame"
        ></x-hidden-field>
        <div class="four fields">
            <div class="field">
                <x-text-field
                    id="nameEn"
                    label="English Name"
                    value=""
                ></x-text-field>
            </div>
            <div class="field">
                <x-combobox.fe-coreness
                    id="coreType"
                    label="Coreness"
                ></x-combobox.fe-coreness>
            </div>
            <div class="field">
                <x-combobox.color
                    id="idColor"
                    label="Color"
                    value=""
                ></x-combobox.color>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-button
            label="Add FE"
            hx-post="/fe"
        ></x-button>
    </x-slot:buttons>
</x-form>
