<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idGroup" value="{{$group->idGroup}}">

            <div class="two fields">
                <div class="field">
                    <label for="name">Name</label>
                    <div class="ui small input">
                        <input type="text" id="name" name="name" value="{{$group->name}}">
                    </div>
                </div>
                <div class="field">
                    <label for="description">Description</label>
                    <div class="ui small input">
                        <input type="text" id="description" name="description" value="{{$group->description}}">
                    </div>
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/group"
            >
                Save
            </button>
        </div>
    </div>
</form>
