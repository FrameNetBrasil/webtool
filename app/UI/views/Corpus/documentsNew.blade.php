<form id="formNewDocument" class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idCorpus" value="{{$idCorpus}}">

            <div class="field">
                <x-combobox.document
                    id="idDocument"
                    label="Use existing document"
                    :value="0"
                ></x-combobox.document>
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
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/corpus/documents/new"
            >
                Save
            </button>
        </div>
    </div>
</form>
