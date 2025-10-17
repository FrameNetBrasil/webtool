<div
    class="h-full"
    hx-trigger="reload-gridUser from:body"
    hx-target="this"
    hx-swap="innerHTML"
    hx-post="/user/grid"
>
    <div class="relative h-full overflow-auto">
        <table id="userTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <tbody
                >
                @foreach($groups as $idGroup => $group)
                    <tr
                        hx-target="#editArea"
                        hx-swap="innerHTML"
                        class="subheader"
                    >
                        <td
                            hx-get="/group/{{$idGroup}}/edit"
                            class="cursor-pointer"
                            style="min-width:120px"
                            colspan="3"
                        >
                            <span class="text-blue-900 font-bold">{{$group->name}}</span>
                        </td>
                    </tr>
                    @foreach($users[$idGroup] as $user)
                        <tr
                            hx-target="#editArea"
                            hx-swap="innerHTML"
                        >
                            <td
                                hx-get="/user/{{$user->idUser}}/edit"
                                class="cursor-pointer"
                                style="min-width:120px"
                            >
                                <span>{{$user->login}}</span>
                            </td>
                            <td
                                hx-get="/user/{{$user->idUser}}/edit"
                                class="cursor-pointer"
                                style="min-width:120px"
                            >
                                <span>{{$user->name}}</span>
                            </td>
                            <td>
                                @if($user->status == 'pending')
                                    <button
                                        class="positive ui button tiny"
                                        hx-put="/user/{{$user->idUser}}/authorize"
                                    >
                                        authorize
                                    </button>

                                @else
                                    <button
                                        class="negative ui button tiny"
                                        hx-put="/user/{{$user->idUser}}/deauthorize"
                                    >
                                        deauthorize
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            @endfragment
        </table>
    </div>
</div>
