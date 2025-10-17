<div class="ui form">
    <div class="fields">
        <div class="field">
            <x-combobox.layer-deixis
                label=""
                id="idLayerType"
                :value="0"
                class="w-15rem"
            ></x-combobox.layer-deixis>
        </div>
        <div class="two fields" x-data>
            <button
                class="ui button basic secondary"
            >
                <span x-text="'Start: ' + newStartFrame"></span>
            </button>
            <button
                class="ui button basic secondary"
            >
                <span x-text="'End: ' + newEndFrame"></span>
            </button>
        </div>
        <div class="field">
            <x-button
                type="button"
                label="Create New Object"
                onclick="annotation.objects.createNewObjectAtLayer({idLayerType: document.getElementById('idLayerType').value, startFrame: Alpine.store('doStore').newStartFrame, endFrame: Alpine.store('doStore').newEndFrame})"
            ></x-button>
        </div>
    </div>
</div>
