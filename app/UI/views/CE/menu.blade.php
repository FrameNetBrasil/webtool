@php
    $items = [
        ['formEdit','Edit'],
        ['entries','Translations'],
        ['constraints','Constraints'],
        ['semanticTypes','SemanticTypes'],
    ];
@endphp
<x-objectmenu
    id="ceMenu"
    :items="$items"
    :path="'/ce/' . $constructionElement->idConstructionElement"
></x-objectmenu>
