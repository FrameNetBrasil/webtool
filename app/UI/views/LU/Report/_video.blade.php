@php
$url = config('webtool.mediaURL') . "/" . $video->currentURL;
@endphp
<script>
    //player = videojs("videoContainer");
    player = window.player;
    player.src({type: 'video/mp4', src: '{{$url}}'});
    Alpine.store('doStore').setCurrentFrame({{$object->startFrame}});
    player.currentTime(Alpine.store('doStore').currentTime);

    $(".bbox").css("display", "none");
    dom = document.createElement("div");
    dom.className = "bbox";
    dom.style.position = "absolute";
    dom.style.display = "block";
    dom.style.width = "{{$bbox->width}}" + "px";
    dom.style.height = "{{$bbox->height}}" + "px";
    dom.style.left = "{{$bbox->x}}" + "px";
    dom.style.top = "{{$bbox->y}}" + "px";
    dom.style.borderColor = 'yellow';
    dom.style.borderStyle = "solid";
    dom.style.borderWidth = "4px";
    dom.style.backgroundColor = "transparent";
    dom.style.opacity = 1;
    document.querySelector("#boxesContainer").appendChild(dom);
</script>
