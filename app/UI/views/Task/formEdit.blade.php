<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idTask" value="{{$task->idTask}}">

            <div class="field">
                <label for="name">Name</label>
                <div class="ui small input">
                    <input type="text" id="name" name="name" value="{{$task->name}}">
                </div>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description">{{$task->description}}</textarea>
            </div>

            <div class="field">
                <x-combobox.project
                    id="idProject"
                    label="Project"
                    value="{{$task->idProject}}"
                >
                </x-combobox.project>
            </div>

            <div class="field">
                <x-combobox.task-group
                    id="idTaskGroup"
                    label="TaskGroup"
                    value="{{$task->idTaskGroup}}"
                >
                </x-combobox.task-group>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/task"
            >
                Save
            </button>
        </div>
    </div>
</form>
