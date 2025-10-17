<div style="display:flex; flex-direction: column; width:auto; padding: 8px">
    <x-form
    >
        <x-slot:fields>
            <x-hidden-field
                id="idDocumentSentence"
                value="{{$idDocumentSentence}}"
            ></x-hidden-field>
            <div class="two fields">
                <div class="field">
                    <x-combobox.frame
                        id="idFrame"
                        label="Choose event frame"
                        placeholder="Frame (min: 2 chars)"
                        style="width:250px"
                        class="mb-2"
                    ></x-combobox.frame>
                </div>
                <div class="field">
                    <x-combobox.lu-event
                        id="idLU"
                        label="  or choose an event related LU:"
                        placeholder="LU (min: 2 chars)"
                        width="400px"
                        class="mb-2"
                    ></x-combobox.lu-event>
                </div>
            </div>
        </x-slot:fields>
        <x-slot:buttons>
            <x-button
                id="btnSubmit"
                label="Add Frame"
                hx-target="#frameElementsPane"
                hx-post="/annotation/staticEvent/addFrame"
            ></x-button>
        </x-slot:buttons>

    </x-form>

</div>
<div id="frameElementsPane" class="pt-2">
    @include('Annotation.StaticEvent.fes')
</div>

