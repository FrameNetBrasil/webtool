<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','New Task']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
                <div class="page-content">
                    <form class="ui form">
                        <div class="ui card form-card w-full p-1">
                            <div class="content">
                                <div class="header">
                                    Create new Task
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="field">
                                    <label for="name">Name</label>
                                    <div class="ui small input">
                                        <input type="text" id="name" name="name" value="">
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description"></textarea>
                                </div>

                                <div class="field">
                                    <x-combobox.project
                                        id="idProject"
                                        label="Project"
                                        value="0"
                                    >
                                    </x-combobox.project>
                                </div>

                                <div class="field">
                                    <x-combobox.task-group
                                        id="idTaskGroup"
                                        label="TaskGroup"
                                        value="0"
                                    >
                                    </x-combobox.task-group>
                                </div>
                            </div>
                            <div class="extra content">
                                <button
                                    type="submit"
                                    class="ui primary button"
                                    hx-post="/task/new"
                                >
                                    Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</x-layout::index>
