<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/dataset','Dataset'],['', 'Dataset #' . $dataset->idDataset]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$dataset->name}} [{{$dataset->project->name}}]</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$dataset->idDataset}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Dataset '{{$dataset->name}}'.`, '/dataset/{{$dataset->idDataset}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$dataset->description}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="datasetTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/dataset/'.$dataset->idDataset.'/formEdit'],
                            'corpus' => ['id' => 'corpus', 'label' => 'Corpus', 'url' => '/dataset/'.$dataset->idDataset.'/corpus']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
