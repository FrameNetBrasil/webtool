@if (!is_null($idAnnotationSet))
    <div
        x-data="annotationSetComponent({{$idAnnotationSet}},'','{{$corpusAnnotationType}}')"
        @selectionchange.document="selectionRaw =  document.getSelection()"
        class="annotationSetComponent"
    >
        <div class="ui card w-full">
            <div class="content">
                <div class="header">
                    <div class="d-flex justify-between">
                        <div></div>
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
                    @include("Annotation.Corpus.Panes.Flex.asAnnotation")
                </div>
            </div>
        </div>
        <div class="ui grid">
            <div class="twelve wide column">
                <div class="ui card w-full">
                    <div class="content">
                        @include("Annotation.Corpus.Panes.Flex.asLabels")
                    </div>
                </div>
            </div>
            <div class="four wide column">
                <div class="d-flex flex-col">
                    <div class="ui card w-full">
                        <div class="content">
                            <div class="header">Created by</div>
                            <div
                                class="description">{!! $annotationSet?->email ?? 'Not available' !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
