<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idDocument" value="{{$object->idDocument}}">
            <input type="hidden" name="idObject" value="{{$object?->idObject}}">
            <input type="hidden" name="annotationType" value="{{$annotationType}}">
            @php($hasLabel = ($annotationType == 'deixis') || ($annotationType == 'canvas'))
            <div class="ui {!! $hasLabel ? 'two' : '' !!} fields">
                @if($hasLabel)
                    <div class="field">
                        @if(!is_null($object->idGenericLabel) || ($object->layerGroup == 'Canvas') || ($object->layerGroup == 'Deixis'))
                            <div class="field">
                                <x-combobox.gl
                                    id="idGenericLabel"
                                    name="idGenericLabel"
                                    label="Label"
                                    :value="$object?->idGenericLabel ?? 0"
                                    :idLayerType="$object?->idLayerType ?? 0"
                                    :hasNull="false"
                                ></x-combobox.gl>
                            </div>
                        @endif
                    </div>
                @endif
                <div class="w-full">
                    <x-ui::frame-fe
                        :object="$object"
                    ></x-ui::frame-fe>
                </div>
            </div>
            <div class="ui two fields">
                <div class="field ml-1">
                    <x-search::lu
                        id="idLU"
                        label="CV Name"
                        placeholder="Select a CV name"
                        search-url="/lu/list/forSelect"
                        value="{{ old('idFrame', $object?->idLU ?? '') }}"
                        display-value="{{ old('frame', $object->lu ?? '') }}"
                        modal-title="Search CV Name"
                    ></x-search::lu>
                </div>
                <div class="field">
                    <label></label>
                    <button
                        type="submit"
                        class="ui primary button"
                        hx-post="/annotation/video/updateObjectAnnotation"
                        hx-target="#o{{$object?->idObject}}"
                        hx-swap="innerHTML"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
