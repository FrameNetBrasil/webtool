@php
$items = [
    ['formEdit','Edit'],
    ['datasets','Datasets'],
    ['users','Managers'],
];
@endphp
<x-objectmenu
    id="projectMenu"
    :items="$items"
    :path="'project/' . $project->idProject"
></x-objectmenu>
