<div
    id="gridCxnRelations"
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridCxnRelation from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/cxn/{{$idConstruction}}/relations/grid"
>
    <div class="flex-grow-1 content bg-white">

        <div
            id="gridCxnRelationsContent"
        >
            @php($i = 0)
            @foreach ($relations as $nameEntry => $relations1)
                @php([$entry, $name] = explode('|', $nameEntry))
                @php($relId = str_replace(' ', '_', $name))
                <x-card-plain
                    title="<span class='color_{{$entry}}'>{{$name}}</span>"
                    @class(["frameReport__card" => (++$i < count($report['relations']))])
                    class="frameReport__card--internal">
                    <div class="flex flex-wrap gap-1">
                        @foreach ($relations1 as $idRelatedCxn => $relation)
                            <button
                                id="btnRelation_{{$relId}}_{{$idRelatedCxn}}"
                                class="ui button basic grey"
                            >
                                <div
                                    class="flex align-items-center "
                                >
                                    <a
                                        href="/cxn/{{$idRelatedCxn}}"
                                        class="font-bold"
                                    >
                                        <x-element.construction name="{{$relation['name']}}"></x-element.construction>
                                    </a>
                                    <a
                                        hx-target="#gridCxnRelationsContent"
                                        hx-swap="innerHTML"
                                        hx-get="/ce/relations/{{$relation['idEntityRelation']}}/cxn/{{$idConstruction}}"
                                        class="fe-fe cursor-pointer right pl-2"
                                    >
                                        CE-CE
                                    </a>
                                    <div class="right pl-2">
                                        <x-delete
                                            title="delete Relation"
                                            onclick="messenger.confirmDelete(`Removing Relation '{{$name}} {{$relation['name']}}'.`, '/relation/cxn/{{$relation['idEntityRelation']}}')"
                                        ></x-delete>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </x-card-plain>
            @endforeach
        </div>
    </div>
</div>
