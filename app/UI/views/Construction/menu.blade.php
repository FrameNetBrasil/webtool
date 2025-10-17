@php
    $items = [
        ['entries','Translations'],
        ['ces','ConstructionElements'],
        ['relations','Relations'],
        ['constraints','Constraints'],
    ];
@endphp
<x-objectmenu
    id="cxnMenu"
    :items="$items"
    :path="'/cxn/' . $cxn->idConstruction"
></x-objectmenu>
