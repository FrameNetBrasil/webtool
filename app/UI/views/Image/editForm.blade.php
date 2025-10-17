@php
    $originalWidth = intval($image->width);
    $originalHeight = intval($image->height);
    debug($originalWidth,$originalHeight);
    $canvasWidth = 540;
    $canvasHeight = 400;
    $scaleWidth = $canvasWidth / $originalWidth;
    $scaleHeight = $canvasHeight / $originalHeight;
    $scaleReduce = ($scaleHeight < $scaleWidth) ? $scaleHeight : $scaleWidth;
    $imageWidth = intval($originalWidth * $scaleReduce);
    $imageHeight = intval($originalHeight * $scaleReduce);
@endphp

<x-form
    title="Edit image"
    hx-post="/image"
>
    <x-slot:fields>
        <x-hidden-field
            id="idImage"
            :value="$image->idImage"
        ></x-hidden-field>
        <div class="formgrid grid">
            <div class="field col">
                <x-text-field
                    label="Name"
                    id="name"
                    :value="$image->name"
                ></x-text-field>
            </div>
            <div class="field col">
                <x-text-field
                    label="Current URL"
                    id="currentURL"
                    :value="$image->currentURL"
                ></x-text-field>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Save"></x-submit>
    </x-slot:buttons>
</x-form>
<div
    style="position:relative;width:{{$canvasWidth}}px;height:{{$canvasHeight}}px;"
>
    <image
        id="imageStaticBBox"
        width="{{$imageWidth}}"
        height="{{$imageHeight}}"
        id="imageContainer"
        src="{!! config('webtool.mediaURL') . "/" . $image->currentURL !!}"
    >
    </image>
</div>
