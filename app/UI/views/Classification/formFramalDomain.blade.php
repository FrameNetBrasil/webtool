<x-form
    title="Domain"
    hx-post="/frame/classification/domain"
>
    <x-slot:fields>
        <x-hidden-field
            id="idFrame"
            :value="$idFrame"
        ></x-hidden-field>
        <div style="height:300px; overflow:auto">
            <x-checkbox.framal-domain
                id="framalDomain"
                :idFrame="$idFrame"
                label=""
            ></x-checkbox.framal-domain>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Update Domain"></x-submit>
    </x-slot:buttons>
</x-form>
