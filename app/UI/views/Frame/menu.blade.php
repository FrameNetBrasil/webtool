@php
    $items = [
        ['entries','Translations'],
        ['fes','FrameElements'],
        ['lus','LUs'],
        ['classification','Classification'],
        ['relations','F-F Relations'],
        ['feRelations','FE-FE Relations'],
        ['semanticTypes','SemanticTypes'],
    ];
@endphp
<x-objectmenu
    id="frameMenu"
    :items="$items"
    :path="'/frame/' . $frame->idFrame"
></x-objectmenu>
