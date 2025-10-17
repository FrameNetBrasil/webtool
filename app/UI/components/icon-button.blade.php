<button
    {{$attributes->merge(['class' => 'ui  ' . $color .' icon button'])}}
>
    @if($icon != '')
        <i class="icon material">{{$icon}}</i>
    @endif
</button>
