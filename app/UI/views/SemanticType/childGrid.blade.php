<div
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridChildST from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/semanticType/{{$idEntity}}/childGrid"
>
    <div class="flex-grow-1 content bg-white">
        <div
            id="gridSemanticType"
            class="ui grid"
        >
            @foreach($relations as $relation)
                <div class="four wide column">
                    <div class="ui card w-full">
                        <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete SemanticType"
                            onclick="manager.confirmDelete(`Removing SemanticType '{{$relation->name}}'.`, '/semanticType/{{$relation->idEntityRelation}}')"
                        ></x-delete>
                    </span>
                            <div
                                class="header"
                            >
                                <x-element.semantictype name="{{$relation->name}}"></x-element.semantictype>
                            </div>
                            <div class="description">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
