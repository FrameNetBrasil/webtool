@php
$items = [
    ['editForm','Edit'],
    ['document','Documents'],
];
@endphp
<x-objectmenu
    id="sentenceMenu"
    :items="$items"
    :path="'sentence/' . $sentence->idSentence"
></x-objectmenu>
