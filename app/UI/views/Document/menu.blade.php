@php
$items = [
    ['entries','Translations'],
    ['formCorpus','Corpus'],
];
@endphp
<x-objectmenu
    id="documentMenu"
    :items="$items"
    :path="'/document/' . $document->idDocument"
></x-objectmenu>
