<x-layout.page>
    <x-slot:head>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/annotation','Annotation'],['/annotation/fe','FE'],['','#' . $idDocumentSentence]]"
        ></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <script type="text/javascript" src="/annotation/corpus/script/components"></script>
        <div class="app-layout annotation-corpus">
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
                            <a href="/annotation/fe/sentence/{{$idPrevious}}">
                                <button class="ui left labeled icon button">
                                    <i class="left arrow icon"></i>
                                    Previous
                                </button>
                            </a>
                        @endif
                        @if($idNext)
                            <a href="/annotation/fe/sentence/{{$idNext}}">
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
                                    hx-get="{!! $hasAS ? '/annotation/fe/as/' . $token['idAS'] . '/' . $token['word'] : '/annotation/fe/lus/'. $idDocumentSentence . '/'. $i !!}"
                                    hx-target=".annotation-panel"
                                    hx-swap="innerHTML"
                                >{{$token['word']}}
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div
                        class="annotation-panel"
                    ></div>
                    @if(!is_null($idAnnotationSet))
                        <div
                            hx-trigger="load"
                            hx-get="/annotation/fe/as/{{$idAnnotationSet}}/{{$word}}"
                            hx-target=".annotation-panel"
                            hx-swap="innerHTML"
                        >
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-slot:main>
</x-layout.page>
