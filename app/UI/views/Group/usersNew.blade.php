<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idGroup" value="{{$idGroup}}">

            <div class="field">
                <x-combobox.user
                    id="idUser"
                    label="Add User"
                    :value="0"
                ></x-combobox.user>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/group/{{$idGroup}}/users/new"
            >
                Add
            </button>
        </div>
    </div>
</form>
