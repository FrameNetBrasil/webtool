<x-form
    title="Add Dataset"
    hx-post="/image/{{$idImage}}/dataset/new"
>
    <x-slot:fields>
        <x-hidden-field id="idImage" value="{{$idImage}}"></x-hidden-field>
        <div class="formgrid grid">
            <div class="field col">
                <x-combobox.dataset
                    id="idDataset"
                    label="Associated Dataset"
                    :value="0"
                >
                </x-combobox.dataset>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add"></x-submit>
    </x-slot:buttons>
</x-form>
