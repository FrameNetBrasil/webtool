<div class="form-field field">
    <label for="{{$id}}">{{$label}}</label>
        <textarea
            id="{{$id}}"
            name="{{$id}}"
            placeholder="{{$placeholder}}"
            {{$attributes->class(["w-full"])}}
            rows="{{$rows}}"
        >{{$value}}</textarea>
</div>
