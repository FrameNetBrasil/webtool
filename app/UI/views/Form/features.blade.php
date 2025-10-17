<div
    hx-trigger="reload-gridFeatures from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/form/feature/{{$form->idLexiconExpression}}"
>
    <div class="card-grid dense w-full">
        @foreach($features as $feature)
            <div
                class="ui card option-card"
            >
                <div class="content overflow-hidden">
                    <div
                        class="header d-flex justify-between"
                    >
                        <x-element::udfeature :name="$feature->name"></x-element::udfeature>
                        <div>
                            <x-delete
                                title="delete Feature"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Feature '{{$feature->name}}' from form.`, '/form/feature/{{$form->idLexiconExpression}}/{{$feature->idUDFeature}}')"
                            ></x-delete>
                        </div>
                    </div>
                    <div
                        class="description"
                    >
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
