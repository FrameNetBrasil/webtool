@php
    $icon = $icon ?? config("webtool.fe.icon")[$type]
@endphp
<div class="d-flex justify-left items-center">
    <div><i class="{{$icon}} icon"></i></div>
    <div class="color_{{$idColor}}" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;padding: 0 2px;">{{$name}}</div>
</div>

