@use("App\Repositories\Frame")
@use("App\Repositories\Namespace_")
<div x-data="luComponent({{$idDocumentSentence}},'{{$corpusAnnotationType}}')" class="ui container page">
    <div class="ui card w-full">
        <div class="content">
            <div class="description">
                <div
                    class="font-semibold"
                >
                    Define span for LU
                </div>
                <hr>
                <div class="d-flex wrap">
                    @foreach($words as $i => $word)
                        <div class="pr-3">
                            <input
                                type="checkbox"
                                class="words"
                                id="words_{{$i}}"
                                name="words[{{$i}}]"
                                value="{{$i}}" {{($i == $idWord) ? 'checked' : ''}}
                                data-startchar="{{$word['startChar']}}"
                                data-endchar="{{$word['endChar']}}"
                            >
                            <span class="">{{$word['word']}}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <h3>Candidate LU for word "<span class="color-lu">{{$words[$idWord]['word']}}</span>"</h3>

    @foreach($lus as $idNamespace => $luByNamespace)
        @php($ns = Namespace_::byId($idNamespace))
        <div class="ui card w-full">
            <div class="content">
                <div class="header">
                    <x-ui::element.namespace :namespace="$ns"></x-ui::element.namespace>
                </div>
            </div>
            <div class="content">
                <div class="card-grid dense">
                    @foreach($luByNamespace as $lu)
                        <div
                            class="ui card option-card cursor-pointer"
                            @click="onCreateAS({{$lu->idLU}})"
                        >
                            <div class="content overflow-hidden">
                                <div class="header">
                                    @php($frame = Frame::byId($lu->idFrame))
                                    <x-ui::element.lu name="{{$lu->lu}}"></x-ui::element.lu>
                                </div>
                                <div class="description">
                                    <div>
                                        <x-ui::element.frame_ns :frame="$frame"></x-ui::element.frame_ns>
                                    </div>
                                    <div>
                                        {{$lu->senseDescription}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
