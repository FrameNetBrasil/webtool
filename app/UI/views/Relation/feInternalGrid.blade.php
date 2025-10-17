<div
    id="gridFEInternalRelation"
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridFEInternalRelation from:body"
    hx-target="this" hx-swap="outerHTML"
    hx-get="/frame/{{$idFrame}}/feRelations/grid"
>
    <div class="flex-grow-1 content bg-white">
        <div
            class="grid"
        >
            @foreach($relations as $relation)
                <div class="col-3">
                    <div class="ui card w-full">
                        <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete FE Relation"
                            onclick="messenger.confirmDelete(`Removing FE Relation '{{$relation->name}}'.`, '/relation/feinternal/{{$relation->idEntityRelation}}')"
                        ></x-delete>
                    </span>
                            <div
                                class="header"
                            >
{{--                                <span class="color_{{$relation->relationType}}">{{$relation->name}}</span>--}}
                                <span style="color:{{$relation->color}}">{{$relation->name}}</span>
                            </div>
                            <x-element.fe
                                name="{{$relation->feName}}"
                                type="{{$relation->feCoreType}}"
                                idColor="{{$relation->feIdColor}}"
                            ></x-element.fe>
                            <x-element.fe
                                name="{{$relation->relatedFEName}}"
                                type="{{$relation->relatedFECoreType}}"
                                idColor="{{$relation->relatedFEIdColor}}"
                            ></x-element.fe>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
