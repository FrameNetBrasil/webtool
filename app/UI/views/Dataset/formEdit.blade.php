<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idDataset" value="{{$dataset->idDataset}}">

            <div class="field">
                <label for="name">Name</label>
                <div class="ui small input">
                    <input type="text" id="name" name="name" value="{{$dataset->name}}">
                </div>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description">{{$dataset->description ?? ''}}</textarea>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/dataset"
            >
                Save
            </button>
        </div>
    </div>
</form>
