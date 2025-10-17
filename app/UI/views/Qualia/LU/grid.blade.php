<div
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridQualiaLU from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/lu/{{$idLU}}/qualia/grid"
>
    <div class="flex-grow-1 content bg-white">
        <div
            class="grid"
        >
            @foreach($qualiaRelations as $qualia)
                <div class="col-3">
                    <div class="ui card w-full">
                        <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete Qualia"
                            onclick="messenger.confirmDelete(`Removing Qualia '{{$qualia->qlrInfo}}:{{$qualia->lu2Name}}'.`, '/lu/qualia/{{$qualia->idEntityRelation}}')"
                        ></x-delete>
                    </span>
                            <div
                                class="header"
                            >
                                <x-element.constraint
                                    name="{{$qualia->qlrInfo}}"
                                ></x-element.constraint>
                            </div>
                            <div class="description">
                                {{$qualia->lu2Name}}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
