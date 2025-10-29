<div
    class="card-grid dense pt-2"
    hx-trigger="reload-gridSemanticTypes from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/domain/{{$idDomain}}/semanticTypes/grid"
>
    @foreach($semanticTypes as $semanticType)
        <div
            class="ui card option-card cursor-pointer"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                         title="remove SemanticType from Domain"
                         onclick="messenger.confirmDelete(`Removing SemanticType '{{$semanticType->name}}' from domain.`, '/domain/{{$idDomain}}/semanticTypes/{{$semanticType->idSemanticType}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    #{{$semanticType->idSemanticType}}
                </div>
                <div class="description">
                    {{$semanticType->name}}
                </div>
            </div>
        </div>
    @endforeach
</div>
