<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idConstructionElement"
            :value="$idConstructionElement"
        ></x-hidden-field>
        <div id="tabConstraints" class="ui secondary pointing menu">
            <div
                class="item cursor-pointer"
                data-tab="dtEvokes"
            >Evokes
            </div>
            <div
                class="item cursor-pointer"
                data-tab="dtStructure"
            >Structure
            </div>
            <div
                class="item cursor-pointer"
                data-tab="dtLexicon"
            >Lexicon
            </div>
            <div
                class="item cursor-pointer"
                data-tab="dtPosition"
            >Position
            </div>
            <div
                class="item cursor-pointer"
                data-tab="dtUD"
            >UD
            </div>
            <div
                class="item cursor-pointer"
                data-tab="dtFeatures"
            >Features
            </div>

        </div>
        <div class="ui tab active" data-tab="dtEvokes">
            <div class="grid">
                <div class="col-2">
                    <h3 class="ui violet dividing header">Evokes FE</h3>
                    <div class="field">
                        <x-combobox.fe-frame
                            id="idFEConstraint"
                            label="FE"
                            style="width:250px"
                            :value="0"
                            :idFrame="$idEvokedFrame"
                            :hasNull="true"
                        ></x-combobox.fe-frame>
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
        <div class="ui tab " data-tab="dtStructure">
            <div class="grid">
                <div class="col-2">
                    <h3 class="ui violet dividing header">Construction</h3>
                    <div class="field">
                        <x-combobox.construction
                            id="idConstructionConstraint"
                            label="Construction [min 3 chars]"
                            :value="0"
                            :hasDescription="false"
                            class="w-25rem"
                        ></x-combobox.construction>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">Frame</h3>
                    <div class="field">
                        <x-combobox.frame
                            id="idFrameConstraint"
                            label="Frame [min 3 chars]"
                            class="w-25rem"
                        ></x-combobox.frame>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">Frame Family</h3>
                    <div class="field">
                        <x-combobox.frame
                            id="idFrameFamilyConstraint"
                            label="Frame [min 3 chars]"
                            class="w-25rem"
                        ></x-combobox.frame>
                    </div>
                </div>

            </div>
        </div>
        <div class="ui tab " data-tab="dtLexicon">
            <div class="grid">
                <div class="col-2">
                    <h3 class="ui violet dividing header">LU</h3>
                    <div class="field">
                        <x-combobox.lu
                            id="idLUConstraint"
                            label="LU"
                            class="w-25rem"
                        ></x-combobox.lu>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">Lemma</h3>
                    <div class="field">
                        <x-combobox.lexicon-lemma
                            id="idLemmaConstraint"
                            label="Lemma"
                            class="w-25rem"
                        ></x-combobox.lexicon-lemma>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">Wordform</h3>
                    <div class="field">
                        <x-combobox.lexicon-expression
                            id="idWordFormConstraint"
                            label="Wordform"
                            class="w-25rem"
                        ></x-combobox.lexicon-expression>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">Morpheme</h3>
                    <div class="field">
                        <x-combobox.lexicon-morpheme
                            id="idMorphemeConstraint"
                            :idLanguage="$constructionElement->cxn->cxIdLanguage"
                            label="Morpheme"
                            class="w-25rem"
                        ></x-combobox.lexicon-morpheme>
                    </div>
                </div>

            </div>
        </div>
        <div class="ui tab " data-tab="dtUD">
            <div class="grid">
                <div class="col-2">
                    <h3 class="ui violet dividing header">UD Relation</h3>
                    <div class="field">
                        <x-combobox.ud-relation
                            id="idUDRelationConstraint"
                            label="UD Relation"
                            :value="0"
                            class="w-25rem"
                        ></x-combobox.ud-relation>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">UD Feature</h3>
                    <div class="field">
                        <x-combobox.ud-feature
                            id="idUDFeatureConstraint"
                            label="UD Feature"
                            :value="0"
                            class="w-25rem"
                        ></x-combobox.ud-feature>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">UD POS</h3>
                    <div class="field">
                        <x-combobox.ud-pos
                            id="idUDPOSConstraint"
                            label="UD POS"
                            :value="0"
                            class="w-25rem"
                        ></x-combobox.ud-pos>
                    </div>
                </div>
            </div>
        </div>
        <div class="ui tab " data-tab="dtPosition">
            <div class="grid">
                <div class="col-2">
                    <h3 class="ui violet dividing header">Before</h3>
                    <div class="field">
                        <x-combobox.ce-cxn
                            id="idBeforeCEConstraint"
                            label="Before"
                            :idConstruction="$constructionElement->idConstruction"
                            class="w-25rem"
                        ></x-combobox.ce-cxn>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">After</h3>
                    <div class="field">
                        <x-combobox.ce-cxn
                            id="idAfterCEConstraint"
                            label="After"
                            :idConstruction="$constructionElement->idConstruction"
                            class="w-25rem"
                        ></x-combobox.ce-cxn>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">Meets</h3>
                    <div class="field">
                        <x-combobox.ce-cxn
                            id="idMeetsCEConstraint"
                            label="Meets"
                            :idConstruction="$constructionElement->idConstruction"
                            class="w-25rem"
                        ></x-combobox.ce-cxn>
                    </div>
                </div>
            </div>
        </div>
        <div class="ui tab " data-tab="dtFeatures">
            <div class="grid">
                <div class="col-2">
                    <h3 class="ui violet dividing header">Index Gender CE</h3>
                    <div class="field">
                        <x-combobox.ce-cxn
                            id="idIndexGenderCEConstraint"
                            label="Index Gender"
                            :idConstruction="$constructionElement->idConstruction"
                            class="w-25rem"
                        ></x-combobox.ce-cxn>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">Index Person CE</h3>
                    <div class="field">
                        <x-combobox.ce-cxn
                            id="idIndexPersonCEConstraint"
                            label="Index Person"
                            :idConstruction="$constructionElement->idConstruction"
                            class="w-25rem"
                        ></x-combobox.ce-cxn>
                    </div>
                </div>
                <div class="col-2">
                    <h3 class="ui violet dividing header">Index Number CE</h3>
                    <div class="field">
                        <x-combobox.ce-cxn
                            id="idIndexNumberCEConstraint"
                            label="Index Number"
                            :idConstruction="$constructionElement->idConstruction"
                            class="w-25rem"
                        ></x-combobox.ce-cxn>
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
            hx-post="/constraint/ce/{{$idConstructionElement}}"
        ></x-submit>
    </x-slot:buttons>
</x-form>
