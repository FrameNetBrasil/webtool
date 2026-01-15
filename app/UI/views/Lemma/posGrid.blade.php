<div
    hx-trigger="reload-gridPOS from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/lemma/{{$lemma->idLemma}}/posGrid"
    class="card-grid dense pt-2"
>
    @foreach($pos as $p)
        <div
            class="ui card option-card"
        >
            <div class="content overflow-hidden">
                <div
                    class="header d-flex justify-between"
                >
                    <x-ui::element.pos :name="$p->POS"></x-ui::element.pos>
                    <div>
                        <x-delete
                            title="delete POS"
                            x-data
                            @click.prevent="messenger.confirmDelete(`Removing POS '{{$p->POS}}' from lemma.`, '/lemma/pos/{{$lemma->idLemma}}/{{$p->idUDPOS}}')"
                        ></x-delete>
                    </div>
                </div>
                <div
                    class="description"
                >
                    <span>{{$p->name}}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
