<x-form
    title="Namespace"
    hx-post="/frame/classification/namespace"
>
    <x-slot:fields>
        <x-hidden-field
            id="idFrame"
            :value="$idFrame"
        ></x-hidden-field>
        <div style="height:300px; overflow:auto">
            <div class="field">
                <x-checkbox.framal-namespace
                    id="idNamespace"
                    :idFrame="$idFrame"
                    label=""
                ></x-checkbox.framal-namespace>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Update Namespace"></x-submit>
    </x-slot:buttons>
</x-form>
