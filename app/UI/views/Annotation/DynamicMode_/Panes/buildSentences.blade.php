<div
    hx-trigger="reload-gridSentence from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/annotation/dynamicMode/buildSentences/sentences/{{$idDocument}}"
>

    <table id="sentenceTable" class="ui compact striped table sentences">
        <thead>
        <tr>
            <th>idSentence</th>
            <th>startTime</th>
            <th>endTime</th>
            <th>text</th>
            <th>origin</th>
        </tr>
        </thead>
        <tbody
        >
        @foreach($sentences as $idSentence => $sentence)
            <tr
                hx-get="/annotation/dynamicMode/formSentence/{{$sentence->idDocument}}/{{$sentence->idSentence}}"
                hx-target="#formSentence"
                hx-swap="innerHTML"
                class="cursor-pointer"
                onclick="annotation.video.playByRange({{$sentence->startTime}},{{$sentence->endTime}},0)"
            >
                <td>
                    #{{$sentence->idSentence}}
                </td>
                <td>{{$sentence->startTime}}</td>
                <td>{{$sentence->endTime}}</td>
                <td>
                    {!! $sentence->text !!}
                </td>
                <td nowrap>{{$sentence->origin}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
