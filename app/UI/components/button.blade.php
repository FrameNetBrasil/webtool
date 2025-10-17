<button
    {{$attributes->merge(['class' => 'ui medium ' . $color .' button'])}}
    {{$attributes}}
>
    @if($icon != '')
        <i class="icon material">{{$icon}}</i>
    @endif
    {{$label}}
    {{$slot}}
</button>
