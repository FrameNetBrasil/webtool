<x-form id="formNewDocumentVideo" title="Add Document" :center="false"  hx-post="/video/{{$idVideo}}/document/new">
    <x-slot:fields>
        <x-hidden-field id="idVideo" value="{{$idVideo}}"></x-hidden-field>
        <x-combobox.document
            id="idDocument"
            label="Associated Document"
            :value="null"
        >
        </x-combobox.document>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add"></x-submit>
    </x-slot:buttons>
</x-form>
