<x-form>
    <x-slot:fields>
        <x-hidden-field id="idFrame" :value="$idFrame"></x-hidden-field>
            <div class="fields">
                <div class="field">
                    <x-combobox.relation
                        id="relationType"
                        group="frame"
                    ></x-combobox.relation>
                </div>
                <div class="field">
                    <x-combobox.frame
                        id="idFrameRelated"
                        label="Related Frame [min: 3 chars]"
                        :hasDescription="false"
                    ></x-combobox.frame>
                </div>
            </div>

    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add Relation" hx-post="/relation/frame"></x-submit>
    </x-slot:buttons>
</x-form>
