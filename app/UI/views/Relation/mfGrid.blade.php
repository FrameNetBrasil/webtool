<div
    id="gridFrameRelations"
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridMicroframeRelation from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/microframe/{{$idFrame}}/relations/grid"
>
    <div class="flex-grow-1 content bg-white">

        <div
            id="gridFrameRelationsContent"
        >
            @php($i = 0)
            @foreach($relations as $direction => $relations1)
                @foreach($relations1 as $nameEntry => $relations2)
                    @php([$entry, $name] = explode('|', $nameEntry))
                    @php($relId = str_replace(' ', '_', $name))
                    @foreach ($relations2 as $idRelatedFrame => $relation)
                        <button
                            id="btnRelation_{{$relId}}_{{$idRelatedFrame}}"
                            class="ui button basic grey"
                        >
                            <div
                                class="d-flex justify-left items-center gap-1"
                            >
                                <a
                                    href="/frame/{{$idRelatedFrame}}"
                                    class="font-bold"
                                >
                                    <x-element.frame name="{{$relation['name']}}"></x-element.frame>
                                </a>
                                <a
                                    hx-target="#gridFrameRelationsContent"
                                    hx-swap="innerHTML"
                                    hx-get="/microframe/relations/{{$relation['idEntityRelation']}}/microframe/{{$idFrame}}"
                                    class="fe-fe cursor-pointer right pl-2"
                                >
                                    FE-FE
                                </a>
                                <div class="right pl-2">
                                    <x-delete
                                        title="delete Relation"
                                        onclick="messenger.confirmDelete(`Removing Relation '{{$name}} {{$relation['name']}}'.`, '/relation/microframe/{{$relation['idEntityRelation']}}')"
                                    ></x-delete>
                                </div>
                            </div>
                        </button>
                    @endforeach
                @endforeach
            @endforeach
        </div>
    </div>
</div>
