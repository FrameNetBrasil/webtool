<div class="ui pointing secondary menu tabs">
    <a
        class="item active"
        data-tab="timeline"
    >Timeline</a>
    <a
        class="item"
        data-tab="objects"
    >Objects</a>
    <a
        class="item"
        data-tab="sentences"
    >Sentences</a>
</div>
<div class="gridBody">
    <div
        id="timelinePane"
        class="ui tab timelinePane h-full w-full active"
        data-tab="timeline"
    >
        @include("Annotation.Video.Panes.timeline")
    </div>
    <div class="ui tab objects h-full w-full" data-tab="objects">
        @include("Annotation.Video.Panes.search", [
            'idDocument' => $idDocument,
            'annotationType' => $annotationType
        ])
    </div>
    <div class="ui tab h-full w-full sentences" data-tab="sentences">

    </div>
</div>
<script type="text/javascript">
    $(function() {
        $(".tabs .item").tab({
            onLoad: (tabPath, parameterArray, historyEvent) => {
                if(tabPath === 'sentences') {
                    htmx.ajax("GET", "/annotation/dynamicMode/sentences/{{$idDocument}}", ".sentences");
                }
            }
        });
    });
</script>
