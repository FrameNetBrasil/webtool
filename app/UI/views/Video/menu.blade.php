@php
$items = [
    ['formEdit','Edit'],
    ['document','Documents'],
    ['formUpload','Upload'],
];
@endphp
<x-objectmenu
    id="videoMenu_{{$video->idVideo}}"
    :items="$items"
    :path="'video/' . $video->idVideo"
></x-objectmenu>
