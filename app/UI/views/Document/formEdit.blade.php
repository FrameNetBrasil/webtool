<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idDocument" value="{{$document->idDocument}}">

            <div class="field">
                <label for="name">Name</label>
                <div class="ui small input">
                    <input type="text" id="name" name="name" value="{{$document->name}}">
                </div>
            </div>

            <div class="field">
                <label for="corpusName">Corpus</label>
                <div class="ui small input">
                    <input type="text" id="corpusName" name="corpusName" value="{{$document->corpusName ?? 'No corpus assigned'}}" readonly>
                </div>
                <div class="ui message">
                    Use the "Corpus" tab to assign this document to a corpus.
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/document/update"
            >
                Save
            </button>
        </div>
    </div>
</form>
