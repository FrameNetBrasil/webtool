<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idUser" value="{{$idUser}}">

            <div class="field">
                <x-combobox.group
                    id="idGroupAdd"
                    label="Add Group"
                    :value="0"
                ></x-combobox.group>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/user/{{$idUser}}/groups/new"
            >
                Add
            </button>
        </div>
    </div>
</form>
