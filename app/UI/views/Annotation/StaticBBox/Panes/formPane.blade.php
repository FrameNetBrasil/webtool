<div class="form">
    <x-form
        hx-post="/annotation/staticBBox/updateObjectAnnotation"
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
                            #{{$object->idStaticObject}}
                        </div>
                    </div>
                </div>
            @endif
        </x-slot:title>
        <x-slot:fields>
            <x-hidden-field id="idDocument" value="{{$object?->idDocument}}"></x-hidden-field>
            <x-hidden-field id="idStaticObject" value="{{$object?->idStaticObject}}"></x-hidden-field>
            <div class="formgroup-inline">
                <div class="field mr-1">
                    <x-combobox.frame
                        id="idFrame"
                        label="Frame"
                        placeholder="Frame (min: 3 chars)"
                        style="width:250px"
                        class="mb-2"
                        :value="$object?->idFrame ?? 0"
                        :name="$object->frame ?? ''"
                        :hasDescription="false"
                        onSelect="htmx.ajax('GET','/annotation/staticBBox/fes/' + result.idFrame,'#fes');"
                    ></x-combobox.frame>
                </div>
                <div id="fes">
                    <div class="field mr-1">
                        <x-combobox.fe-frame
                            id="idFrameElement"
                            name="idFrameElement"
                            label="FE"
                            :value="$object?->idFrameElement ?? 0"
                            :idFrame="$object?->idFrame ?? 0"
                            :hasNull="false"
                        ></x-combobox.fe-frame>
                    </div>
                </div>
                <div class="field mr-1">
                    <x-combobox.lu
                        id="idLU"
                        label="Framed entity"
                        placeholder="LU (min: 2 chars)"
                        class="w-23rem mb-2"
                        :value="$object?->idLU"
                        :name="$object?->lu ?? ''"
                    ></x-combobox.lu>
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
