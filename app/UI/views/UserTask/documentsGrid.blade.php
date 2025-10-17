<div
    id="gridUserTaskDocuments"
    class="grid"
    hx-trigger="reload-gridUserTaskDocuments from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/usertask/{{$idUserTask}}/documents/grid"
>
    @foreach($documents as $document)
        <div class="col-4">
            <div class="ui card w-full">
                <div class="content">
                    @if($document->idDocument)
                        <span class="right floated">
                        <x-delete
                            title="delete Document"
                            onclick="messenger.confirmDelete(`Removing document '{{$document->documentName}}' from user.`, '/usertask/{{$idUserTask}}/documents/{{$document->idUserTaskDocument}}')"
                        ></x-delete>
                    </span>
                        <div
                            class="header"
                        >
                            #{{$document->idUserTaskDocument}} - Document
                        </div>
                        <div class="description">
                            [#{{$document->idDocument}}] {{$document->documentName}}
                        </div>
                    @else
                        <span class="right floated">
                            <x-delete
                                title="delete Corpus"
                                onclick="messenger.confirmDelete(`Removing corpus '{{$document->corpusName}}' from user.`, '/usertask/{{$idUserTask}}/documents/{{$document->idUserTaskDocument}}')"
                            ></x-delete>
                    </span>
                        <div
                            class="header"
                        >
                            #{{$document->idUserTaskDocument}} - Corpus
                        </div>
                        <div class="description">
                            [#{{$document->idCorpus}}] {{$document->corpusName}}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
