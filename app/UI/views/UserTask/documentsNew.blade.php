<x-form
    hx-post="/usertask/documents/new"
>
    <x-slot:fields>
        <x-hidden-field id="idUserTask" value="{{$idUserTask}}"></x-hidden-field>
        <div class="field">
            <x-combobox.corpus
                id="idCorpus"
                label="Corpus"
                value=""
            >
            </x-combobox.corpus>
        </div>
        <div class="field">
            <x-combobox.document
                id="idDocument"
                label="Document"
                value=""
            >
            </x-combobox.document>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add Corpus/Document"></x-submit>
    </x-slot:buttons>
</x-form>
