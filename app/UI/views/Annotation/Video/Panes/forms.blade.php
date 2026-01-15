<div
    id="formsPane"
    x-data="formsComponent({{$idDocument}})"
    @video-update-state.document="onVideoUpdateState"
    @bbox-toggle-tracking.document="onBBoxToggleTracking"
    @bbox-drawn.document="onBBoxDrawn"
    @bbox-update.document="onBBoxUpdate"
>
    @if ($idObject == 0)
        @include("Annotation.Video.Forms.formNewObject")
        @if (($frameNumber ?? 0) > 0)
            <script type="text/javascript">
                $(function() {
                    document.dispatchEvent(new CustomEvent("video-seek-frame", { detail: { frameNumber: {{$frameNumber}} }}));
                });
            </script>
        @endif
    @else
        <div
            hx-trigger="load"
            hx-target="#formsPane"
            hx-get="/annotation/video/object"
            hx-vals='{"idObject": {{$idObject}},"idDocument": {{$idDocument}}, "annotationType":"{{$annotationType}}","frameNumber": {{$frameNumber}}}'
            hx-swap="innerHTML"
        ></div>
    @endif
</div>

