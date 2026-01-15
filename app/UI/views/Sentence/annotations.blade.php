<div class="annotation-workarea">
    <div class="annotation-sentence">
        {{$text}}
{{--        @foreach($tokens as $i => $token)--}}
{{--            @php($hasAS = ($token['idAS'] != -1))--}}
{{--            @if(!$token['hasLU'] && !$hasAS)--}}
{{--                <div--}}
{{--                    class="ui medium button mb-2 hasNone"--}}
{{--                >{{$token['word']}}</div>--}}
{{--            @else--}}
{{--                <div--}}
{{--                    class="ui medium button mb-2 {!! $hasAS ? 'hasAS' : 'hasLU' !!}"--}}
{{--                    --}}{{--                                    hx-get="{!! $hasAS ? '/annotation/corpus/as/'. $corpusAnnotationType. '/'.  $token['idAS'] . '/' . $token['word']  : '/annotation/corpus/lus/'.$corpusAnnotationType.'/'. $idDocumentSentence . '/'. $i !!}"--}}
{{--                    --}}{{--                                    hx-target=".annotation-panel"--}}
{{--                    --}}{{--                                    hx-swap="innerHTML"--}}
{{--                >{{$token['word']}}--}}
{{--                </div>--}}
{{--            @endif--}}
{{--        @endforeach--}}
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
        </div>
    @endforeach
</div>
