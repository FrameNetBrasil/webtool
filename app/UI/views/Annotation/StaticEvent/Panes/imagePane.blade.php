@php
    $imageURL = config('webtool.mediaURL') . "/" . $image->currentURL;
    $imageWidth = $image->width;
    $imageHeight = $image->height;

@endphp
    <div style="display:flex; flex-direction: column; width:auto">
        <div id="image" style="width: {{$imageWidth}}px;height: {{$imageHeight}}px;">
            <img src="{{$imageURL}}" width="{{$imageWidth}}" height="{{$imageHeight}}">
        </div>
    </div>

