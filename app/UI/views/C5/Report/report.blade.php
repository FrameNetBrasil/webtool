<div id="frameReport" class="flex flex-column h-full">
    <div class="flex flex-row align-content-start">
        <div class="col-12 sm:col-12 md:col-12 lg:col-7 xl:col-6">
            <h1>
                <x-element.concept type="{{$concept->type}}"
                                   name="{{$concept->name}} ({{$concept->type}})"></x-element.concept>
            </h1>
        </div>
        <div
            class="col-12 sm:col-12 md:col-12 lg:col-5 xl:col-6 flex gap-1 flex-wrap align-items-center justify-content-end">
            <div class="ui label wt-tag-id">
                #{{$concept->idConcept}}
            </div>
        </div>
    </div>
    <x-card title="Definition" class="frameReport__card frameReport__card--main">
        {!! str_replace('ex>','code>',nl2br($concept->description)) !!}
    </x-card>
    @if(isset($relationTypes['rel_constituentof']) || isset($relationTypes['rel_constituentof_inverse']))
        <x-card title="Constituency" class="frameReport__card frameReport__card--main" open="true">
            @php($i = 0)
            @foreach ($relations as $nameEntry => $relations1)
                @php([$entry, $name, $color] = explode('|', $nameEntry))
                @php($relId = str_replace(' ', '_', $name))
                @if(($entry == 'rel_constituentof') || ($entry == 'rel_constituentof_inverse'))
                    <x-card-plain
                        title="<span style='color:{{$color}}'>{{$name}}</span>"
                        @class(["frameReport__card" => (++$i < count($report['relations']))])
                        class="frameReport__card--internal">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($relations1 as $idConcept => $relation)
                                <button
                                    id="btnRelation_{{$relId}}_{{$idConcept}}"
                                    class="ui button basic"
                                >
                                    <a
                                        hx-get="/report/c5/content/{{$idConcept}}"
                                        hx-target="#reportArea"
                                    >
                                        <x-element.concept type="{{$relation['type']}}"
                                                           name="{{$relation['name']}}"></x-element.concept>
                                    </a>
                                </button>
                            @endforeach
                        </div>
                    </x-card-plain>
                @endif
            @endforeach
        </x-card>
    @endif
    @if(isset($relationTypes['rel_attributeof']) || isset($relationTypes['rel_attributeof_inverse']))
        <x-card title="Attribute" class="frameReport__card frameReport__card--main" open="true">
            @php($i = 0)
            @foreach ($relations as $nameEntry => $relations1)
                @php([$entry, $name, $color] = explode('|', $nameEntry))
                @php($relId = str_replace(' ', '_', $name))
                @if(($entry == 'rel_attributeof') || ($entry == 'rel_attributeof_inverse'))
                    <x-card-plain
                        title="<span style='color:{{$color}}'>{{$name}}</span>"
                        @class(["frameReport__card" => (++$i < count($report['relations']))])
                        class="frameReport__card--internal">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($relations1 as $idConcept => $relation)
                                <button
                                    id="btnRelation_{{$relId}}_{{$idConcept}}"
                                    class="ui button basic"
                                >
                                    <a
                                        hx-get="/report/c5/content/{{$idConcept}}"
                                        hx-target="#reportArea"
                                    >
                                        <x-element.concept type="{{$relation['type']}}"
                                                           name="{{$relation['name']}}"></x-element.concept>
                                    </a>
                                </button>
                            @endforeach
                        </div>
                    </x-card-plain>
                @endif
            @endforeach
        </x-card>
    @endif
    @if(isset($relationTypes['rel_functionof']) || isset($relationTypes['rel_functionof_inverse']))
        <x-card title="Function" class="frameReport__card frameReport__card--main" open="true">
            @php($i = 0)
            @foreach ($relations as $nameEntry => $relations1)
                @php([$entry, $name, $color] = explode('|', $nameEntry))
                @php($relId = str_replace(' ', '_', $name))
                @if(($entry == 'rel_functionof') || ($entry == 'rel_functionof_inverse'))
                    <x-card-plain
                        title="<span style='color:{{$color}}'>{{$name}}</span>"
                        @class(["frameReport__card" => (++$i < count($report['relations']))])
                        class="frameReport__card--internal">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($relations1 as $idConcept => $relation)
                                <button
                                    id="btnRelation_{{$relId}}_{{$idConcept}}"
                                    class="ui button basic"
                                >
                                    <a
                                        hx-get="/report/c5/content/{{$idConcept}}"
                                        hx-target="#reportArea"
                                    >
                                        <x-element.concept type="{{$relation['type']}}"
                                                           name="{{$relation['name']}}"></x-element.concept>
                                    </a>
                                </button>
                            @endforeach
                        </div>
                    </x-card-plain>
                @endif
            @endforeach
        </x-card>
    @endif
    @if(isset($relationTypes['rel_roleof']) || isset($relationTypes['rel_roleof_inverse']))
        <x-card title="Role" class="frameReport__card frameReport__card--main" open="true">
            @php($i = 0)
            @foreach ($relations as $nameEntry => $relations1)
                @php([$entry, $name, $color] = explode('|', $nameEntry))
                @php($relId = str_replace(' ', '_', $name))
                @if(($entry == 'rel_roleof') || ($entry == 'rel_roleof_inverse'))
                    <x-card-plain
                        title="<span style='color:{{$color}}'>{{$name}}</span>"
                        @class(["frameReport__card" => (++$i < count($report['relations']))])
                        class="frameReport__card--internal">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($relations1 as $idConcept => $relation)
                                <button
                                    id="btnRelation_{{$relId}}_{{$idConcept}}"
                                    class="ui button basic"
                                >
                                    <a
                                        hx-get="/report/c5/content/{{$idConcept}}"
                                        hx-target="#reportArea"
                                    >
                                        <x-element.concept type="{{$relation['type']}}"
                                                           name="{{$relation['name']}}"></x-element.concept>
                                    </a>
                                </button>
                            @endforeach
                        </div>
                    </x-card-plain>
                @endif
            @endforeach
        </x-card>
    @endif
    @if(isset($relationTypes['rel_expressionof']) || isset($relationTypes['rel_expressionof_inverse']))
        <x-card title="Expression" class="frameReport__card frameReport__card--main" open="true">
            @php($i = 0)
            @foreach ($relations as $nameEntry => $relations1)
                @php([$entry, $name, $color] = explode('|', $nameEntry))
                @php($relId = str_replace(' ', '_', $name))
                @if(($entry == 'rel_expressionof') || ($entry == 'rel_expressionof_inverse'))
                    <x-card-plain
                        title="<span style='color:{{$color}}'>{{$name}}</span>"
                        @class(["frameReport__card" => (++$i < count($report['relations']))])
                        class="frameReport__card--internal">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($relations1 as $idConcept => $relation)
                                <button
                                    id="btnRelation_{{$relId}}_{{$idConcept}}"
                                    class="ui button basic"
                                >
                                    <a
                                        hx-get="/report/c5/content/{{$idConcept}}"
                                        hx-target="#reportArea"
                                    >
                                        <x-element.concept type="{{$relation['type']}}"
                                                           name="{{$relation['name']}}"></x-element.concept>
                                    </a>
                                </button>
                            @endforeach
                        </div>
                    </x-card-plain>
                @endif
            @endforeach
        </x-card>
    @endif
    @if(isset($relationTypes['rel_modeledon']) || isset($relationTypes['rel_modeledon_inverse']))
        <x-card title="Model" class="frameReport__card frameReport__card--main" open="true">
            @php($i = 0)
            @foreach ($relations as $nameEntry => $relations1)
                @php([$entry, $name, $color] = explode('|', $nameEntry))
                @php($relId = str_replace(' ', '_', $name))
                @if(($entry == 'rel_modeledon') || ($entry == 'rel_modeledon_inverse'))
                    <x-card-plain
                        title="<span style='color:{{$color}}'>{{$name}}</span>"
                        @class(["frameReport__card" => (++$i < count($report['relations']))])
                        class="frameReport__card--internal">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($relations1 as $idConcept => $relation)
                                <button
                                    id="btnRelation_{{$relId}}_{{$idConcept}}"
                                    class="ui button basic"
                                >
                                    <a
                                        hx-get="/report/c5/content/{{$idConcept}}"
                                        hx-target="#reportArea"
                                    >
                                        <x-element.concept type="{{$relation['type']}}"
                                                           name="{{$relation['name']}}"></x-element.concept>
                                    </a>
                                </button>
                            @endforeach
                        </div>
                    </x-card-plain>
                @endif
            @endforeach
        </x-card>
    @endif
    @if(isset($relationTypes['rel_recruitedfrom']) || isset($relationTypes['rel_recruitedfrom_inverse']))
        <x-card title="Recruitment" class="frameReport__card frameReport__card--main" open="true">
            @php($i = 0)
            @foreach ($relations as $nameEntry => $relations1)
                @php([$entry, $name, $color] = explode('|', $nameEntry))
                @php($relId = str_replace(' ', '_', $name))
                @if(($entry == 'rel_recruitedfrom') || ($entry == 'rel_recruitedfrom_inverse'))
                    <x-card-plain
                        title="<span style='color:{{$color}}'>{{$name}}</span>"
                        @class(["frameReport__card" => (++$i < count($report['relations']))])
                        class="frameReport__card--internal">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($relations1 as $idConcept => $relation)
                                <button
                                    id="btnRelation_{{$relId}}_{{$idConcept}}"
                                    class="ui button basic"
                                >
                                    <a
                                        hx-get="/report/c5/content/{{$idConcept}}"
                                        hx-target="#reportArea"
                                    >
                                        <x-element.concept type="{{$relation['type']}}"
                                                           name="{{$relation['name']}}"></x-element.concept>
                                    </a>
                                </button>
                            @endforeach
                        </div>
                    </x-card-plain>
                @endif
            @endforeach
        </x-card>
    @endif
    @if(isset($relationTypes['rel_subtypeof']) || isset($relationTypes['rel_subtypeof_inverse']))
        <x-card title="Hierarchy" class="frameReport__card frameReport__card--main" open="true">
            @php($i = 0)
            @foreach ($relations as $nameEntry => $relations1)
                @php([$entry, $name, $color] = explode('|', $nameEntry))
                @php($relId = str_replace(' ', '_', $name))
                @if(($entry == 'rel_subtypeof') || ($entry == 'rel_subtypeof_inverse'))
                    <x-card-plain
                        title="<span style='color:{{$color}}'>{{$name}}</span>"
                        @class(["frameReport__card" => (++$i < count($report['relations']))])
                        class="frameReport__card--internal">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($relations1 as $idConcept => $relation)
                                <button
                                    id="btnRelation_{{$relId}}_{{$idConcept}}"
                                    class="ui button basic"
                                >
                                    <a
                                        hx-get="/report/c5/content/{{$idConcept}}"
                                        hx-target="#reportArea"
                                    >
                                        <x-element.concept type="{{$relation['type']}}"
                                                           name="{{$relation['name']}}"></x-element.concept>
                                    </a>
                                </button>
                            @endforeach
                        </div>
                    </x-card-plain>
                @endif
            @endforeach
        </x-card>
    @endif
    {{--        @if(!empty($relations))--}}
    {{--            <x-card title="Relations" class="frameReport__card frameReport__card--main" open="true">--}}
    {{--                @php($i = 0)--}}
    {{--                @foreach ($relations as $nameEntry => $relations1)--}}
    {{--                    @php([$entry, $name, $color] = explode('|', $nameEntry))--}}
    {{--                    @php($relId = str_replace(' ', '_', $name))--}}
    {{--                    <x-card-plain--}}
    {{--                        title="<span style='color:{{$color}}'>{{$name}}</span>"--}}
    {{--                        @class(["frameReport__card" => (++$i < count($report['relations']))])--}}
    {{--                        class="frameReport__card--internal">--}}
    {{--                        <div class="flex flex-wrap gap-1">--}}
    {{--                            @foreach ($relations1 as $idConcept => $relation)--}}
    {{--                                <button--}}
    {{--                                    id="btnRelation_{{$relId}}_{{$idConcept}}"--}}
    {{--                                    class="ui button basic"--}}
    {{--                                >--}}
    {{--                                    <a--}}
    {{--                                        href="/report/c5/{{$idConcept}}"--}}
    {{--                                    >--}}
    {{--                                        <x-element.concept type="{{$relation['type']}}"--}}
    {{--                                                           name="{{$relation['name']}}"></x-element.concept>--}}
    {{--                                    </a>--}}
    {{--                                </button>--}}
    {{--                            @endforeach--}}
    {{--                        </div>--}}
    {{--                    </x-card-plain>--}}
    {{--                @endforeach--}}
    {{--            </x-card>--}}
    {{--    @endif--}}
</div>
