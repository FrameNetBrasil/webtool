<div
    id="gridFrameRelations"
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridFrameRelation from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/frame/{{$idFrame}}/relations/grid"
>
    <div class="flex-grow-1 content bg-white">

        <div
            id="gridFrameRelationsContent"
            {{--            class="grid"--}}
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
                        @foreach ($relations1 as $idRelatedFrame => $relation)
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
                                        hx-get="/fe/relations/{{$relation['idEntityRelation']}}/frame/{{$idFrame}}"
                                        class="fe-fe cursor-pointer right pl-2"
                                    >
                                        FE-FE
                                    </a>
                                    <div class="right pl-2">
                                        <x-delete
                                            title="delete Relation"
                                            onclick="messenger.confirmDelete(`Removing Relation '{{$name}} {{$relation['name']}}'.`, '/relation/frame/{{$relation['idEntityRelation']}}')"
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
