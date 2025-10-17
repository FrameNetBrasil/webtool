<div id="frameReport" class="flex flex-column h-full">
    <div class="flex flex-row align-content-start">
        <div class="col-12 sm:col-12 md:col-12 lg:col-7 xl:col-6">
            <h1>
                <x-element.semantictype name="{{$semanticType->name}}"></x-element.semantictype>
            </h1>
        </div>
        <div class="col-12 sm:col-12 md:col-12 lg:col-5 xl:col-6 flex gap-1 flex-wrap align-items-center justify-content-end">
        </div>
    </div>
    <x-card title="Definition" class="frameReport__card frameReport__card--main">
        {!! str_replace('ex>','code>',nl2br($semanticType->description)) !!}
    </x-card>
    <x-card title="Relations" class="frameReport__card frameReport__card--main" open="true">
        @php($i = 0)
        @foreach ($relations as $nameEntry => $relations1)
            @php([$entry, $name] = explode('|', $nameEntry))
            @php($relId = str_replace(' ', '_', $name))
            <x-card-plain
                title="<span class='color_{{$entry}}'>{{$name}}</span>"
                @class(["frameReport__card" => (++$i < count($report['relations']))])
                class="frameReport__card--internal">
                <div class="flex flex-wrap gap-1">
                    @foreach ($relations1 as $idSemanticType => $relation)
                        <button
                            id="btnRelation_{{$relId}}_{{$idSemanticType}}"
                            class="ui button basic"
                        >
                            <a
                                href="/report/semanticType/{{$idSemanticType}}"
                            >
                                <x-element.semantictype name="{{$relation['name']}}"></x-element.semantictype>
                            </a>
                        </button>
                    @endforeach
                </div>
            </x-card-plain>
        @endforeach
    </x-card>
</div>
