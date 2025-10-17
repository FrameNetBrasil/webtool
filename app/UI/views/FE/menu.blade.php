@php
    $items = [
        ['formEdit','Edit'],
        ['entries','Translations'],
        ['constraints','Constraints'],
        ['semanticTypes','SemanticTypes'],
    ];
@endphp
<x-objectmenu
    id="feMenu"
    :items="$items"
    :path="'/fe/' . $frameElement->idFrameElement"
></x-objectmenu>
