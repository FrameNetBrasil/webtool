<div
    x-data="boxComponent('videoContainer_html5_api')"
    @disable-drawing.document="onDisableDrawing"
    @enable-drawing.document="onEnableDrawing"
    @bbox-create.document="onBBoxCreate"
    @bbox-created.document="onBBoxCreated"
    @bbox-change-blocked.document="onBBoxChangeBlocked"
    @video-update-state.document="onVideoUpdateState"
    @auto-tracking-start.document="onStartTracking"
    @auto-tracking-stop.document="onStopTracking"
    @object-loaded.document="onObjectLoaded"
    id="boxesContainer"
    style="position: absolute; top: 0; left: 0; width:852px; height:480px; background-color: transparent"
    hx-swap-oob="true"
>
    <div
        class="bbox" style="display:none"
    >
        <div class="objectId"></div>
    </div>
</div>
