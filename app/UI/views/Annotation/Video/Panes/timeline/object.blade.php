@php
    $bgColor = $objectData->bgColor;
    $fgColor = $objectData->fgColor;
    $label = $objectData->name;
    $tooltip = "#" . $objectData->idObject . ": " . $label . "\nFrames: " . $objectData->startFrame . "-" . $objectData->endFrame . "\nDuration: " . $duration . " frames";
    if ($objectData->comment) {
        if (trim($objectData->comment->comment) != '') {
            $label = "*" . $label;
        }
    }
@endphp
<div
    data-id="{{$objectData->idObject}}"
    class="internal"
    id="o{{$objectData->idObject}}"
    style="background-color: {{ $bgColor }};color: {{$fgColor}}"
    title="{{ $tooltip }}"
    @click.prevent="onClickObject({{$objectData->idObject}})"
>
    {{ $label }}
</div>
