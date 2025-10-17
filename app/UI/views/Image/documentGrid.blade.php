<div
    id="gridDatasetCorpus"
    class="grid"
    hx-trigger="reload-gridImageDocument from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/image/{{$idImage}}/document/grid"
>
    @foreach($documents as $document)
        <div class="col-4">
            <div class="ui card w-full">
                <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete Document-Image"
                            onclick="messenger.confirmDelete(`Removing association to Document '{{$document->name}}'.`, '/image/{{$idImage}}/document/{{$document->idDocument}}')"
                        ></x-delete>
                    </span>
                    <div
                        class="header"
                    >
                        {{$document->name}}
                    </div>
                    <div
                        class="description"
                    >
                        #{{$document->idDocument}}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
