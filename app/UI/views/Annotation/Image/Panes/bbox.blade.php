<div
    x-data="boxComponent({{$idDocument}},{{$scale}}, {!! Js::from($bboxes) !!})"
    @disable-drawing.document="onDisableDrawing"
    @enable-drawing.document="onEnableDrawing"
    @bbox-create.document="onBBoxCreate"
    @bbox-created.document="onBBoxCreated"
    @bbox-change-blocked.document="onBBoxChangeBlocked"
    @bbox-toggle-show.document="onBBoxToggleShow"
    @object-loaded.document="onObjectLoaded"
    id="boxesContainer"
    style="position: absolute; top: 0; left: 0; width:{{$imageWidth}}px; height:{{$imageHeight}}px; background-color: transparent"
    hx-swap-oob="true"
>
    @foreach($bboxes as $bbox)
    <div
        id="bbox_{{$bbox->idBoundingBox}}" class="bbox" style="display:none"
    >

        <div class="objectId"></div>
    </div>
        @endforeach
</div>
