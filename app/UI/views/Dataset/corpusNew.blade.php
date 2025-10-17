<form id="formNewDatasetCorpus" class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idDataset" value="{{$idDataset}}">

            <div class="field">
                <x-combobox.corpus
                    id="idCorpus"
                    label="Associated Corpus"
                    :value="null"
                >
                </x-combobox.corpus>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/dataset/{{$idDataset}}/corpus/new"
            >
                Add
            </button>
        </div>
    </div>
</form>
