<div class="sentences-container">
    <table id="sentenceTable" class="ui compact striped table">
        <thead>
        <tr>
            <th>idSentence</th>
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
                <td>
                    {!! $sentence->text !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
