<form id="formNewDocument" class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idImage" value="{{$idImage}}">

            <div class="field">
                <x-combobox.document
                    id="idDocument"
                    label="Add document"
                    :value="0"
                ></x-combobox.document>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/image/documents/new"
            >
                Save
            </button>
        </div>
    </div>
</form>
