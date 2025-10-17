<div
    class="card-grid dense pt-2"
    hx-trigger="reload-gridDatasets from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/project/{{$idProject}}/datasets/grid"
>
    @foreach($datasets as $dataset)
        <div
            class="ui card option-card cursor-pointer"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                         title="remove Dataset from Project"
                         onclick="messenger.confirmDelete(`Removing datase '{{$dataset->name}}' from project.`, '/project/{{$idProject}}/datasets/{{$dataset->idDataset}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    #{{$dataset->idDataset}}
                </div>
                <div class="description">
                    {{$dataset->name}}
                </div>
            </div>
        </div>
    @endforeach
</div>
