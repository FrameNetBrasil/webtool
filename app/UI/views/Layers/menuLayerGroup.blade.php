@php
$items = [
    ['formEdit','Edit'],
];
@endphp
<x-objectmenu
    id="layerGroupMenu_{{$layerGroup->idLayerGroup}}"
    :items="$items"
    :path="'layers/layergroup/' . $layerGroup->idLayerGroup"
></x-objectmenu>
