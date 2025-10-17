<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idEntityRelation"
            :value="$idEntityRelation"
        ></x-hidden-field>
        <div class="fields">
            <div class="field w-20rem">
                <x-combobox.ce-cxn
                    id="idConstructionElement"
                    :idConstruction="$cxn->idConstruction"
                    label="{{$cxn->name}}.CE"
                ></x-combobox.ce-cxn>
            </div>
            <div class="color_{{$relation->entry}} w-auto mr-2">{{$relation->name}}</div>
            <div class="field w-20rem">
                <x-combobox.ce-cxn
                    id="idConstructionElementRelated"
                    :idConstruction="$relatedCxn->idConstruction"
                    label="{{$relatedCxn->name}}.CE"
                ></x-combobox.ce-cxn>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add Relation" hx-post="/relation/ce"></x-submit>
    </x-slot:buttons>
</x-form>
