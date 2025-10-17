<div class="w-1/2">
    <table class="ui celled striped table">
        <thead>
        <tr>
            <th colspan="3">FE Syntatic Realizations</th>
        </tr>
        <tr>
            <th>FE</th>
            <th style="width:8rem"># Annotated</th>
            <th>Realization(s)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($realizations as $feIdEntity => $gfptas)
            @if($feIdEntity)
                <tr
{{--                    style="display:table;table-layout: fixed;" class="border-b"--}}
                >
                    <td>
                        <x-ui::element.fe name="{{$fes[$feIdEntity]['name']}}"
                                          type="{{$fes[$feIdEntity]['type']}}"
                                          idColor="{{$fes[$feIdEntity]['idColor']}}"></x-ui::element.fe>
                    </td>
                    <td style="width:8rem;text-align:center">
                        <a href="#" onclick="reportLU.addFESentences('{{$feIdEntity}}')">
                            {!! count($fes[$feIdEntity]['as']) !!}
                        </a>
                    </td>
                    <td>
                        @foreach($gfptas as $gf => $ptas)
                            @foreach($ptas as $pt => $idRealization)
                                {{$gf}}.{{$pt}}&nbsp;&nbsp;
                                <a href="#"
                                   onclick="reportLU.addASSentences(reportLU.realizationAS['{{$idRealization[0]}}'])">
                                    ({!! count($realizationAS[$idRealization[0]]) !!})
                                </a>
                                <br />
                            @endforeach
                        @endforeach
                    </td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
</div>
<div class="mt-2" id="colGridValence">
    <table class="ui celled table">
        <thead>
        <tr>
            <th colspan="{!! $maxCountFE + 3 !!}">Valence Patterns</th>
        </tr>
        <tr>
            <th colspan="2" style="width:8rem"># Annotated</th>
            <th colspan="{!! $maxCountFE + 1 !!}" class="text-center">Patterns</th>
        </tr>
        </thead>
        <tbody>
        @php($p = 0)
        @foreach($vp as $idVPFE => $vp1)
            @php($l = 0)
            @foreach($patterns[$idVPFE] as $idVP => $scfegfptas)
                @if ($l++ == 0)
{{--                    <tr class="{!! ($p > 0) ? 'pattern' : '' !!} border-t border-b-dashed">--}}
                        <tr class="bg-gray-50">
                        <td style="width:8rem;text-align:center">
                            <a href="#"
                               onclick="reportLU.addASSentences(reportLU.patternFEAS['{{$idVPFE}}'])">
                                {{$vpfe[$idVPFE]['count']}}
                            </a>
                        </td>
                            <td></td>
                        @php($i = 0)
                        @foreach($scfegfptas as $sc => $fegfptas)
                            @foreach($fegfptas as $feIdEntity => $gfptas)
                                @if($feIdEntity)
                                    @foreach($gfptas as $gf => $ptas)
                                        @foreach($ptas as $pt => $as)
                                            @php($i = $i + 1)
                                            <td>
                                                <x-ui::element.fe name="{{$fes[$feIdEntity]['name']}}"
                                                                  type="{{$fes[$feIdEntity]['type']}}"
                                                                  idColor="{{$fes[$feIdEntity]['idColor']}}"></x-ui::element.fe>
                                            </td>
                                        @endforeach
                                    @endforeach
                                @endif
                            @endforeach
                        @endforeach
                        @for($j = $i; $j < ($maxCountFE); $j++)
                            <td></td>
                        @endfor
                    </tr>
                @endif
                <tr>
                    <td></td>
                    <td style="width:8rem;text-align:center">
                        <a href="#" onclick="reportLU.addASSentences(reportLU.patternAS['{{$idVP}}'])">
                            {!! count($vp[$idVPFE][$idVP]) !!}
                        </a>
                    </td>
                    @php($i = 0)
                    @foreach($scfegfptas as $sc => $fegfptas)
                        @foreach($fegfptas as $fe => $gfptas)
                            @foreach($gfptas as $gf => $ptas)
                                @foreach($ptas as $pt => $as)
                                    @php($i = $i + 1)
                                    <td>{{$gf}}<br />{{$pt}}</td>
                                @endforeach
                            @endforeach
                        @endforeach
                    @endforeach
                    @for($j = $i; $j < ($maxCountFE ); $j++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
            @php(++$p)
        @endforeach
    </tbody>
    </table>
</div>
<div id="reportLUSentences" class="mt-2">
    <div class="flex-container column">
        <div class="text">
            <button class="ui secondary button" onclick="reportLU.clearSentences()">Clear Sentences</button>
            <button class="ui secondary button" onclick="reportLU.toogleSentenceColors()">Turn Colors On/Off</button>

        </div>
        <div id="placeholder" class="ui placeholder mt-2" style="display:none">
            <div class="paragraph">
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
            </div>
        </div>
        <div id="divLexicalEntrySentences" class="mt-2">
            <div id="divSentencesColorOn" style="display:block">
            </div>
            <div id="divSentencesColorOff" style="display:none">
            </div>
        </div>
    </div>
</div>

<script>
    $("#btnDownload").click(function(e) {
        const options = {
            margin: 0.5,
            filename: '{{$lu->name}}.pdf',
            image: {
                type: "jpeg",
                quality: 500
            },
            html2canvas: {
                scale: 1
            },
            jsPDF: {
                unit: "in",
                format: "a4",
                orientation: "portrait"
            }
        };

        e.preventDefault();
        const element = document.getElementById("colGridValence");
        html2pdf().from(element).set(options).save();
    });

    reportLU = {
        fes: {{ Js::from($fes) }},
        realizationAS: {{ Js::from($realizationAS) }},
        feAS: {{ Js::from($feAS) }},
        patternFEAS: {{ Js::from($patternFEAS) }},
        patternAS: {{ Js::from($patternAS) }},
        showFrame: function(idFrame) {
            $("#reportLUCenterPane").html("");
            manager.doGet("/report/frame/showFrame" + "/" + {{$lu->idFrame}}, "reportLUCenterPane");
        },
        clearSentences: function() {
            $("#divSentencesColorOn").html("");
            $("#divSentencesColorOff").html("");
        },
        toogleSentenceColors: function() {
            if ($("#divSentencesColorOn").css("display") == "block") {
                $("#divSentencesColorOn").css("display", "none");
                $("#divSentencesColorOff").css("display", "block");
            } else {
                $("#divSentencesColorOn").css("display", "block");
                $("#divSentencesColorOff").css("display", "none");
            }
        },
        addFESentences: async function(feEntry) {
            $("#placeholder").show();
            await $.ajax({
                url: "/report/lu/sentences",
                method: "POST",
                dataType: "json",
                data: {
                    idAS: reportLU.feAS[feEntry],
                    _token: "{{ csrf_token() }}"
                },
                success: (sentences) => {
                    $("#placeholder").hide();
                    $.each(sentences, function(index, sentence) {
                        console.log(sentence);
                        var id = "sentence" + sentence.idSentence;
                        if ($("#" + id).length) {
                            //$(sentence.text).replaceAll('#' + id);
                        } else {
                            reportLU.addSentence(id, sentence.text, sentence.clean, sentence.idDocumentSentence);
                        }
                    });

                    // Scroll to the sentences container after adding sentences
                    setTimeout(() => {
                        document.getElementById("reportLUSentences").scrollIntoView({
                            behavior: "smooth",
                            block: "start"
                        });
                    }, 100);
                }
            });
        },
        addASSentences: async function(asSet) {
            $("#placeholder").show();
            await $.ajax({
                url: "/report/lu/sentences",
                method: "POST",
                dataType: "json",
                data: {
                    idAS: asSet,
                    _token: "{{ csrf_token() }}"
                },
                success: (sentences) => {
                    $("#placeholder").hide();
                    $.each(sentences, function(index, sentence) {
                        console.log(sentence);
                        var id = "sentence" + sentence.idSentence;
                        if ($("#" + id).length) {
                            //$(sentence.text).replaceAll('#' + id);
                        } else {
                            reportLU.addSentence(id, sentence.text, sentence.clean, sentence.idDocumentSentence);
                        }
                    });

                    // Scroll to the sentences container after adding sentences
                    setTimeout(() => {
                        document.getElementById("reportLUSentences").scrollIntoView({
                            behavior: "smooth",
                            block: "start"
                        });
                    }, 100);
                }
            });
        },
        addSentence: function(id, text, cleanText, idSentence) {
            var ban = `
                       <span class="delete material-icons-outlined wt-datagrid-icon wt-icon-delete" title="delete Sentence" onclick="reportLU.removeSentence('${id}')"></span>
                    `;
            var linkToAnnotation = `<a href="/annotation/fe/sentence/${idSentence}" target="_blank">[#${idSentence}]</a>`;
            $("#divSentencesColorOn").append("<div class='sentence' id='" + id + "'>" + ban + text + linkToAnnotation + "</div>");
            $("#divSentencesColorOff").append("<div class='sentence' id='" + id + "'>" + ban + cleanText + linkToAnnotation + "</div>");
        },
        removeSentence: function(id) {
            console.log(id);
            $("#" + id).remove();
        }
    };

</script>

