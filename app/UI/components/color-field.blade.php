<label for="{{$id}}">{{$label}}</label>
<div class="ui small input">
    <input
        type="color"
        id="{{$id}}"
        name="{{$id}}"
        value="{{$value}}"
        placeholder="{{$placeholder}}"
        {{$attributes->class([])}}
    >
</div>

