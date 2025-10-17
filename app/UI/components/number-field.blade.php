<label for="{{$id}}">{{$label}}</label>
<div class="ui small input">
    <input
        type="number"
        id="{{$id}}"
        name="{{$id}}"
        value="{{$value}}"
        placeholder="{{$placeholder}}"
        {{$attributes->class([])}}
    >
</div>

