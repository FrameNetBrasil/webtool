@php
    $originalWidth = intval($image->width);
    $originalHeight = intval($image->height);
    $canvasWidth = 860;
    $canvasHeight = 800;
    $scaleWidth = $canvasWidth / $originalWidth;
    $scaleHeight = $canvasHeight / $originalHeight;
    $scale = ($scaleHeight < $scaleWidth) ? $scaleHeight : $scaleWidth;
    $imageWidth = intval($originalWidth * $scale);
    $imageHeight = intval($originalHeight * $scale);
    debug("original width: ". $originalWidth);
    debug("original height: ". $originalHeight);
    debug("canvas width: ". $canvasWidth);
    debug("canvas height: ". $canvasHeight);
    debug("scale width: ". $scaleWidth);
    debug("scale height: ". $scaleHeight);
    debug("scale: ". $scale);
    debug("image width: ". $imageWidth);
    debug("image height: ". $imageHeight);
@endphp
<div class="annotation-controls">
    <div class="d-flex justify-between items-center">
        <div>
            <div class="ui label">
            Scale: {!! number_format($scale,6) !!}
            </div>
        </div>
        <div class="mt-1">
            <button
                id="btnShowHideObjects"
                class="ui toggle small button secondary"
                x-data @click="$dispatch('bbox-toggle-show')"
            >
                Show/Hide All
            </button>
        </div>
    </div>
</div>
<div
    class="annotation-image"
    style="width:{{$imageWidth}}px;height:{{$imageHeight}}px;"
>
    <img
        alt="{{$image->name}}"
        width="{{$imageWidth}}"
        height="{{$imageHeight}}"
        id="imageContainer"
        src="{!! config('webtool.mediaURL') . "/" . $image->currentURL !!}"
    >
    <canvas
        id="canvas"
        width="{{$imageWidth}}"
        height="{{$imageHeight}}"
        style="position: absolute; top: 0; left: 0; background-color: transparent; z-index: 1;"
    ></canvas>
    @include("Annotation.Image.Panes.bbox")
</div>
