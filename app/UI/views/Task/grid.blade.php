<div
    class="h-full"
    hx-trigger="reload-gridTask from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/task/grid"
>
    <div class="relative h-full overflow-auto">
        <table class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            <tbody>
            @fragment('search')
                @foreach($tasks as $idTask => $task)
                    <tr
                        hx-target="#editArea"
                        hx-swap="innerHTML"
                        class="subheader"
                    >
                        <td
                            hx-get="/task/{{$idTask}}/edit"
                            class="cursor-pointer"
                            style="min-width:120px"
                            colspan="3"
                        >
                            <span class="text-blue-900 font-bold">{{$task->name}}</span>
                        </td>
                    </tr>
                    @php($usersForTask = $users[$idTask] ?? [])
                    @foreach($usersForTask as $user)
                        <tr
                            hx-target="#editArea"
                            hx-swap="innerHTML"
                        >
                            <td
                                hx-get="/usertask/{{$user->idUserTask}}/edit"
                                class="cursor-pointer"
                                style="min-width:120px"
                            >
                                <span class="pl-4">
                                    #{{$user->idUser}}  {{$user->name}} [{{$user->email}}]
                                </span>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            @endfragment
            </tbody>
        </table>
    </div>
</div>
