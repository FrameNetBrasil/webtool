<x-form>
    <x-slot:fields>
        <x-hidden-field id="idConstruction" :value="$idConstruction"></x-hidden-field>
            <div class="fields">
                <div class="field">
                    <x-combobox.relation
                        id="relationType"
                        group="cxn"
                    ></x-combobox.relation>
                </div>
                <div class="field">
                    <x-combobox.construction
                        id="idCxnRelated"
                        label="Related Construction [min: 3 chars]"
                        :hasDescription="false"
                    ></x-combobox.construction>
                </div>
            </div>

    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add Relation" hx-post="/relation/cxn"></x-submit>
    </x-slot:buttons>
</x-form>
