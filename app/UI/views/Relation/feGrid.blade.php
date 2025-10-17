<div
    id="gridFERelation"
    title=""
    type="child"
    hx-trigger="reload-gridFERelation from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/fe/relations/{{$idEntityRelation}}/grid"
>
    @foreach($relations as $relation)
        <button
            class="ui button basic grey mb-1"
        >
            <div
                class="d-flex justify-left items-center gap-1"
            >
                <div class="col">
                    <x-element.frame
                        name="{{$frame->name}}"
                    ></x-element.frame>
                    <x-element.fe
                        name="{{$relation->feName}}"
                        type="{{$relation->feCoreType}}"
                        idColor="{{$relation->feIdColor}}"
                    ></x-element.fe>

                </div>
                <div class="col">
                    <span class="color_{{$relation->entry}}">{{$relation->relationName}}</span>

                </div>
                <div class="col">
                    <x-element.frame
                        name="{{$relatedFrame->name}}"
                    ></x-element.frame>
                    <x-element.fe
                        name="{{$relation->relatedFEName}}"
                        type="{{$relation->relatedFECoreType}}"
                        idColor="{{$relation->relatedFEIdColor}}"
                    ></x-element.fe>

                </div>
                <div class="right pl-2">
                    <x-delete
                        title="delete FE Relation"
                        onclick="messenger.confirmDelete(`Removing FE Relation.`, '/relation/fe/{{$relation->idEntityRelation}}')"
                    ></x-delete>
                </div>
            </div>
        </button>
    @endforeach
</div>
