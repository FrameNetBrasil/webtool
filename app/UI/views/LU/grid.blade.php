<div
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridLU from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/frame/{{$idFrame}}/lus/grid"
>
    <div class="flex-grow-1 content bg-white">
        <div
            id="gridLU"
            class="ui grid"
        >
            @foreach($lus as $lu)
                <div class="four wide column">
                    <div class="ui card w-full">
                        <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete LU"
                            onclick="messenger.confirmDelete(`Removing LU '{{$lu->name}}'.`, '/lu/{{$lu->idLU}}')"
                        ></x-delete>
                    </span>
                            <div
                                class="header"
                            >
                                <a href="/lu/{{$lu->idLU}}/edit">
                                    <x-element.lu :name="$lu->name"></x-element.lu>
                                </a>
                            </div>
                            <div class="description">
                                {{$lu->senseDescription}}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
