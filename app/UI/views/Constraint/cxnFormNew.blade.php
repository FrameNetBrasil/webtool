<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idConstruction"
            :value="$idConstruction"
        ></x-hidden-field>
        <div id="tabConstraints" class="ui secondary pointing menu">
            <div
                class="item cursor-pointer"
                data-tab="dtEvokes"
            >Evokes
            </div>
        </div>
        <div class="ui tab active" data-tab="dtEvokes">
            <div class="grid">
                <div class="col-2">
                    <h3 class="ui violet dividing header">Evokes Frame</h3>
                    <div class="field">
                        <x-combobox.frame
                            id="idFrameConstraint"
                            label="Frame [min 3 chars]"
                            class="w-25rem"
                            :hasDescription="false"
                        ></x-combobox.frame>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">Evokes Concept</h3>
                    <div class="field">
                        <x-combobox.concept
                            id="idConceptConstraint"
                            label="Concept"
                            class="w-25rem"
                        ></x-combobox.concept>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $(function() {
                $("#tabConstraints .item").tab({});
            });
        </script>

    </x-slot:fields>
    <x-slot:buttons>
        <x-submit
            label="Add Constraint"
            hx-post="/constraint/cxn/{{$idConstruction}}"
        ></x-submit>
    </x-slot:buttons>
</x-form>
