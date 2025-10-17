@php
$items = [
    ['entries','Translations'],
];
@endphp
<x-objectmenu
    id="corpusMenu"
    :items="$items"
    :path="'/corpus/' . $corpus->idCorpus"
></x-objectmenu>
