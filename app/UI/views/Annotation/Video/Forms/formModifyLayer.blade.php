<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idDocument" value="{{$object->idDocument}}">
            <input type="hidden" name="idObject" value="{{$object->idObject}}">
            <input type="hidden" name="annotationType" value="{{$annotationType}}">
            <div class="two fields">
                <div class="field">
                    <x-combobox.layer-deixis
                        id="idLayerTypeNew"
                        label="Layer"
                        :value="$object->idLayerType"
                        onChange="htmx.ajax('GET','/annotation/video/labels/' + value, '#comboboxLabel')"
                    ></x-combobox.layer-deixis>
                </div>
                <div id="comboboxLabel" class="field">
                    @include("Annotation.Video.Partials.comboboxLabel")
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/annotation/video/updateLayerLabel"
            >
                Save
            </button>
        </div>
    </div>
</form>
