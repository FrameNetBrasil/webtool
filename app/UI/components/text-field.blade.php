@props([
    'id' => '',
    'label' => '',
    'value' => '',
    'placeholder' => ''
])
@if($label != '')
    <label for="{{$id}}">{{$label}}</label>
@endif
<div class="ui small input">
    <input
        type="text"
        id="{{$id}}"
        name="{{$id}}"
        value="{{$value}}"
        placeholder="{{$placeholder}}"
        {{$attributes->class([])}}
    >
</div>

