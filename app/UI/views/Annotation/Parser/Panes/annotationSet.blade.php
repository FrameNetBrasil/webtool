@if (!is_null($idAnnotationSet))
    <div
        x-data="annotationSetComponent({{$idAnnotationSet}},'{{$word}}','{{$corpusAnnotationType}}')"
        @selectionchange.document="selectionRaw =  document.getSelection()"
        class="annotationSetComponent"
    >
        <div class="ui card w-full">
            <div class="content">
                <div class="header">
                    <div class="d-flex justify-between">
                        <div class="d-flex">
                            <div>LU: <span class="color_frame">{{$lu->frame->name}}</span>.<span
                                    class="color_lu">{{$lu->name}}</span>
                            </div>
                            <div id="statusField" class="d-flex">
                                @include("Annotation.Corpus.Panes.asStatusField")
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="ui label wt-tag-id">
                                #{{$idAnnotationSet}}
                            </div>
                            <button
                                class="ui button negative"
                                onclick="messenger.confirmDelete(`Removing AnnotationSet #{{$idAnnotationSet}}'.`, '/annotation/corpus/annotationset/{{$idAnnotationSet}}/{{$corpusAnnotationType}}', null, '#workArea')"
                            >
                                Delete this AnnotationSet
                            </button>

                        </div>
                    </div>
                </div>
                <hr>
                <div class="annotationSetColumns description">
                    @if($corpusAnnotationType == "fe")
                        @include("Annotation.Corpus.Panes.FE.asAnnotation")
                    @else
                        @include("Annotation.Corpus.Panes.FullText.asAnnotation")
                    @endif
                </div>
            </div>
        </div>
        <div class="ui grid">
            <div class="twelve wide column">
                <div class="ui card w-full">
                    <div class="content">
                        @if($corpusAnnotationType == "fe")
                            @include("Annotation.Corpus.Panes.FE.asLabels")
                        @else
                            @include("Annotation.Corpus.Panes.FullText.asLabels")
                        @endif
                    </div>
                </div>
            </div>
            <div class="four wide column">
                <div class="d-flex flex-col">
                    @include("Annotation.Corpus.Panes.asStatus")
                    <div class="ui card w-full">
                        <div class="content">
                            <div class="header">Created by</div>
                            <div
                                class="description">{!! ($annotationSet->email != '') ? $annotationSet->email : 'Not available' !!}
                            </div>
                        </div>
                    </div>
                    <div class="ui card w-full alternativeLU mt-0">
                        <div class="content">
                            <div class="header">
                                Alternative LUs
                            </div>
                            <div class="description lus">
                                @foreach(($alternativeLU ?? []) as $lu)
                                    <div class="mb-2">
                                        <button
                                            class="ui button basic"
                                            onclick="messenger.confirmPost(`Change AnnotationSet to LU '{{$lu->frameName}}.{{$lu->lu}}' ?`, '/annotation/corpus/annotationset/{{$idAnnotationSet}}/change/{{$lu->idLU}}/{{$corpusAnnotationType}}')"
                                        ><span class="color_frame">{{$lu->frameName}}</span>.<span
                                                class="color_lu">{{$lu->lu}}</span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endif
