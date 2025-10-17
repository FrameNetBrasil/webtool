@php
$items = [
    ['formEdit','Edit'],
    ['corpus','Corpus'],
];
@endphp
<x-objectmenu
    id="datasetMenu"
    :items="$items"
    :path="'dataset/' . $dataset->idDataset"
></x-objectmenu>
