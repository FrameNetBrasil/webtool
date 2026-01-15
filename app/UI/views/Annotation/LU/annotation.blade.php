<x-layout::index>
    <div class="app-layout annotation">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/annotation','Annotation'],['/annotation/lu','AnnotationSets by LU'],['','#' . $idLU]]"
        ></x-partial::breadcrumb>
        <div class="annotation-corpus">
            <script type="text/javascript" src="/annotation/corpus/script/components"></script>
            <div class="annotation-canvas">
                <div class="annotation-navigation">
                </div>
                <div class="annotation-workarea">
                    @foreach($annotationSets as $i => $annotationSet)
                        <div class="ui fluid card bg-gray-200">
                            <div class="content">
                                <div
                                    hx-trigger="load"
                                    hx-get="/annotation/fe/asExternal/{{$annotationSet->idAnnotationSet}}"
                                    hx-swap="innerHTML"
                                >
                                </div>
                            </div>
                            <div class="extra content ">
                                <a
                                    href="/annotation/fe/sentence/{{$annotationSet->idDocumentSentence}}/{{$annotationSet->idAnnotationSet}}"
                                    target="_blank"
                                >
                                    <button
                                        type="button"
                                        class="ui secondary button"
                                    >Edit this AnnotationSet
                                    </button>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-layout::index>
