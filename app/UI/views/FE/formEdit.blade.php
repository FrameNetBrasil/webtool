<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idFrameElement"
            :value="$frameElement->idFrameElement"
        ></x-hidden-field>
        <div class="two fields">
            <div class="field max-w-15rem">
                <x-combobox.fe-coreness
                    id="coreTypeEdit"
                    label="Coreness"
                    :value="$frameElement->coreType"
                ></x-combobox.fe-coreness>
            </div>
            <div class="field  max-w-15rem">
                <x-combobox.color
                    id="idColorEdit"
                    label="Color"
                    :value="$frameElement->idColor"
                ></x-combobox.color>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit
            label="Save"
            hx-put="/fe/{{$frameElement->idFrameElement}}"
        ></x-submit>
    </x-slot:buttons>
</x-form>

