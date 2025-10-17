<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idFrameElement"
            :value="$idFrameElement"
        ></x-hidden-field>
        <div class="grid">
            <div class="col-6">
                <h3 class="ui violet dividing header">Frame</h3>
                <div class="field">
                    <x-combobox.frame
                        id="idFrameConstraint"
                        label="Frame [min 3 chars]"
                        class="w-25rem"
                    ></x-combobox.frame>
                </div>
            </div>
            <div class="col-6">
                <h3 class="ui violet dividing header">Qualia</h3>
                <div class="two fields">
                    <div class="field">
                        <x-combobox.qualia-relations
                            id="idQualiaConstraint"
                            label="Qualia relation"
                            class="w-25rem"
                        ></x-combobox.qualia-relations>
                    </div>
                    <div class="field">
                        <x-combobox.fe-frame
                            id="idFEQualiaConstraint"
                            label="Related FE"
                            :idFrame="$frameElement->idFrame"
                            class="w-25rem"
                        ></x-combobox.fe-frame>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <h3 class="ui violet dividing header">Metonym-FE</h3>
                <div class="field">
                    <x-combobox.fe-frame
                        id="idFEMetonymConstraint"
                        label="Related FE"
                        :idFrame="$frameElement->idFrame"
                        class="w-25rem"
                    ></x-combobox.fe-frame>
                </div>
            </div>
            <div class="col-6">
                <h3 class="ui violet dividing header">Metonym-LU</h3>
                <div class="field">
                    <x-combobox.lu-frame
                        id="idLUMetonymConstraint"
                        label="Related LU"
                        :idFrame="$frameElement->idFrame"
                        class="w-25rem"
                    ></x-combobox.lu-frame>
                </div>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit
            label="Add Constraint"
            hx-post="/constraint/fe/{{$idFrameElement}}"
        ></x-submit>
    </x-slot:buttons>
</x-form>
