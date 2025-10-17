@php($word =  '')
<div
    class="container"
    hx-trigger="reload-sentence from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/annotation/fullText/annotations/{{$sentence->idDocumentSentence}}"
>
    @foreach($tokens as $i => $token)
        @php($hasAS = isset($token['idAS']) ? ' hasAS ' : '')
        @php($hasLU = $token['hasLU'] ? ' hasLU ' : '')
        @if(isset($token['idAS']))
            @if($token['idAS'] == $idAnnotationSet)
               @php($word =  $token['word'])
            @endif
        @endif
        <span
            class="word {{$hasLU}}"
            id="{{$i}}">
            @if($hasAS != '')
                <button
                    class="hasAS"
                    hx-get="/annotation/fullText/as/{{$token['idAS']}}/{{$token['word']}}"
                    hx-target="#workArea"
                    hx-swap="innerHTML"
                >{{$token['word']}}
                </button>
            @else
                @if($hasLU != '')
                    <button
                        class="hasLU"
                        hx-get="/annotation/fullText/lus/{{$sentence->idDocumentSentence}}/{{$i}}"
                        hx-target="#workArea"
                        hx-swap="innerHTML"
                    >{{$token['word']}}
                    </button>
                @else
                    {{$token['word']}}
                @endif
            @endif
        </span>
    @endforeach
</div>
<div id="workArea" class="workArea h-full" x-data="$store.ftStore">
    @if(!is_null($idAnnotationSet))
        <div
            hx-trigger="load"
            hx-get="/annotation/fullText/as/{{$idAnnotationSet}}/{{$word}}"
            hx-target="#workArea"
            hx-swap="innerHTML"
        >
        </div>
    @endif
</div>
