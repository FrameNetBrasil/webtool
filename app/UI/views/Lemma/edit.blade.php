<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/lemma','Lemmas'],['', 'Lemma #' . $lemma->idLexicon]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page d-flex flex-col">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span>{{$lemma->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$lemma->idLemma}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Lemma '{{$lemma->name}}'.`, '/lemma/{{$lemma->idLemma}}')"
                            >Delete
                            </button>
                        </div>
                    </div>
                    <dic class="page-subtitle">
                        Lemma
                    </dic>
                </div>
                <div class="page-content">
                    <x-ui::tabs
                        id="lemmaTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/lemma/'.$lemma->idLemma.'/formEdit'],
                            'expressions' => ['id' => 'expressions', 'label' => 'Expressions', 'url' => '/lemma/'.$lemma->idLemma.'/expressions'],
                            'pos' => ['id' => 'pos', 'label' => 'POS', 'url' => '/lemma/'.$lemma->idLemma.'/pos'],
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
