<div
    class="h-full"
    hx-trigger="reload-gridDataset from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/dataset/grid"
>
    <div class="relative h-full overflow-auto">
        <table class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            <tbody>
            @fragment('search')
                @foreach($projects as $idProject => $project)
                    <tr
                        hx-target="#editArea"
                        hx-swap="innerHTML"
                        class="subheader"
                    >
                        <td
                            hx-get="/project/{{$idProject}}/edit"
                            class="cursor-pointer"
                            style="min-width:120px"
                            colspan="3"
                        >
                            <span class="text-blue-900 font-bold">{{$project->name}}</span>
                        </td>
                    </tr>
                    @php($datasetForProject = $datasets[$idProject] ?? [])
                    @foreach($datasetForProject as $dataset)
                        <tr
                            hx-target="#editArea"
                            hx-swap="innerHTML"
                        >
                            <td
                                hx-get="/dataset/{{$dataset->idDataset}}/edit"
                                class="cursor-pointer"
                                style="min-width:120px"
                            >
                                <span class="pl-4">{{$dataset->name}}</span>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            @endfragment
            </tbody>
        </table>
    </div>
</div>
