<x-form id="formEdit" title="Domain" :center="false" hx-post="/domain">
    <x-slot:fields>
        <x-hidden-field
            id="idDomain"
            :value="$domain->idDomain"
        ></x-hidden-field>
        <x-text-field
            label="Name"
            id="name"
            :value="$domain->name"
        ></x-text-field>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Save" ></x-submit>
    </x-slot:buttons>
</x-form>
