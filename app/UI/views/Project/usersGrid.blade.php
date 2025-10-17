<div
    class="card-grid dense pt-2"
    hx-trigger="reload-gridManagers from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/project/{{$idProject}}/users/grid"
>
    @foreach($managers as $manager)
        <div
            class="ui card option-card cursor-pointer"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                        title="remove User from Project"
                        onclick="messenger.confirmDelete(`Removing manager '{{$manager->name}}' from project.`, '/project/{{$idProject}}/users/{{$manager->idUser}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    #{{$manager->idUser}}
                </div>
                <div class="description">
                    {{$manager->name}}
                </div>
            </div>
        </div>
    @endforeach
</div>
