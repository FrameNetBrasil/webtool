<div class="grids flex flex-column flex-grow-1">
    <div class="ui pointing secondary menu tabs">
        <a class="item" data-tab="timeline">Timeline</a>
        <a class="item" data-tab="objects">Objects</a>
    </div>
    <div class="gridBody">
        <div
            class="ui tab timeline h-full"
            data-tab="timeline"
        >
            @include("Annotation.Deixis.Panes.timelinePane")
        </div>
        @include("Annotation.Deixis.Panes.objectsPane")
    </div>
    <script type="text/javascript">
        $(".tabs .item")
            .tab()
        ;
    </script>
</div>
