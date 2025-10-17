<a href="{{$href}}">
<button
    class = "ui medium {{$color}} button"
>
    @if($icon != '')
        <i class="icon material">{{$icon}}</i>
    @endif
    {{$label}}
    {{$slot}}
</button>
</a>
