<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idConstructionElement"
            :value="$constructionElement->idConstructionElement"
        ></x-hidden-field>
        <div class="field  max-w-15rem">
            <x-combobox.color
                id="idColorEdit"
                label="Color"
                :value="$constructionElement->idColor"
            ></x-combobox.color>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit
            label="Save"
            hx-put="/ce/{{$constructionElement->idConstructionElement}}"
        ></x-submit>
    </x-slot:buttons>
</x-form>

