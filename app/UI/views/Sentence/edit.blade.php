<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/sentence','Sentence'],['', 'Sentence #' . $sentence->idSentence]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">Sentence #{{$sentence->idSentence}}</span>
                        </div>
                        <div class="page-object-data">
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Sentence '#{{$sentence->idSentence}}'.`, '/sentence/{{$sentence->idSentence}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="sentenceTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/sentence/'.$sentence->idSentence.'/formEdit'],
                            'annotations' => ['id' => 'annotations', 'label' => 'Annotations', 'url' => '/sentence/'.$sentence->idSentence.'/annotations'],
//                            'documents' => ['id' => 'documents', 'label' => 'Documents', 'url' => '/corpus/'.$corpus->idCorpus.'/documents'],
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
