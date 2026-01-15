@php
    $items = [
        ['entries','Translations'],
        ['fes','Domain/Range'],
//        ['lus','LUs'],
//        ['classification','Classification'],
        ['relations','F-F Relations'],
//        ['feRelations','FE-FE Relations'],
        ['semanticTypes','SemanticTypes'],
    ];
@endphp
<x-objectmenu
    id="frameMenu"
    :items="$items"
    :path="'/microframe/' . $frame->idFrame"
></x-objectmenu>
