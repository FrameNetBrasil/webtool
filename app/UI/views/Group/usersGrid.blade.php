<div
    class="card-grid dense pt-2"
    hx-trigger="reload-gridGroupUsers from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/group/{{$idGroup}}/users/grid"
>
    @foreach($users as $user)
        <div
            class="ui card option-card cursor-pointer"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                         title="remove User from Group"
                         onclick="messenger.confirmDelete(`Removing user '{{$user->login}}' from group.`, '/group/{{$idGroup}}/users/{{$user->idUser}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    #{{$user->idUser}}
                </div>
                <div class="description">
                    {{$user->login}} - {{$user->name}}
                </div>
            </div>
        </div>
    @endforeach
</div>
