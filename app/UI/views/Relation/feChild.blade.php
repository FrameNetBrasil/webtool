<div class="flex w-full mb-2">
    <div class="flex-grow-0">
        <i
            hx-target="#gridFrameRelations"
            hx-swap="outerHTML"
            hx-get="/frame/{{$idFrameBase}}/relations/grid"
            class="times icon cursor-pointer pr-4"
        ></i>
    </div>
    <div class="flex-grow-1">
        <div
            class="ui card w-full mb-2"
        >
            <div class="content bg-white">
                <h3 class="ui header">
                    FE-FE Relation for [<span class="color_frame">{{$frame->name}}</span>
                    <span class='color_{{$relation->relationType}}'>{{$relation->name}}</span>
                    <span class="color_frame">{{$relatedFrame->name}}]</span></h3>
            </div>
            <div class="content bg-white">
                <div
                    hx-trigger="load"
                    hx-target="this"
                    hx-swap="outerHTML"
                    hx-get="/fe/relations/{{$idEntityRelation}}/formNew"
                ></div>
                <div
                    hx-trigger="load"
                    hx-target="this"
                    hx-swap="outerHTML"
                    hx-get="/fe/relations/{{$idEntityRelation}}/grid"
                ></div>
            </div>
        </div>
    </div>
</div>
