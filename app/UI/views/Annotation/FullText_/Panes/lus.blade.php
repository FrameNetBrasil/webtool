<div class="ui card w-full">
    <div class="content">
        <div class="description">
            <div
            >
                Define span for LU
            </div>
            <hr>
            <div class="flex flex-row flex-wrap">
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

<script>
    function getWordData() {
        return JSON.stringify(
            _.map(
                _.filter(
                    document.querySelectorAll(".words"),
                    (x) => x.checked
                ),
                (y) => {
                    return {
                        startChar: y.dataset.startchar,
                        endChar: y.dataset.endchar
                    };
                }
            )
        );
    }
</script>

<x-datagrid
    id="gridLU"
    title="Candidate LU for word [{{$words[$idWord]['word']}}]"
    type="master"
{{--    height="450px"--}}
>
    @foreach($lus as $lu)
        <tr
            hx-post="/annotation/fullText/create"
            hx-vals='js:{"idDocumentSentence": {{$idDocumentSentence}},"idLU": {{$lu->idLU}}, "wordList": getWordData()}'
            hx-target="#workArea"
            hx-swap="innerHTML"
        >
            <td
                class="cursor-pointer"
                style="width:20%"
            >
                {{$lu->frameName}}
            </td>
            <td
                class="cursor-pointer"
                style="width:20%"
            >
                {{$lu->lu}}
            </td>
            <td
                class="cursor-pointer"
                style="width:60%"
            >
                {{$lu->senseDescription}}
            </td>
        </tr>
    @endforeach
</x-datagrid>

