<div class="form-field field">
    <label for="{{$id}}">{{$label}}</label>
    <div class="ui small file input">
        <input
            type="file"
            id="{{$id}}"
            name="{{$id}}"
            value="{{$value}}"
            placeholder="{{$placeholder}}"
            {{$attributes->class([])}}
        >
    </div>
</div>

