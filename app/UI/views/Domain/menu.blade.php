@php
$items = [
    ['entries','Translations'],
];
@endphp
<x-objectmenu
    id="domainMenu"
    :items="$items"
    :path="'domain/' . $domain->idDomain"
></x-objectmenu>
