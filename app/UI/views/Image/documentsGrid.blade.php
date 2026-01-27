<div
    id="gridDocuments"
    class="card-grid dense pt-2"
    hx-trigger="reload-gridDocuments from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/image/{{$idImage}}/documents/grid"
>
    @foreach($documents as $document)
        <div
            class="ui card option-card cursor-pointer"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                         title="remove Document from Corpus"
                         onclick="messenger.confirmDelete(`Removing document '{{$document->name}}' from image.`, '/image/{{$idImage}}/documents/{{$document->idDocument}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    {{$document->name}}
                </div>
                <div class="description">
                    Corpus: {{$document->corpusName}}
                </div>
            </div>
        </div>
    @endforeach
</div>
