<div
    class="grid"
    hx-trigger="reload-gridSentenceDocument from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/sentence/{{$idSentence}}/document/grid"
>
    @foreach($documents as $document)
        <div class="col-4">
            <div class="ui card w-full">
                <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete Document-Image"
                            onclick="messenger.confirmDelete(`Removing association to Document '{{$document->name}}'.`, '/sentence/{{$idSentence}}/document/{{$document->idDocument}}')"
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
