@php
$items = [
    ['entries','Translations'],
    ['formEdit','Edit'],
];
@endphp
<x-objectmenu
    id="relationTypeMenu_{{$relationType->idRelationType}}"
    :items="$items"
    :path="'relations/relationtype/' . $relationType->idRelationType"
></x-objectmenu>
