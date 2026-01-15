@use('App\Data\SemanticType\SearchData')
@use('App\Services\SemanticType\BrowseService')

@props([
    'label' => '',
    'id' => '',
    'value' => '',
    'displayValue' => '',
    'baseType' => '',
])

@php
    $search = new SearchData(semanticType: $baseType);
    $data = BrowseService::browseSemanticTypeBySearch($search);
@endphp

<input type="hidden" id="{{$id}}" name="{{$id}}" value="{{$value}}">
<x-text-field
    id="{{$id}}_text"
    label="{{$label}}"
    value="{{$displayValue}}">
</x-text-field>
<div
    x-data
    class="w-full"
    @tree-item-selected.document="(event) => {
        $('#{{$id}}').val(event.detail.id);
        $('#{{$id}}_text').val(event.detail.item.name);
    }"
>
    <div class="tree-field">
        <x-ui::tree
            title=""
            url="/semanticType/browse/search"
            :data="$data"
        ></x-ui::tree>
    </div>
</div>
