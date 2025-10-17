@php
$items = [
    ['entries','Translations'],
    ['subTypes','SubTypes'],
];
@endphp
<x-objectmenu
    id="semanticTypeMenu_{{$semanticType->idSemanticType}}"
    :items="$items"
    :path="'semanticType/' . $semanticType->idSemanticType"
></x-objectmenu>
