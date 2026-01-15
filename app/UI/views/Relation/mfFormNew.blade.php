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
                    <x-search::microframe
                        id="idFrameRelated"
                        label="Related Microframe"
                        placeholder="Select a Microframe"
                        search-url="/microframe/list/forSelect"
                        value=""
                        display-value=""
                        modal-title="Search Microframe"
                    ></x-search::microframe>
                </div>
            </div>

    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add Relation" hx-post="/relation/microframe"></x-submit>
    </x-slot:buttons>
</x-form>
