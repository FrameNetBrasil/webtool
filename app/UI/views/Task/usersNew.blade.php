<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idTask" value="{{$idTask}}">

            <div class="field">
                <x-combobox.user
                    id="idUser"
                    label="User"
                    value="0"
                >
                </x-combobox.user>
            </div>

            <div class="two fields">
                <div class="field">
                    <div class="ui checkbox">
                        <input type="checkbox" name="isActive" value="1">
                        <label for="isActive">Is Active?</label>
                    </div>
                </div>
                <div class="field">
                    <div class="ui checkbox">
                        <input type="checkbox" name="isIgnore" value="1">
                        <label for="isIgnore">Is Ignore?</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/task/{{$idTask}}/users/new"
            >
                Add
            </button>
        </div>
    </div>
</form>
