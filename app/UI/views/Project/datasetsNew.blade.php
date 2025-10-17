<form id="formNewDataset" class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idProject" value="{{$idProject}}">

            <div class="field">
                <x-combobox.dataset
                    id="idDataset"
                    label="Use existing dataset"
                    :value="0"
                ></x-combobox.dataset>
            </div>

            <div class="field">
                <label>OR create a new one</label>
            </div>

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
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/project/datasets/new"
            >
                Save
            </button>
        </div>
    </div>
</form>
