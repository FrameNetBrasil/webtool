<div
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridConstraintCE from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/ce/{{$idConstructionElement}}/constraints/grid"
>
    <div class="flex-grow-1 content bg-white">
        <div
            id="ceConstraintGrid"
            class="grid"
        >
            @foreach($constraints as $constraint)
                <div class="col-3">
                    <div class="ui card w-full">
                        <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete CE Constraint"
                            onclick="messenger.confirmDelete(`Removing Constraint '{{$constraint->constraintName}}'.`, '/constraint/ce/{{$constraint->idConstraintInstance}}')"
                        ></x-delete>
                    </span>
                            <div
                                class="header"
                            >
                                <x-element.constraint
                                    name="{{$constraint->constraintName}}"
                                ></x-element.constraint>
                            </div>
                            <div class="description">
                                {{$constraint->idConstrainedByName}}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
