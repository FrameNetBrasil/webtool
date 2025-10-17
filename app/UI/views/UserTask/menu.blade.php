@php
$items = [
    ['documents','Documents'],
];
@endphp
<x-objectmenu
    id="usertaskMenu_{{$usertask->idUserTask}}"
    :items="$items"
    :path="'usertask/' . $usertask->idUserTask"
></x-objectmenu>
