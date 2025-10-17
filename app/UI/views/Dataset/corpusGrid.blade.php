<div
    id="gridDatasetCorpus"
    class="card-grid dense pt-2"
    hx-trigger="reload-gridDatasetCorpus from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/dataset/{{$idDataset}}/corpus/grid"
>
    @foreach($corpus as $c)
        <div
            class="ui card option-card cursor-pointer"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    <x-ui::delete
                        title="remove Corpus from Dataset"
                        onclick="messenger.confirmDelete(`Removing association to Corpus '{{$c->name}}'.`, '/dataset/{{$idDataset}}/corpus/{{$c->idCorpus}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    {{$c->name}}
                </div>
                <div class="description">
                    {{$c->description}}
                </div>
            </div>
        </div>
    @endforeach
</div>
