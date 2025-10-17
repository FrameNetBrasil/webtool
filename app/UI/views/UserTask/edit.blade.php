<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/task','Task/User'],['', 'UserTask #' . $usertask->idUserTask]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$usertask->userName}} / {{$usertask->taskName}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$usertask->idUserTask}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing UserTask '{{$usertask->idUserTask}}'.`, '/usertask/{{$usertask->idUserTask}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        User: {{$usertask->userName}} assigned to Task: {{$usertask->taskName}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="usertaskTabs"
                        style="secondary pointing"
                        :tabs="[
                            'documents' => ['id' => 'documents', 'label' => 'Documents', 'url' => '/usertask/'.$usertask->idUserTask.'/documents']
                        ]"
                        defaultTab="documents"
                    />
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
