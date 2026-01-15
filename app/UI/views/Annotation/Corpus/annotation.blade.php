<x-layout::index>
    <div class="app-layout annotation">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/annotation','Annotation'],[$url,$page],['','#' . $idDocumentSentence]]"
        ></x-partial::breadcrumb>
        <div class="annotation-action">
            @if($corpusAnnotationType != 'flex')
                @include("Annotation.Session.Panes.sessionPane")
            @endif
        </div>
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
                        @if($corpusAnnotationType == 'flex')
                            @foreach($tokens as $i => $token)
                                <div
                                    class="ui medium mb-2 mr-2"
                                >{{$token['word']}}
                                </div>
                            @endforeach
                        @else
                            @foreach($tokens as $i => $token)
                                @php($hasAS = ($token['idAS'] != -1))
                                @if(!$token['hasLU'] && !$hasAS)
                                    <div
                                        class="ui medium button mb-2 hasNone"
                                    >{{$token['word']}}</div>
                                @else
                                    <div
                                        class="ui medium button mb-2 {!! $hasAS ? 'hasAS' : 'hasLU' !!}"
                                        hx-get="{!! $hasAS ? '/annotation/corpus/as/'. $corpusAnnotationType. '/'.  $token['idAS'] . '/' . $token['word']  : '/annotation/corpus/lus/'.$corpusAnnotationType.'/'. $idDocumentSentence . '/'. $i !!}"
                                        hx-target=".annotation-panel"
                                        hx-swap="innerHTML"
                                    >{{$token['word']}}
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                    <div
                        class="annotation-panel"
                    ></div>
                    @if($corpusAnnotationType == 'flex')
                        <div
                            hx-trigger="load"
                            hx-get="/annotation/flex/annotation/{{$idDocumentSentence}}"
                            hx-target=".annotation-panel"
                            hx-swap="innerHTML"
                        >
                        </div>
                    @else
                        @if(!is_null($idAnnotationSet))
                            <div
                                hx-trigger="load"
                                hx-get="/annotation/corpus/as/{{$corpusAnnotationType}}/{{$idAnnotationSet}}/{{$word}}"
                                hx-target=".annotation-panel"
                                hx-swap="innerHTML"
                            >
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layout::index>
