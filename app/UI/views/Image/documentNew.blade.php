<x-form
    title="Add Document"
    hx-post="/image/{{$idImage}}/document/new"
>
    <x-slot:fields>
        <x-hidden-field id="idImage" value="{{$idImage}}"></x-hidden-field>
        <div class="formgrid grid">
            <div class="field col">
                <x-combobox.document
                    id="idDocument"
                    label="Associated Document"
                    :value="null"
                >
                </x-combobox.document>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add"></x-submit>
    </x-slot:buttons>
</x-form>
