@php
$items = [
    ['formEdit','Edit'],
];
@endphp
<x-objectmenu
    id="userMenu"
    :items="$items"
    :path="'user/' . $user->idUser"
></x-objectmenu>
