@php
$sections = [['/','Home'],['/annotation','Annotation']];
if ($annotationType == 'dynamicMode') {
    $sections[] = ['/annotation/dynamicMode','Dynamic'];
} elseif ($annotationType == 'deixis') {
    $sections[] = ['/annotation/deixis','Deixis'];
}
$sections[] = ['',$document->name];
@endphp
<x-layout::index>
{{--    <script src="/scripts/utils/jquery.parser.js"></script>--}}
{{--    <script src="/scripts/utils/jquery.draggable.js"></script>--}}
{{--    <script src="/scripts/utils/jquery.resizable.js"></script>--}}
    {{-- Working vatic library from webtool41 --}}
    <script type="text/javascript" src="/scripts/vatic/dist/compatibility.js"></script>
    <script type="text/javascript" src="/scripts/vatic/dist/jsfeat.js"></script>
    <script type="text/javascript" src="/scripts/vatic/dist/nudged.js"></script>
    <script type="text/javascript" src="/scripts/vatic/dist/polyfill.js"></script>
    <script type="text/javascript" src="/scripts/vatic/vatic.js"></script>
    <script type="text/javascript" src="/scripts/vatic/FramesManager.js"></script>
    <script type="text/javascript" src="/scripts/vatic/OpticalFlow.js"></script>
    <script type="text/javascript" src="/scripts/vatic/BoundingBox.js"></script>
    <script type="text/javascript" src="/scripts/vatic/DynamicObject.js"></script>
    <script type="text/javascript" src="/scripts/vatic/ObjectsTracker.js"></script>
    {{-- Custom inline implementations (commented out - now using vatic library) --}}
    {{-- <script type="text/javascript" src="/annotation/video/script/objects"></script> --}}
    <script type="text/javascript" src="/annotation/video/script/components"></script>
    <div class="app-layout annotation-video">
        <x-partial::breadcrumb
            :sections="$sections"
        ></x-partial::breadcrumb>
        <div class="annotation-canvas">
            <div class="annotation-video">
                <div class="annotation-player">
                @include("Annotation.Video.Panes.video")
                </div>
                <div class="annotation-forms">
                    @include("Annotation.Video.Panes.forms")
                </div>
            </div>
            <div class="annotation-objects">
                @include("Annotation.Video.Panes.grids")
            </div>
        </div>
    </div>
</x-layout::index>
