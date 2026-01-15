<x-form>
    <x-slot:fields>
        <x-hidden-field id="idFrame" :value="$idFrame"></x-hidden-field>
            <div class="fields">
                <div class="field">
                    <x-combobox.microframe-relation
                        id="idEntityMicroframe"
                        group="subsumption"
                    ></x-combobox.microframe-relation>
                </div>
                <div class="field w-15em">
                    <x-search::class
                        id="idFrameRelated"
                        label="Related Class"
                        placeholder="Select a Class"
                        search-url="/class/list/forSelect"
                        value=""
                        display-value=""
                        modal-title="Search Class"
                    ></x-search::class>
                </div>
            </div>

    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add Relation" hx-post="/relation/class"></x-submit>
    </x-slot:buttons>
</x-form>
