<div
    class="card-grid dense pt-2"
    hx-trigger="reload-gridLayerTypes from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/layers/{{$idLayerGroup}}/layertypes/grid"
>
    @foreach($layerTypes as $layerType)
        <div
            class="ui card option-card cursor-pointer"
            onclick="window.location.assign('/layertype/{{$layerType->idLayerType}}/edit')"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                         title="remove Layer Type from Layer Group"
                         onclick="event.stopPropagation(); messenger.confirmDelete(`Removing layer type '{{$layerType->name}}' from layer group.`, '/layers/{{$idLayerGroup}}/layertypes/{{$layerType->idLayerType}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    #{{$layerType->idLayerType}}
                </div>
                <div class="description">
                    {{$layerType->name}} [{{$layerType->entry}}]
                </div>
                <div class="meta">
                    Order: {{$layerType->layerOrder}}
                </div>
            </div>
        </div>
    @endforeach
</div>
