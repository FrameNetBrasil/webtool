<script>
    $(function() {
        annotation.drawBox.config();
    });
</script>

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
    <canvas
        id="canvas"
        width=0
        height=0
    ></canvas>
    <div id="boxesContainer">
    </div>
</div>
