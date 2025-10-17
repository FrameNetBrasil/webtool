@php
$items = [
    ['formEdit','Edit'],
];
@endphp
<x-objectmenu
    id="groupMenu"
    :items="$items"
    :path="'group/' . $group->idGroup"
></x-objectmenu>
