<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idConstruction"
            :value="$idConstruction"
        ></x-hidden-field>
        <div class="two fields">
            <div class="field">
                <x-text-field
                    id="nameEn"
                    label="English Name"
                    value=""
                ></x-text-field>
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
            label="Add CE"
            hx-post="/ce"
        ></x-button>
    </x-slot:buttons>
</x-form>
