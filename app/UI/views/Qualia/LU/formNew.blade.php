<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idLU"
            :value="$idLU"
        ></x-hidden-field>
        <div class="grid">
            <div class="col-6">
                <h3 class="ui violet dividing header">Qualia</h3>
                <div class="two fields">
                    <div class="field">
                        <x-combobox.qualia-relations
                            id="idQualiaRelation"
                            label="Qualia relation"
                            class="w-25rem"
                        ></x-combobox.qualia-relations>
                    </div>
                    <div class="field">
                        <div class="field">
                            <x-combobox.lu
                                id="idLURelated"
                                label="Related LU"
                                class="w-25rem"
                            ></x-combobox.lu>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit
            label="Add Qualia"
            hx-post="/lu/qualia/{{$idLU}}"
        ></x-submit>
    </x-slot:buttons>
</x-form>
