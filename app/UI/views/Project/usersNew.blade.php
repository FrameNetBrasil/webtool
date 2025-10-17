<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idProject" value="{{$idProject}}">

            <div class="field">
                <x-combobox.user
                    id="idUser"
                    label="User"
                    value="0"
                >
                </x-combobox.user>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/project/{{$idProject}}/users/new"
            >
                Add
            </button>
        </div>
    </div>
</form>
