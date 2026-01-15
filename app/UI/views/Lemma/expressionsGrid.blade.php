<div
    hx-trigger="reload-gridExpressions from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/lemma/{{$lemma->idLemma}}/expressionsGrid"
    class="card-grid dense pt-2"
>
    @foreach($expressions as $expression)
        <div
            class="ui card option-card"
        >
            <div class="content overflow-hidden">
                <div
                    class="header d-flex justify-between"
                >
                    <x-ui::element.expression :name="$expression->form"></x-ui::element.expression>
                    <div>
                        <x-delete
                            title="delete Expression"
                            x-data
                            @click.prevent="messenger.confirmDelete(`Removing Expression '{{$expression->form}}' from lemma.`, '/lemma/expression/{{$expression->idLexiconExpression}}')"
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
