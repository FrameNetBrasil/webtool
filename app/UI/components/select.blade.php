<select {{$attributes}} id="{{$id}}" name="{{$id}}">
    {{$slot}}
</select>
@push('onload')
    $('#{{$id}}').combobox({
        editable:false,
    })
@endpush