<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idEntity"
            :value="$idEntity"
        ></x-hidden-field>
        <div class="field">
            <x-combobox.semantic-type
                id="idSemanticType"
                label="Semantic Type"
                :root="$root"
                class="w-25rem"
            ></x-combobox.semantic-type>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit
            label="Add Semantic Type"
            hx-post="/semanticType/{{$idEntity}}/add"
        ></x-submit>
    </x-slot:buttons>
</x-form>
