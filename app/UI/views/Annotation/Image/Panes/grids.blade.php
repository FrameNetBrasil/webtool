<div class="ui pointing secondary menu tabs">
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
    <div class="ui tab objects h-full w-full" data-tab="objects">
        @include("Annotation.Image.Panes.search", [
            'idDocument' => $idDocument,
            'annotationType' => $annotationType
        ])
    </div>
    <div
        class="ui tab h-full w-full sentences"
        data-tab="sentences"
        hx-get="/annotation/staticBBox/sentences/{{$idDocument}}"
        hx-trigger="load"
    >

    </div>
</div>
<script type="text/javascript">
    $(function() {
        $(".tabs .item").tab({
            {{--onLoad: (tabPath) => {--}}
            {{--    console.log(tabPath);--}}
            {{--    if(tabPath === 'sentences') {--}}
            {{--        htmx.ajax("GET", "/annotation/staticBBox/sentences/{{$idDocument}}", ".sentences");--}}
            {{--    }--}}
            {{--}--}}
        });
    });
</script>
