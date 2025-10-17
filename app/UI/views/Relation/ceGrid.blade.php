<div
    id="gridCERelation"
    title=""
    type="child"
    hx-trigger="reload-gridCERelation from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/ce/relations/{{$idEntityRelation}}/grid"
>
    @foreach($relations as $relation)
        <button
            class="ui button basic grey mb-1"
        >
            <div
                class="flex align-items-center gap-1"
            >
                <div class="col">
                    <x-element.construction
                        name="{{$cxn->name}}"
                    ></x-element.construction>
                    <x-element.ce
                        name="{{$relation->ceName}}"
                        idColor="{{$relation->ceIdColor}}"
                    ></x-element.ce>

                </div>
                <div class="col">
                    <span class="color_{{$relation->entry}}">{{$relation->relationName}}</span>

                </div>
                <div class="col">
                    <x-element.construction
                        name="{{$relatedCxn->name}}"
                    ></x-element.construction>
                    <x-element.ce
                        name="{{$relation->relatedCEName}}"
                        idColor="{{$relation->relatedCEIdColor}}"
                    ></x-element.ce>

                </div>
                <div class="right pl-2">
                    <x-delete
                        title="delete CE Relation"
                        onclick="messenger.confirmDelete(`Removing CE Relation.`, '/relation/ce/{{$relation->idEntityRelation}}')"
                    ></x-delete>
                </div>
            </div>
        </button>
    @endforeach
</div>
