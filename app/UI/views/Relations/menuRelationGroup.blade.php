@php
$items = [
    ['entries','Translation'],
];
@endphp
<x-objectmenu
    id="relationGroupMenu_{{$relationGroup->idRelationGroup}}"
    :items="$items"
    :path="'relations/relationgroup/' . $relationGroup->idRelationGroup"
></x-objectmenu>
