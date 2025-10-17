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
    <script type="text/javascript" src="/annotation/video/script/objects"></script>
    <script type="text/javascript" src="/annotation/video/script/components"></script>
    <div class="app-layout annotation-video">
        <x-layout::breadcrumb
            :sections="$sections"
        ></x-layout::breadcrumb>
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
