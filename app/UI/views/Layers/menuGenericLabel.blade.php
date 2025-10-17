@php
$items = [
    ['formEdit','Edit'],
];
@endphp
<x-objectmenu
    id="genericLabelMenu_{{$genericLabel->idGenericLabel}}"
    :items="$items"
    :path="'layers/genericlabel/' . $genericLabel->idGenericLabel"
></x-objectmenu>
