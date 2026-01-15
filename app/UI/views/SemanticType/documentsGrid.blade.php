<div
    id="gridDocuments"
    class="card-grid dense pt-2"
    hx-trigger="reload-gridDocuments from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/corpus/{{$idCorpus}}/documents/grid"
>
    @foreach($documents as $document)
        <div
            class="ui card option-card cursor-pointer"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                         title="remove Document from Corpus"
                         onclick="messenger.confirmDelete(`Removing document '{{$document->name}}' from corpus.`, '/corpus/{{$idCorpus}}/documents/{{$document->idDocument}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    #{{$document->idDocument}}
                </div>
                <div class="description">
                    {{$document->name}}
                </div>
            </div>
        </div>
    @endforeach
</div>
