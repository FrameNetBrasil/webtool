<div class="flex w-full mb-2">
    <div class="flex-grow-0">
        <i
            hx-target="#gridCxnRelations"
            hx-swap="outerHTML"
            hx-get="/cxn/{{$idCxnBase}}/relations/grid"
            class="times icon cursor-pointer pr-4"
        ></i>
    </div>
    <div class="flex-grow-1">
        <div
            class="ui card w-full mb-2"
        >
            <div class="content bg-white">
                <h3 class="ui header">
                    CE-CE Relation for [<span class="color_cxn">{{$cxn->name}}</span>
                    <span class='color_{{$relation->relationType}}'>{{$relation->name}}</span>
                    <span class="color_frame">{{$relatedCxn->name}}]</span></h3>
            </div>
            <div class="content bg-white">
                <div
                    hx-trigger="load"
                    hx-target="this"
                    hx-swap="outerHTML"
                    hx-get="/ce/relations/{{$idEntityRelation}}/formNew"
                ></div>
                <div
                    hx-trigger="load"
                    hx-target="this"
                    hx-swap="outerHTML"
                    hx-get="/ce/relations/{{$idEntityRelation}}/grid"
                ></div>
            </div>
        </div>
    </div>
</div>
