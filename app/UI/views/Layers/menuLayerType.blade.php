@php
$items = [
    ['entries','Translations'],
    ['formEdit','Edit'],
];
@endphp
<x-objectmenu
    id="layerTypeMenu_{{$layerType->idLayerType}}"
    :items="$items"
    :path="'layers/layertype/' . $layerType->idLayerType"
></x-objectmenu>
