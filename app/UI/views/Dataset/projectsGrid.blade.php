<div
    id="gridDatasetProjects"
    class="grid"
    hx-trigger="reload-gridDatasetProjects from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/dataset/{{$idDataset}}/projects/grid"
>
    @foreach($projects as $project)
        <div class="col-4">
            <div class="ui card w-full">
                <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete Project"
                            onclick="messenger.confirmDelete(`Removing association to Project '{{$project->name}}'.`, '/dataset/{{$idDataset}}/projects/{{$project->idProject}}')"
                        ></x-delete>
                    </span>
                    <div
                        class="header"
                    >
                        {{$project->name}}
                    </div>
                    <div class="description">
                        {{$project->description}}
                        @if($project->isSource)
                            [Source]
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
