<div class="wt-combobox-panel">
<div id="{{$id}}" style="position:absolute; width:100%;z-index:10;">
    {{$slot}}
</div>
</div>
@push('onload')
    $('#{{$id}}').panel({
        width: {{$width}},
        collapsible: true,
        title: '{{$label}}',
        collapsed: true
    });
@endpush