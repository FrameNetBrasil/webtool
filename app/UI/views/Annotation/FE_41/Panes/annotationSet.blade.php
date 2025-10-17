<div
    id="formAnnotationSet"
    class="h-full"
    hx-trigger="reload-annotationSet from:body"
    hx-target="#workArea"
    hx-swap="innerHTML"
    hx-get="/annotation/fe/as/{{$idAnnotationSet}}/{{$word}}"
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
                    </div>
                </div>
            </div>
            <hr>
            <div id="formDescription" class="description">
                <div class="flex flex-row">
                    <div class="" style="width:150px">
                        <div class="rowNI">
                            @foreach($it as $i => $type)
                                @if(($type->entry != 'int_normal') && ($type->entry != 'int_apos'))
                                    <div class="colNI">
                                        @php($height = 24 + (isset($nis[$type->idTypeInstance]) ? (count($nis[$type->idTypeInstance]) * 30) : 0))
                                        <span
                                            class="ni"
                                            id="ni_{{$i}}"
                                            data-type="ni"
                                            data-name="{{$type->name}}"
                                            data-id="{{$type->idTypeInstance}}"
                                            style="height:{{$height}}px"
                                        >{{$type->name}}
                                            @foreach($nis as $idInstantiationType => $niFEs)
                                                @php($topLine = 30)
                                                @if($type->idTypeInstance == $idInstantiationType)
                                                    @foreach($niFEs as $niFE)
                                                        @php($idEntityFE = $niFE['idEntityFE'])
                                                        {{--                                                                            <span class="line" style="background:#{{$fes[$idEntityFE]->rgbBg}}; top:{{$topLine}}px">--}}
                                                        <span class="line color_{{$fes[$idEntityFE]->idColor}}"
                                                              style="top:{{$topLine}}px">
                                        <span class="feLabel color_{{$fes[$idEntityFE]->idColor}}"
                                              style="top:0px">{{$niFE['label']}}</span>
                            </span>
                                                        @php($topLine += 24)
                                                    @endforeach
                                                @endif
                                            @endforeach
                    </span>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                    </div>
                    <div>
                        <div class="rowWord">

                            @foreach($words as $i => $w)
                                {{--                            @if($word['word'] != ' ')--}}
                                <div class="{!! ($w['word'] != ' ') ? 'colWord' : 'colSpace' !!}">
                                    @php($isTarget = ($i >= $target->startWord) && ($i <= $target->endWord))
                                    @php($topLine = 30)
                                    @php($labelsAtWord = ($spans[$i] ?? []))
                                    @php($height = 24 + ($isTarget ? 0 : (count($labelsAtWord) * 30)))
                                    <span
                                        class="word {{$isTarget ? 'target' : ''}} {{$w['hasFE'] ? 'hasFE' : ''}}"
                                        id="word_{{$i}}"
                                        data-type="word"
                                        data-i="{{$i}}"
                                        data-startchar="{{$w['startChar']}}"
                                        data-endchar="{{$w['endChar']}}"
                                        style="height:{{$height}}px"
                                    >{{$w['word']}}
                                        @foreach($idLayers as $l => $idLayer)
                                            @php($label = $spans[$i][$idLayer])
                                            {{--                                        @foreach($labelsAtWord as $label)--}}
                                            @if(!is_null($label))
                                                @php($idEntityFE = $label['idEntityFE'])
                                                {{--                                <span class="line" style="background:#{{$fes[$idEntityFE]->rgbBg}}; top:{{$topLine}}px">--}}
                                                <span class="line color_{{$fes[$idEntityFE]->idColor}}"
                                                      style="top:{{$topLine}}px">
                                                @if($label['label'])
                                                        <span class="feLabel color_{{$fes[$idEntityFE]->idColor}}"
                                                              style="top:0px">{{$label['label']}}</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span></span>
                                            @endif
                                            @php($topLine += 24)
                                            {{--                                        @endforeach--}}
                                        @endforeach
                    </span>
                                </div>
                                {{--                            @endif--}}
                            @endforeach

                        </div>

                    </div>
                </div>


                <div class="rowFE">
                    @foreach($fes as $fe)
                        <div class="colFE">
                            <button
                                class="ui right labeled icon button color_{{$fe->idColor}}"
                                hx-on:click="event.stopPropagation()"
                                hx-post="/annotation/fe/annotate"
                                hx-target="#workArea"
                                hx-swap="innerHTML"
                                hx-vals="js:{idAnnotationSet: {{$idAnnotationSet}}, token: '{{$word}}', idFrameElement:{{$fe->idFrameElement}}, selection: annotationFE.selection}"
                            >
                                <i
                                    class="delete icon"
                                    hx-on:click="event.stopPropagation()"
                                    hx-delete="/annotation/fe/frameElement"
                                    hx-vals="js:{idAnnotationSet: {{$idAnnotationSet}}, token: '{{$word}}', idFrameElement:{{$fe->idFrameElement}}}"
                                    hx-target="#workArea"
                                    hx-swap="innerHTML"
                                >
                                </i>
                                <x-element.fe
                                    name="{{$fe->name}}"
                                    type="{{$fe->coreType}}"
                                    idColor="{{$fe->idColor}}"
                                ></x-element.fe>
                            </button>
                        </div>
                    @endforeach
                </div>
                <hr />
                <div class="rowDanger flex">
                    <button
                        class="ui button secondary"
                        hx-get="/annotation/fe/formComment/{{$idAnnotationSet}}"
                        hx-target="#formDescription"
                        hx-swap="innerHTML"
                    >
                        Comment
                    </button>
                    <button
                        class="ui button negative"
                        onclick="messenger.confirmDelete(`Removing AnnotationSet #{{$idAnnotationSet}}'.`, '/annotation/fe/annotationset/{{$idAnnotationSet}}', null, '#workArea')"
                        hx-indicator="#htmx-indicator"
                    >
                        Delete this AnnotationSet
                    </button>
                    <div id="htmx-indicator" class="htmx-indicator">
                        <div class="ui page">
                            <div class="ui loader active tiny text inverted">Processing</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    annotationFE.selection = {
        type: "",
        id: "",
        start: 0,
        end: 0
    };

    $(function() {
        $(".alternativeLU")
            .dropdown({
                action: "hide"
            });
    });
</script>
