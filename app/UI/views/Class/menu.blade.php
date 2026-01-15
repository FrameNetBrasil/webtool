@php
    $items = [
        ['entries','Translations'],
        ['fes','FrameElements'],
        ['relations','C-C Relations'],
        ['feRelations','FE-FE Relations'],
        ['semanticTypes','SemanticTypes'],
    ];
@endphp
<x-objectmenu
    id="frameMenu"
    :items="$items"
    :path="'/class/' . $frame->idFrame"
></x-objectmenu>
