<div
    class="h-full"
    hx-trigger="reload-gridGroup from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/group/grid"
>
    <div class="relative h-full overflow-auto">
        <table class="ui striped small compact table">
            @fragment('search')
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($groups as $group)
                    <tr class="cursor-pointer" onclick="window.location.assign('/group/{{$group->idGroup}}/edit')">
                        <td style="width:80px">
                            #{{$group->idGroup}}
                        </td>
                        <td>
                            <i class="users icon"></i>
                            {{$group->name}}
                        </td>
                        <td>
                            {{$group->description}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            @endfragment
        </table>
    </div>
</div>
