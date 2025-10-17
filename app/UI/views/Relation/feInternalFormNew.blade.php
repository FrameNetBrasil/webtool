<div id="feInternalFormNew">
    @fragment('form')
        <x-form
            hx-post="/relation/feinternal"
{{--            hx-target="#feInternalFormNew"--}}
        >
            <x-slot:fields>
                <x-hidden-field
                    id="idFrame"
                    :value="$idFrame"
                ></x-hidden-field>
                <div class="field">
                    <x-checkbox.fe-frame
                        id="idFrameElementRelated"
                        :idFrame="$idFrame"
                        :value="$idFrameElementRelated ?? []"
                    ></x-checkbox.fe-frame>
                </div>
                <div class="field">
                    <x-combobox.relation
                        id="relationTypeFEInternal"
                        group="fe"
                        :value="$relationType ?? null"
                    ></x-combobox.relation>
                </div>
            </x-slot:fields>
            <x-slot:buttons>
                <x-submit
                    label="Add Relation"
                ></x-submit>
            </x-slot:buttons>
        </x-form>
    @endfragment
</div>
