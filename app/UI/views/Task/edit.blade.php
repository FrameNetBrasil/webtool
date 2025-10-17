<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/task','Task/User'],['', 'Task #' . $task->idTask]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$task->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$task->idTask}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Task '{{$task->name}}'.`, '/task/{{$task->idTask}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$task->description}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="taskTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/task/'.$task->idTask.'/formEdit'],
                            'users' => ['id' => 'users', 'label' => 'Users', 'url' => '/task/'.$task->idTask.'/users']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
