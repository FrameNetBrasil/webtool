<div class="sentences-container">
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
                <td @click.prevent="$dispatch('video-seek-time',{time:{{$sentence->startTime}}})"><span
                        class="cursor-pointer">{{$sentence->startTime}}</span></td>
                <td @click.prevent="$dispatch('video-seek-time',{time:{{$sentence->endTime}}})"><span
                        class="cursor-pointer">{{$sentence->endTime}}</span></td>
                <td @click.prevent="$dispatch('play-at-time',{time:{{$sentence->startTime}}})"><i
                        class="icon material text-xl cursor-pointer">play_arrow</i></td>
                <td @click.prevent="$dispatch('play-range',{startTime:{{$sentence->startTime}},endTime:{{$sentence->endTime}},duration:1})"><i
                        class="icon material text-xl cursor-pointer">timer</i></td>
                <td @click.prevent="$dispatch('play-range',{startTime:{{$sentence->startTime}},endTime:{{$sentence->endTime}},duration:3})"><i
                        class="icon material text-xl cursor-pointer">timer_3_alt_1</i></td>
                <td @click.prevent="$dispatch('play-range',{startTime:{{$sentence->startTime}},endTime:{{$sentence->endTime}},duration:5})"><i
                        class="icon material text-xl cursor-pointer">timer_5</i></td>
                <td>
                    {!! $sentence->text !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
