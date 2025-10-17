<x-form
    hx-post="/group"
>
    <x-slot:fields>
        <x-hidden-field
            id="idGroup"
            :value="$group->idGroup"
        ></x-hidden-field>
        <div class="two fields">
            <div class="field">
                <x-text-field
                    label="Name"
                    id="name"
                    :value="$group->name"
                ></x-text-field>
            </div>
            <div class="field">
                <x-text-field
                    label="Description"
                    id="description"
                    :value="$group->description"
                ></x-text-field>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Save"></x-submit>
    </x-slot:buttons>
</x-form>
