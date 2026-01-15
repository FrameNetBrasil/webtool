@php(debug($object))
<x-combobox.gl
    id="idGenericLabelNew"
    name="idGenericLabelNew"
    label="Label"
    :value="$object?->idGenericLabel ?? 0"
    :idLayerType="$object?->idLayerType ?? 0"
    :hasNull="false"
></x-combobox.gl>
