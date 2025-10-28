<div
    class="card-grid dense pt-2"
    hx-trigger="reload-gridUserGroups from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/user/{{$idUser}}/groups/grid"
>
    @foreach($groups as $group)
        <div
            class="ui card option-card cursor-pointer"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                         title="remove Group from User"
                         onclick="messenger.confirmDelete(`Removing group '{{$group->name}}' from user.`, '/user/{{$idUser}}/groups/{{$group->idGroup}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    #{{$group->idGroup}}
                </div>
                <div class="description">
                    {{$group->name}}
                </div>
            </div>
        </div>
    @endforeach
</div>
