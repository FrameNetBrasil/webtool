@php
    $items = [
        ['textual','Textual'],
        ['static','Static'],
        ['dynamic','Dynamic'],
    ];
    $id = uniqid("luReportMenu");
@endphp
<x-objectmenu
    id={{$id}}
    :items="$items"
    :path="'/report/lu/' . $lu->idLU"
></x-objectmenu>
<style>
    #{{$id}}_textual_tab {
        flex-grow: 1;
    }
    #{{$id}}_static_tab {
        flex-grow: 1;
    }
    #{{$id}}_dynamic_tab {
        flex-grow: 1;
    }
</style>
