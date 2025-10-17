<x-form
    title="Type"
    hx-post="/frame/classification/type"
>
    <x-slot:fields>
        <x-hidden-field
            id="idFrame"
            :value="$idFrame"
        ></x-hidden-field>
        <div style="height:300px; overflow:auto">
            <div class="field">
                <x-checkbox.framal-type
                    id="framalType"
                    :idFrame="$idFrame"
                    label=""
                ></x-checkbox.framal-type>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Update Type"></x-submit>
    </x-slot:buttons>
</x-form>
