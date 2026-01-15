@php
    $items = [
        ['entries','Translations'],
        ['fes','Elements'],
//        ['lus','LUs'],
//        ['classification','Classification'],
//        ['relations','F-F Relations'],
//        ['feRelations','FE-FE Relations'],
//        ['semanticTypes','SemanticTypes'],
    ];
@endphp
<x-objectmenu
    id="frameMenu"
    :items="$items"
    :path="'/cluster/' . $frame->idFrame"
></x-objectmenu>
