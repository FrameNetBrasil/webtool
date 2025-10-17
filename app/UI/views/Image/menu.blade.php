@php
$items = [
    ['editForm','Edit'],
    ['dataset','Datasets'],
    ['document','Documents'],
];
$id = uniqid("imageMenu")
@endphp
<x-objectmenu
    id="{{$id}}"
    :items="$items"
    :path="'image/' . $image->idImage"
></x-objectmenu>
