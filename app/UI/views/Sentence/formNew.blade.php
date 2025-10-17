<x-form
    title="New Lemma"
    hx-post="/lexicon/lemma/new"
>
    <x-slot:fields>
        <div class="field">
            <x-text-field
                label="Lemma (format: lemmaName.pos)"
                id="name"
                value=""
            ></x-text-field>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Save"></x-submit>
    </x-slot:buttons>
</x-form>
