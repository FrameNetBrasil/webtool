<div class="form" style="height:320px">
    <x-form
        hx-post="/annotation/dynamicMode/updateObjectAnnotation"
    >
        <x-slot:title>
            @if($order == 0)
                <div class="flex">
                    <div class="title">Current Object: #none</div>
                </div>
            @else
                <div class="flex gap-2">
                    <div class="title">Current Object: #{{$order}}</div>
                    <div class="flex h-2rem gap-2">
                        <div class="ui label">
                            Range
                            <div class="detail">{{$object->startFrame}}/{{$object->endFrame}}</div>
                        </div>
                        <div class="ui label wt-tag-id">
                            #{{$object->idDynamicObject}}
                        </div>
                    </div>
                </div>
            @endif
        </x-slot:title>
        <x-slot:fields>
            <x-hidden-field id="idDocument" value="{{$object?->idDocument}}"></x-hidden-field>
            <x-hidden-field id="idDynamicObject" value="{{$object?->idDynamicObject}}"></x-hidden-field>
            <div class="formgroup-inline">
                <div class="field mr-1">
                    <x-combobox.frame
                        id="idFrame"
                        label="Frame (Event/State)"
                        placeholder="Frame (min: 3 chars)"
                        style="width:250px"
                        class="mb-2"
                        :value="$object?->idFrame ?? 0"
                        :name="$object->frame ?? ''"
                        :hasDescription="false"
                        onSelect="htmx.ajax('GET','/annotation/dynamicMode/fes/' + result.idFrame,'#fes');"
                    ></x-combobox.frame>
                </div>
                <div id="fes" class="field w-17rem mr-1">
                    <x-combobox.fe-frame
                        id="idFrameElement"
                        name="idFrameElement"
                        label="FE (Participant/Prop)"
                        :value="$object?->idFrameElement ?? 0"
                        :idFrame="$object?->idFrame ?? 0"
                        :hasNull="false"
                    ></x-combobox.fe-frame>
                </div>
            </div>
            <div class="formgroup-inline">
                <div class="field mr-1">
                    <x-combobox.lu
                        id="idLU"
                        label="Framed Entity"
                        placeholder="LU (min: 2 chars)"
                        class="w-23rem mb-2"
                        :value="$object?->idLU"
                        :name="$object?->lu ?? ''"
                    ></x-combobox.lu>
                </div>
            </div>
            <div class="formgroup-inline">
                <div class="field mr-1">
                    <x-number-field
                        id="startFrame"
                        label="Start frame"
                        :value="$object?->startFrame ?? 0"
                    ></x-number-field>
                </div>
                <div class="field mr-1">
                    <x-number-field
                        id="endFrame"
                        label="End frame"
                        :value="$object?->endFrame ?? 0"
                    ></x-number-field>
                </div>
                <div class="field ml-3">
                    <label></label>
                    <div class="ui checkbox">
                        <input type="checkbox" name="isBlocked" value="1" {!! $object?->isBlocked ? 'checked' : '' !!}>
                        <label>Is blocked?</label>
                    </div>
                </div>
            </div>
        </x-slot:fields>
        <x-slot:buttons>
            <x-submit label="Save"></x-submit>
            <x-button
                type="button"
                label="Clone"
                color="secondary"
                onclick="annotation.objects.cloneCurrentObject()"
            ></x-button>
        </x-slot:buttons>
    </x-form>
</div>

