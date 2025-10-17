<div
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridCE from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/cxn/{{$idConstruction}}/ces/grid"
>
    <div class="flex-grow-1 content bg-white">
{{--        <h3 class="ui header">{!! config("webtool.fe.coreness.{$ct}") !!}</h3>--}}
        <div
            id="gridFE"
            class="grid"
        >
            @foreach($ces as $ce)
                <div class="col-3">
                    <div class="ui card w-full">
                        <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete CE"
                            onclick="messenger.confirmDelete(`Removing ConstructionElement '{{$ce->name}}'.`, '/ce/{{$ce->idConstructionElement}}')"
                        ></x-delete>
                    </span>
                            <div
                                class="header"
                            >
                                <a href="/ce/{{$ce->idConstructionElement}}/edit">
                                    <x-element.ce
                                        name="{{$ce->name}}"
                                        idColor="{{$ce->idColor}}"
                                    ></x-element.ce>
                                </a>
                            </div>
                            <div class="description">
                                {{$ce->description}}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
