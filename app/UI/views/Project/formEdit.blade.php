<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idProject" value="{{$project->idProject}}">

            <div class="field">
                <label for="name">Name</label>
                <div class="ui small input">
                    <input type="text" id="name" name="name" value="{{$project->name}}">
                </div>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description">{{$project->description}}</textarea>
            </div>

            <div class="field">
                <x-combobox.project-group
                    label="Project Group"
                    id="idProjectGroup"
                    :value="$project->idProjectGroup"
                ></x-combobox.project-group>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/project"
            >
                Save
            </button>
        </div>
    </div>
</form>
