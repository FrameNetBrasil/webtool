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
<form id="formNewImage" class="ui form" hx-encoding='multipart/form-data'>
    <input type="hidden" name="idImage" value="{{$image->idImage}}">
    <input type="hidden" name="idDocument" value="{{$idDocument}}">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <div class="header">
                Update Image
            </div>
            <div class="description">

            </div>
        </div>
        <div class="content">
            <div class="field">
                <x-file-field
                    id="file"
                    label="File"
                    value=""
                >
                </x-file-field>
            </div>

            <div class="field">
                <progress id='progress' value='0' max='100'></progress>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/image"
            >
                Save
            </button>
        </div>
    </div>
</form>
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
