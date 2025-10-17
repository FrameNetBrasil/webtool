@php
    use App\Services\AnnotationFEService;
    if (($search->idSentence ?? '') != '') {
        $sentences = AnnotationFEService::getSentence($search->idSentence);
    }
@endphp
<div class="wt-datagrid flex flex-column" style="height:100%">
    <div class="datagrid-header">
        <div class="datagrid-title flex justify-content-between">
            <div>
                Sentences
            </div>
            @if(isset($document))
                <div>
                    <div class="ui label wt-tag-id">
                        <x-icon.corpus></x-icon.corpus>{{$document->corpusName}}
                    </div>
                    <div class="ui label wt-tag-id">
                        <x-icon.document></x-icon.document>{{$document->name}}
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="table" style="position:relative;height:100%">
        <table id="sentenceTable">
            <tbody
            >
            @foreach($sentences as $idSentence => $sentence)
                <tr
                    {{--                    hx-target="#gridArea"--}}
                    {{--                    hx-swap="innerHTML"--}}
                >
                    <td
                        style="width:100px"
                    >
                        <a
                            href="/annotation/fe/sentence/{{$idSentence}}" target="_blank"
                        >
                            #{{$sentence->idDocumentSentence}}
                        </a>
                    </td>
                    @if(isset($sentence->startTime))
                        <td
                            style="width:100px"
                        >
                            <a
                                href="/annotation/fe/sentence/{{$idSentence}}" target="_blank"
                            >
                                <i class="material icon">schedule</i>{{$sentence->startTime}}
                            </a>
                        </td>
                    @endif
                    <td>
                        <a
                            href="/annotation/fe/sentence/{{$idSentence}}" target="_blank"
                        >
                            <div class="sentence">
                                {!! $sentence->text !!}
                            </div>
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

