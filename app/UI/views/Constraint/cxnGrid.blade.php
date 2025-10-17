<div
    class="ui card h-full w-full mb-2"
    hx-trigger="reload-gridConstraintCxn from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/cxn/{{$idConstruction}}/constraints/grid"
>
    <div class="flex-grow-1 content bg-white">
        <div
            id="cxnConstraintGrid"
            class="grid"
        >
            @foreach($constraints as $constraint)
                <div class="col-3">
                    <div class="ui card w-full">
                        <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete Cxn Constraint"
                            onclick="messenger.confirmDelete(`Removing Constraint '{{$constraint->constraintName}}'.`, '/constraint/cxn/{{$constraint->idConstraintInstance}}')"
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
