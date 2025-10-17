@php
$items = [
    ['formEdit','Edit'],
];
$id = uniqid($luCandidate->idLU);
@endphp
<x-objectmenu
    id="luCandidateMenu_{{$id}}"
    :items="$items"
    :path="'luCandidate/' . $luCandidate->idLU"
></x-objectmenu>
