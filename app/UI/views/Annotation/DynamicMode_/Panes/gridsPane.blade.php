<div class="grids flex flex-column flex-grow-1" style="width:1024px;">
    <div class="ui pointing secondary menu tabs">
        <a class="item" data-tab="objects">Objects</a>
        <a class="item" data-tab="sentences">Sentences</a>
    </div>
    <div class="gridBody">
        @include("Annotation.DynamicMode.Panes.objectsPane")
        <div
            class="ui tab sentences"
            data-tab="sentences"
            hx-trigger="load"
            hx-get="/annotation/dynamicMode/sentences/{{$idDocument}}"
        >
            sentences
        </div>
    </div>
    <script type="text/javascript">
        $(".tabs .item")
            .tab()
        ;
    </script>
</div>
