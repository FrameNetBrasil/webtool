<div
    hx-trigger="reload-gridExpressions from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/lexicon3/lemma/{{$lemma->idLexicon}}/expressions"
>
    <div class="card-grid dense w-full">
        @foreach($expressions as $expression)
            <div
                class="ui card option-card"
            >
                <div class="content overflow-hidden">
                    <div
                        class="header d-flex justify-between"
                        hx-get="/lexicon3/expression/{{$expression->idForm}}"
                        hx-target="#editArea"
                        hx-swap="innerHTML"
                    >
                        <x-ui::element.expression :name="$expression->form"></x-ui::element.expression>
                        <div>
                            <x-delete
                                title="delete Expression"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Expression '{{$expression->form}}' from lemma.`, '/lexicon3/expression/{{$expression->idLexiconExpression}}')"
                            ></x-delete>
                        </div>
                    </div>
                    <div
                        class="description"
                    >
                        <span>Position: {{$expression->position}}</span>
                        <span>{{$expression->head ? ' head ': ''}}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
