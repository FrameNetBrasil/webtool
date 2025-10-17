<x-layout.index>
    <div class="app-layout annotation">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/annotation','Annotation'],['/annotation/as','AnnotationSets'],['','#' . $idDocumentSentence]]"
        ></x-layout::breadcrumb>
        <div class="annotation-corpus">
            <script type="text/javascript" src="/annotation/corpus/script/components"></script>
            <div class="annotation-canvas">
                <div class="annotation-navigation">
                    <div class="tag">
                        <div class="ui label wt-tag-id">
                            Corpus: {{$corpus->name}}
                        </div>
                        <div class="ui label wt-tag-id">
                            Document: {{$document->name}}
                        </div>
                    </div>
                    <div>
                        @if($idPrevious)
                            <a href="/annotation/{{$corpusAnnotationType}}/sentence/{{$idPrevious}}">
                                <button class="ui left labeled icon button">
                                    <i class="left arrow icon"></i>
                                    Previous
                                </button>
                            </a>
                        @endif
                        @if($idNext)
                            <a href="/annotation/{{$corpusAnnotationType}}/sentence/{{$idNext}}">
                                <button class="ui right labeled icon button">
                                    <i class="right arrow icon"></i>
                                    Next
                                </button>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="annotation-workarea">
                    <div class="annotation-sentence">
                        @foreach($tokens as $i => $token)
                            @php($hasAS = ($token['idAS'] != -1))
                            @if(!$token['hasLU'] && !$hasAS)
                                <div
                                    class="ui medium button mb-2 hasNone"
                                >{{$token['word']}}</div>
                            @else
                                <div
                                    class="ui medium button mb-2 {!! $hasAS ? 'hasAS' : 'hasLU' !!}"
                                    {{--                                    hx-get="{!! $hasAS ? '/annotation/corpus/as/'. $corpusAnnotationType. '/'.  $token['idAS'] . '/' . $token['word']  : '/annotation/corpus/lus/'.$corpusAnnotationType.'/'. $idDocumentSentence . '/'. $i !!}"--}}
                                    {{--                                    hx-target=".annotation-panel"--}}
                                    {{--                                    hx-swap="innerHTML"--}}
                                >{{$token['word']}}
                                </div>
                            @endif
                        @endforeach
                    </div>
                        @foreach($annotationSets as $i => $annotationSet)
                            @php($token = substr($sentence->text, $annotationSet->startChar, $annotationSet->endChar - $annotationSet->startChar + 1))
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
                                        href="/annotation/fe/sentence/{{$idDocumentSentence}}/{{$annotationSet->idAnnotationSet}}"
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
</x-layout.index>
