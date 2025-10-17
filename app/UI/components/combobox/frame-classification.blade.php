@if($placeholder == '')
    <div class="form-field">
        <label for="{{$id}}">{{$label}}</label>
        <input {{$attributes}} id="{{$id}}" name="{{$id}}">
    </div>
@else
    <input {{$attributes}} id="{{$id}}" name="{{$id}}">
@endif
@push('onload')
    $('#{{$id}}').combobox({
        valueField: 'value',
        textField: 'text',
        editable:false,
        prompt: '{{$placeholder}}',
        data: {{ Js::from($options) }},
    });
@endpush