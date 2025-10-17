<div
    id="formAnnotationSet"
    class="h-full  flex flex-column"
    hx-trigger="reload-annotationSet from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/annotation/fullText/as/{{$idAnnotationSet}}/{{$word}}"
>

    <div
        class="annotationSet"
    >
        <div class="ui card w-full">
            <div class="content">
                <div class="header">
                    <div class="grid">
                        <div class="col-8">
                            LU: {{$lu->frame->name}}.{{$lu->name}}
                        </div>
                        <div class="col-4 text-right">
                            <div class="ui dropdown alternativeLU">
                                <div class="text">Alternative LUs</div>
                                <i class="dropdown icon"></i>
                                <div class="menu">
                                    @foreach($alternativeLU as $lu)
                                        <div class="item">{{$lu->frameName}}.{{$lu->lu}}</div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ui label wt-tag-id">
                                #{{$idAnnotationSet}}
                            </div>
                            <button
                                class="ui button secondary"
                                hx-get="/annotation/fullText/formComment/{{$idAnnotationSet}}"
                                hx-target="#formDescription"
                                hx-swap="innerHTML"
                            >
                                Comment
                            </button>
                            <button
                                class="ui button negative"
                                onclick="messenger.confirmDelete(`Removing AnnotationSet #{{$idAnnotationSet}}'.`, '/annotation/fullText/annotationset/{{$idAnnotationSet}}', null, '#workArea')"
                            >
                                Delete this AnnotationSet
                            </button>
                        </div>
                    </div>
                </div>
                <hr>
                <div id="formDescription" class="description">
                    @include("Annotation.FullText.Panes.annotationSetAnnotations")
                </div>
            </div>
        </div>
    </div>


    <div class="ui secondary menu tabs">
        @foreach($layerTypes as $layerType)
            <a class="item" data-tab="{{$layerType->entry}}">{{$layerType->name}}</a>
        @endforeach
    </div>
    <div class="gridLabels">
        <div class="labels">
            {{--            <div class="grids flex flex-column flex-grow-1">--}}
            @foreach($labels as $layerType => $labelData)
                <div class="ui card w-full tab {!! ($layerType == 'lty_fe') ? 'active' : '' !!}"
                     data-tab="{{$layerType}}">
                    <div class="content">
                        <div class="rowFE">
                            @foreach($labelData as $idEntity => $label)
                                <div class="colFE">
                                    <button
                                        class="ui right labeled icon button color_{{$label->idColor}}"
                                        x-data @click="$store.ftStore.annotate({{$idEntity}},'{{$layerType}}')"
                                    >
                                        <i
                                            class="delete icon"
                                            x-data @click.stop="$store.ftStore.deleteLabel({{$idEntity}})"
                                            {{--                                        hx-on:click="event.stopPropagation()"--}}
                                            {{--                                        hx-delete="/annotation/fullText/label"--}}
                                            {{--                                        hx-vals='js:{idAnnotationSet: {{$idAnnotationSet}}, idEntity:{{$idEntity}}}'--}}
                                            {{--                                        hx-target="#workArea"--}}
                                        >
                                        </i>
                                        @if ($layerType == 'lty_fe')
                                            <x-element.fe
                                                name="{{$label->name}}"
                                                type="{{$label->coreType}}"
                                                idColor="{{$label->idColor}}"
                                            ></x-element.fe>
                                        @else
                                            <x-element.gl
                                                name="{{$label->name}}"
                                                idColor="{{$label->idColor}}"
                                            ></x-element.gl>
                                        @endif
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
<script type="text/javascript">
    console.log({{$idAnnotationSet}});
    $(function() {
        Alpine.store("ftStore").idAnnotationSet = {{$idAnnotationSet}};
        Alpine.store("ftStore")._token = "{{ csrf_token() }}";
        Alpine.store("ftStore").updateASData();
        $(".tabs .item")
            .tab()
        ;
        $(".alternativeLU")
            .dropdown({
                action: "hide"
            });
    });
</script>
