<x-form>
    <x-slot:fields>
        <x-hidden-field
            id="idEntityRelation"
            :value="$idEntityRelation"
        ></x-hidden-field>
        <div class="fields">
            <div class="field w-20rem">
                <x-combobox.fe-frame
                    id="idFrameElement"
                    :idFrame="$frame->idFrame"
                    label="{{$frame->name}}.FE"
                ></x-combobox.fe-frame>
            </div>
            <div class="color_{{$relation->entry}} w-auto mr-2">{{$relation->name}}</div>
            <div class="field w-20rem">
                <x-combobox.fe-frame
                    id="idFrameElementRelated"
                    :idFrame="$relatedFrame->idFrame"
                    label="{{$relatedFrame->name}}.FE"
                ></x-combobox.fe-frame>
            </div>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Add Relation" hx-post="/relation/fe"></x-submit>
    </x-slot:buttons>
</x-form>
