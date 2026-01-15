<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idEntity"
            :value="$idEntity"
        ></x-hidden-field>
        <div class="field">
            <x-ui::tree.semantictype
                id="idSemanticType"
                label="SemanticType"
                :baseType="$root"
            ></x-ui::tree.semantictype>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit
            label="Add Semantic Type"
            hx-post="/semanticType/{{$idEntity}}/add"
        ></x-submit>
    </x-slot:buttons>
</x-form>
