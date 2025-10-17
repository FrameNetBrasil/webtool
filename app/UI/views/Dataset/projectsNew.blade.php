<x-form id="formNewDatasetProject" title="Add to Project" :center="false"
        hx-post="/dataset/{{$idDataset}}/projects/new">
    <x-slot:fields>
        <x-hidden-field id="idDataset" value="{{$idDataset}}"></x-hidden-field>
        <div class="field">
            <x-combobox.project
                id="idProject"
                label="Associated project"
                value="0"
            >
            </x-combobox.project>
        </div>
        <div class="field">
            <div class="ui checkbox">
                <input type="checkbox"
                       name="isSource"
                       value="1">
                <label for="isSource">Is Source?</label>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add"></x-submit>
    </x-slot:buttons>
</x-form>
