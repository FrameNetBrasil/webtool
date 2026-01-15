@if (!is_null($idAnnotationSet))
    <div class="annotation-corpus">
        <div class="annotation-panel">
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
                                        onclick="messenger.confirmDelete(`Removing AnnotationSet #{{$idAnnotationSet}}'.`, '/annotation/fe/asExternal/{{$idAnnotationSet}}', null, '.annotationSetColumns')"
                                    >
                                        Delete this AnnotationSet
                                    </button>

                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="annotationSetColumns description">
                            @include("Annotation.Corpus.Panes.FE.asAnnotationRaw")
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
