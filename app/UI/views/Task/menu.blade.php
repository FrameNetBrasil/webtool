@php
$items = [
    ['formEdit','Edit'],
    ['users','Users'],
];
@endphp
<x-objectmenu
    id="taskMenu"
    :items="$items"
    :path="'task/' . $task->idTask"
></x-objectmenu>
