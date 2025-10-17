<div
    id="gridUserTask"
    class="card-grid dense pt-2"
    hx-trigger="reload-gridTaskUsers from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/task/{{$idTask}}/users/grid"
>
    @foreach($usertasks as $usertask)
        <div
            class="ui card option-card cursor-pointer"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                        title="remove User from Task"
                        onclick="messenger.confirmDelete(`Removing user '{{$usertask->name}}' from task.`, '/task/{{$idTask}}/users/{{$usertask->idUserTask}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    #{{$usertask->idUser}}
                </div>
                <div class="description">
                    {{$usertask->name}}
                </div>
            </div>
        </div>
    @endforeach
</div>
