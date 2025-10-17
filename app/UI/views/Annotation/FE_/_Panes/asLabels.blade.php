<div class="annotationTab">
    <div id="annotationTabs_{{$idAnnotationSet}}" class="ui pointing secondary menu tabs">
        <a
            class="active item"
            data-tab="labels"
        >FE</a>
        <a
            class="item"
            data-tab="comment"
        ><i class="comment dots outline icon"></i>
            Comment</a>
    </div>
    <div
        class="ui tab"
        data-tab="labels"
    >
        @foreach($fesByType as $type => $fesData)
            @if (count($fesData) > 0)
                <div>{{$type}}</div>
                <div class="rowFE">
                    @foreach($fesData as $idEntity)
                        @php($fe = $fes[$idEntity])
                        <div class="colFE">
                            <button
                                class="ui right labeled icon button mb-2 color_{{$fe->idColor}}"
                                @click.stop="onLabelAnnotate({{$fe->idFrameElement}})"
                            >
                                <i
                                    class="delete icon"
                                    @click.stop="onLabelDelete({{$fe->idFrameElement}})"
                                >
                                </i>
                                <div class="d-flex">
                                    <i class="{!! config("webtool.fe.icon")[$fe->coreType] !!} icon feIcon"></i>{{$fe->name}}
                                </div>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
    <div class="ui tab" data-tab="comment">
        @include("Annotation.Comment.formComment")
    </div>
    <script type="text/javascript">
        $(function () {
            $(".menu .item").tab();
        });
    </script>
</div>
