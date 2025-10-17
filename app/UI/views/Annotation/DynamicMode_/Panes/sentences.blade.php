<table id="sentenceTable" class="ui compact striped table">
    <thead>
    <tr>
        <th>idSentence</th>
        <th>startTime</th>
        <th>endTime</th>
        <th colspan="4">play</th>
        <th>text</th>
    </tr>
    </thead>
    <tbody
    >
    @foreach($sentences as $idSentence => $sentence)
        <tr>
            <td>
                #{{$sentence->idDocumentSentence}}
            </td>
            <td onclick="annotation.video.gotoTime({{$sentence->startTime}})"><span
                    class="cursor-pointer">{{$sentence->startTime}}</span></td>
            <td onclick="annotation.video.gotoTime({{$sentence->endTime}})"><span
                    class="cursor-pointer">{{$sentence->endTime}}</span></td>
            <td onclick="annotation.video.playByRange({{$sentence->startTime}},{{$sentence->endTime}},0)"><i
                    class="icon material text-xl cursor-pointer">play_arrow</i></td>
            <td onclick="annotation.video.playByRange({{$sentence->startTime}},{{$sentence->endTime}},1)"><i
                    class="icon material text-xl cursor-pointer">timer</i></td>
            <td onclick="annotation.video.playByRange({{$sentence->startTime}},{{$sentence->endTime}},3)"><i
                    class="icon material text-xl cursor-pointer">timer_3_alt_1</i></td>
            <td onclick="annotation.video.playByRange({{$sentence->startTime}},{{$sentence->endTime}},5)"><i
                    class="icon material text-xl cursor-pointer">timer_5</i></td>
            <td>
                {!! $sentence->text !!}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
