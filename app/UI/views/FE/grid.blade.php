<div
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridFE from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/frame/{{$idFrame}}/fes/grid"
>
    <div class="flex-grow-1 content bg-white">
        @php(debug($fes))
        @php($coreType = ['cty_core','cty_core-unexpressed','cty_peripheral','cty_extra-thematic'])
        @foreach($coreType as $ct)
            @php($array = $fes[$ct] ?? [])
            @if(!empty($array))
            <h3 class="ui header">{!! config("webtool.fe.coreness.{$ct}") !!}</h3>
            <div
                id="gridFE"
                class="ui grid"
            >
                @foreach($array as $fe)
                    <div class="four wide column">
                        <div class="ui card w-full">
                            <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete FE"
                            onclick="messenger.confirmDelete(`Removing FrameElement '{{$fe->name}}'.`, '/fe/{{$fe->idFrameElement}}')"
                        ></x-delete>
                    </span>
                                <div
                                    class="header"
                                >
                                    <a href="/fe/{{$fe->idFrameElement}}/edit">
                                        <x-element.fe
                                            name="{{$fe->name}}"
                                            type="{{$fe->coreType}}"
                                            idColor="{{$fe->idColor}}"
                                        ></x-element.fe>
                                    </a>
                                </div>
                                <div class="description">
                                    {{$fe->description}}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        @endforeach
    </div>
</div>
