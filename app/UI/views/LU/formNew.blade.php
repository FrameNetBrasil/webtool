<form>
    <input type="hidden" name="idFrame" value="{{$idFrame}}">
    <div class="ui fluid card form-card">
        <div class="content">
            <div class="ui form">
                <div class="two fields">
                    <div class="field">
                        <x-search::lemma
                            id="idLemma"
                            label="Lemma"
                            search-field="lemmaName"
                            value=""
                            display-value=""
                        ></x-search::lemma>
                    </div>
                    <div class="field">
                        <x-combobox.fe-frame
                            id="incorporatedFE"
                            name="incorporatedFE"
                            label="Incorporated FE"
                            style="width:250px"
                            :value="0"
                            :idFrame="$idFrame"
                            :hasNull="false"
                        ></x-combobox.fe-frame>
                    </div>
                </div>
                <div class="field">
                    <x-multiline-field
                        label="Sense Description"
                        id="senseDescription"
                        value=""
                    ></x-multiline-field>
                </div>
                <div class="extra content">
                    <div class="ui buttons">
                        <button
                            class="ui button primary"
                            hx-post="/lu"
                        >Add LU
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
