<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/project','Project/Dataset'],['', 'Project #' . $project->idProject]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$project->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$project->idProject}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Project '{{$project?->name}}'.`, '/project/{{$project->idProject}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$project->description}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="projectTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/project/'.$project->idProject.'/formEdit'],
                            'datasets' => ['id' => 'datasets', 'label' => 'Datasets', 'url' => '/project/'.$project->idProject.'/datasets'],
                            'managers' => ['id' => 'managers', 'label' => 'Managers', 'url' => '/project/'.$project->idProject.'/users']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>