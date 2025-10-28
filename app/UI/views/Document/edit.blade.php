<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['/corpus','Corpus/Document'],['', 'Document #' . $document->idDocument]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$document->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$document->idDocument}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Document '{{$document?->name}}'.`, '/document/{{$document->idDocument}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$document->corpusName ?? 'No corpus assigned'}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="documentTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/document/'.$document->idDocument.'/formEdit'],
                            'corpus' => ['id' => 'corpus', 'label' => 'Corpus', 'url' => '/document/'.$document->idDocument.'/formCorpus'],
                            'entries' => ['id' => 'entries', 'label' => 'Translations', 'url' => '/document/'.$document->idDocument.'/entries']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
