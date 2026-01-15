<div
    class="card-grid dense pt-2"
    hx-trigger="reload-gridGenericLabels from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/layertype/{{$idLayerType}}/genericlabels/grid"
>
    @foreach($genericLabels as $genericLabel)
        <div
            class="ui card option-card cursor-pointer"
            onclick="window.location.assign('/genericlabel/{{$genericLabel->idGenericLabel}}/edit')"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                         title="remove Generic Label from Layer Type"
                         onclick="event.stopPropagation(); messenger.confirmDelete(`Removing generic label '{{$genericLabel->name}}' from layer type.`, '/layertype/{{$idLayerType}}/genericlabels/{{$genericLabel->idGenericLabel}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    #{{$genericLabel->idGenericLabel}}
                </div>
                <div class="description">
                    {{$genericLabel->name}}
                </div>
                @if($genericLabel->definition)
                <div class="meta">
                    {{Str::limit($genericLabel->definition, 100)}}
                </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
