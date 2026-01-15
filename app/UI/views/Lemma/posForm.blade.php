<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idLemma" value="{{$lemma->idLemma}}">
            <div class="ui form">
                <div class="fields">
                    <div class="field">
                        <x-combobox::ud-pos
                            id="idUDPOS"
                            label="POS"
                            value=""
                        ></x-combobox::ud-pos>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                class="ui button primary"
                hx-post="/lemma/{{$lemma->idLemma}}/pos"
            >Add POS
            </button>
        </div>
    </div>
</form>
