<div
    class="grid"
    hx-trigger="reload-gridImageDataset from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/image/{{$idImage}}/dataset/grid"
>
    @foreach($datasets as $dataset)
        <div class="col-4">
            <div class="ui card w-full">
                <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete Dataset-Image"
                            onclick="messenger.confirmDelete(`Removing association to Dataset '{{$dataset->name}}'.`, '/image/{{$idImage}}/dataset/{{$dataset->idDataset}}')"
                        ></x-delete>
                    </span>
                    <div
                        class="header"
                    >
                        {{$dataset->name}}
                    </div>
                    <div
                        class="description"
                    >
                        #{{$dataset->idDataset}}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
