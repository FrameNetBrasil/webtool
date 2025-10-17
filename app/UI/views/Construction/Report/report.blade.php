<div id="cxnReport" class="flex flex-column h-full">
    <div class="flex flex-row align-content-start">
        <div class="col-12 sm:col-12 md:col-12 lg:col-7 xl:col-6">
            <h1>
                <x-element.construction name="{{$construction->name}}"></x-element.construction>
            </h1>
        </div>
        <div
            class="col-12 sm:col-12 md:col-12 lg:col-5 xl:col-6 flex gap-1 flex-wrap align-items-center justify-content-end">
            <div class="ui label wt-tag-id">
                {{$language->description}}
            </div>
            <div class="ui label wt-tag-id">
                #{{$construction->idConstruction}}
            </div>
            <button
                id="btnDownload"
                class="ui button mini basic secondary"
            ><i class="icon material">download</i>PDF
            </button>
        </div>
    </div>
    <x-card title="Definition" class="cxnReport__card cxnReport__card--main">
        {!! str_replace('ex>','code>',nl2br($construction->description)) !!}
    </x-card>
    <x-card title="Construction Elements" class="cxnReport__card cxnReport__card--main">
        @foreach ($ces['ces'] as $ceObj)
            <x-card
                title="<span class='color_{{$ceObj->idColor}}'>{{$ceObj->name}}</span>"
                class="cxnReport__card cxnReport__card--main"
            >
                <x-card title="" class="cxnReport__card cxnReport__card--main">
                    {!! str_replace('ex>','code>',nl2br($ceObj->description)) !!}
                </x-card>

                {{--                <x-card title="Relations" class="cxnReport__card cxnReport__card--main" open="true">--}}
                {{--                    @php($i = 0)--}}
                {{--                    @foreach ($relationsCE[$ceObj->idConstructionElement] as $nameEntry => $relations1)--}}
                {{--                        @php([$entry, $name] = explode('|', $nameEntry))--}}
                {{--                        @php($relId = str_replace(' ', '_', $name))--}}
                {{--                        <x-card-plain--}}
                {{--                            title="<span class='color_{{$entry}}'>{{$name}}</span>"--}}
                {{--                            @class(["cxnReport__card" => (++$i < count($report['relations']))])--}}
                {{--                            class="cxnReport__card--internal"--}}
                {{--                        >--}}
                {{--                            <div class="flex flex-wrap gap-1">--}}
                {{--                                @foreach ($relations1 as $idConstruction => $relation)--}}
                {{--                                    <button--}}
                {{--                                        id="btnRelation_{{$relId}}_{{$idConstruction}}"--}}
                {{--                                        class="ui button basic"--}}
                {{--                                    >--}}
                {{--                                        <a--}}
                {{--                                            href="/report/cxn/{{$idConstruction}}"--}}
                {{--                                        >--}}
                {{--                                            <x-element.construction name="{{$relation['name']}}"></x-element.construction>--}}
                {{--                                        </a>--}}
                {{--                                    </button>--}}
                {{--                                @endforeach--}}
                {{--                            </div>--}}
                {{--                        </x-card-plain>--}}
                {{--                    @endforeach--}}
                <x-card
                    title="Comparative concepts"
                    class="cxnReport__card--internal"
                    open="true"
                >
                    @php($i = 0)
                    @foreach ($conceptsCE[$ceObj->idConstructionElement] as $concept)
                        <button
                            id="btnRelation_concept_{{$concept->idConcept}}"
                            class="ui button basic"
                        >
                            <a
                                href="/report/c5/{{$concept->idConcept}}"
                            >
                                <x-element.concept name="{{$concept->name}}" type="{{$concept->type}}"></x-element.concept>
                            </a>
                        </button>
                    @endforeach
                </x-card>

                <x-card
                    title="Evokes"
                    class="cxnReport__card--internal"
                    open="true"
                >
                    @php($i = 0)
                    @foreach ($evokesCE[$ceObj->idConstructionElement] as $fe)
                        <button
                            id="btnRelation_evokes_{{$fe->idFrame}}"
                            class="ui button basic"
                        >
                            <a
                                href="/report/frame/{{$fe->idFrame}}"
                            >
                                <x-element.frame name="{{$fe->frameName}}.{{$fe->name}}"></x-element.frame>
                            </a>
                        </button>
                    @endforeach
                </x-card>

                <x-card
                    title="Constraints"
                    class="cxnReport__card--internal"
                    open="true"
                >
                    @foreach ($constraintsCE[$ceObj->idConstructionElement] as $conName => $constraints)
                        <div class="flex">
                            <div class="font-bold">
                                {{$conName}}:
                            </div>
                            @foreach ($constraints as $constraint)
                                <div
                                    class="ml-2"
                                    id="btnRelation_constraint_{{$constraint->idConstraint}}"
                                >
                                    {{$constraint->name}}
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </x-card>

            </x-card>




            {{--            </x-card>--}}
        @endforeach
    </x-card>
    <x-card title="Relations" class="cxnReport__card cxnReport__card--main" open="true">
        @php($i = 0)
        @foreach ($relations as $nameEntry => $relations1)
            @php([$entry, $name] = explode('|', $nameEntry))
            @php($relId = str_replace(' ', '_', $name))
            <x-card-plain
                title="<span class='color_{{$entry}}'>{{$name}}</span>"
                @class(["cxnReport__card" => (++$i < count($report['relations']))])
                class="cxnReport__card--internal"
            >
                <div class="flex flex-wrap gap-1">
                    @foreach ($relations1 as $idConstruction => $relation)
                        <button
                            id="btnRelation_{{$relId}}_{{$idConstruction}}"
                            class="ui button basic"
                        >
                            <a
                                href="/report/cxn/{{$idConstruction}}"
                            >
                                <x-element.construction name="{{$relation['name']}}"></x-element.construction>
                            </a>
                        </button>
                    @endforeach
                </div>
            </x-card-plain>
        @endforeach
        <x-card-plain
            title="Comparative concepts"
            class="cxnReport__card--internal"
            open="true"
        >
            @php($i = 0)
            @foreach ($concepts as $concept)
                @php(debug($concept))
                <button
                    id="btnRelation_concept_{{$concept->idConcept}}"
                    class="ui button basic"
                >
                    <a
                        href="/report/c5/{{$concept->idConcept}}"
                    >
                        <x-element.concept name="{{$concept->name}}" type="{{$concept->type}}"></x-element.concept>
                    </a>
                </button>
            @endforeach
        </x-card-plain>

        <x-card-plain
            title="Evokes"
            class="cxnReport__card--internal"
            open="true"
        >
            @php($i = 0)
            @foreach ($evokes as $frame)
                <button
                    id="btnRelation_evokes_{{$frame->idFrame}}"
                    class="ui button basic"
                >
                    <a
                        href="/report/frame/{{$frame->idFrame}}"
                    >
                        <x-element.frame name="{{$frame->name}}"></x-element.frame>
                    </a>
                </button>
            @endforeach
        </x-card-plain>

    </x-card>

</div>
<script>
    $("#btnDownload").click(function(e) {
        const options = {
            margin: 0.5,
            filename: '{{$construction->name}}.pdf',
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
        const element = document.getElementById("cxnReport");
        html2pdf().from(element).set(options).save();
    });
</script>
