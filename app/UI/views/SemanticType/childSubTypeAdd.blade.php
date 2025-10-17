<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idEntity"
            :value="$idEntity"
        ></x-hidden-field>
        <div class="field">
            <x-combobox.semantic-type-list
                id="idSemanticType"
                label="SubType"
                value=""
            ></x-combobox.semantic-type-list>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit
            label="Add SubType"
            hx-post="/semanticType/{{$idEntity}}/addSubType"
        ></x-submit>
    </x-slot:buttons>
</x-form>
