<x-form
    hx-put="/relations/relationtype"
>
    <x-slot:fields>
        <x-hidden-field id="idRelationType" value="{{$relationType->idRelationType}}"></x-hidden-field>
        <div class="fields">
            <div class="field">
                <x-combobox.relation-group
                    label="RelationGroup"
                    id="idRelationGroup"
                    :value="$relationType->idRelationGroup"
                ></x-combobox.relation-group>
            </div>
        </div>
        <div class="three fields">
            <div class="field">
                <x-text-field
                    label="Canonical name"
                    id="nameCanonical"
                    :value="$relationType->nameCanonical"
                ></x-text-field>
            </div>
            <div class="field">
                <x-text-field
                    label="Direct name"
                    id="nameDirect"
                    :value="$relationType->nameDirect"
                ></x-text-field>
            </div>
            <div class="field">
                <x-text-field
                    label="Inverse name"
                    id="nameInverse"
                    :value="$relationType->nameInverse"
                ></x-text-field>
            </div>
        </div>
        <div class="fields">
            <div class="field">
                <x-color-field
                    label="Color"
                    id="color"
                    :value="$relationType->color"
                ></x-color-field>
            </div>
            <div class="field">
                <x-text-field
                    label="Prefix (exact 3 chars)"
                    id="prefix"
                    :value="$relationType->prefix"
                ></x-text-field>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Save"></x-submit>
    </x-slot:buttons>
</x-form>
