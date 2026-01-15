<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idLayerGroup" value="{{$layerGroup->idLayerGroup}}">

            <div class="field">
                <label for="name">Name</label>
                <div class="ui small input">
                    <input type="text" id="name" name="name" value="{{$layerGroup->name}}">
                </div>
            </div>

            <div class="field">
                <label for="type">Type</label>
                <div class="ui small input">
                    <input type="text" id="type" name="type" value="{{$layerGroup->type}}">
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/layers"
            >
                Save
            </button>
        </div>
    </div>
</form>
