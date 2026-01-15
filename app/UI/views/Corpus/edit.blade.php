<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/corpus','Corpus'],['', 'Corpus #' . $corpus->idCorpus]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$corpus->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$corpus->idCorpus}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Corpus '{{$corpus->name}}'.`, '/corpus/{{$corpus->idCorpus}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$corpus->description ?? ''}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="corpusTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/corpus/'.$corpus->idCorpus.'/formEdit'],
                            'documents' => ['id' => 'documents', 'label' => 'Documents', 'url' => '/corpus/'.$corpus->idCorpus.'/documents'],
                            'entries' => ['id' => 'entries', 'label' => 'Translations', 'url' => '/corpus/'.$corpus->idCorpus.'/entries']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
