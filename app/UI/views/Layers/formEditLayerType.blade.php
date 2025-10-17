<x-form
    hx-put="/layers/layertype"
>
    <x-slot:fields>
        <x-hidden-field id="idLayerType" value="{{$layerType->idLayerType}}"></x-hidden-field>
        <div class="fields">
            <div class="field">
                <x-combobox.layer-group
                    label="LayerGroup"
                    id="idLayerGroup"
                    :value="$layerType->idLayerGroup"
                ></x-combobox.layer-group>
            </div>
            <div class="field">
                <x-number-field
                    label="LayerOrder"
                    id="layerOrder"
                    :value="$layerType->layerOrder"
                ></x-number-field>
            </div>
        </div>
        <div class="fields">
            <div class="field">
                <x-checkbox
                    label="Allows Apositional"
                    id="allowsApositional"
                    :active="$layerType->allowsApositional"
                ></x-checkbox>
            </div>
            <div class="field">
                <x-checkbox
                    label="Is Annotation"
                    id="isAnnotation"
                    :active="$layerType->isAnnotation"
                ></x-checkbox>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Save"></x-submit>
    </x-slot:buttons>
</x-form>
