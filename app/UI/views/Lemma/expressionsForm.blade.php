<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idLemma" value="{{$lemma->idLemma}}">
            <div class="ui form">
                <div class="fields">
                    <div class="field">
                        <x-ui::text-field
                            label="Wordform"
                            id="form"
                            value=""
                        ></x-ui::text-field>
                    </div>
                    <div class="field">
                        <x-ui::text-field
                            label="Position"
                            id="position"
                            :value="1"
                        ></x-ui::text-field>
                    </div>
                    <div class="field">
                        <x-ui::checkbox
                            id="headWord"
                            name="head"
                            label="Is Head?"
                            :active="true"
                        ></x-ui::checkbox>
                    </div>
                    <div class="field">
                        <x-ui::checkbox
                            id="breakBefore"
                            label="Break before?"
                            :active="false"
                        ></x-ui::checkbox>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                class="ui button primary"
                hx-post="/lemma/{{$lemma->idLemma}}/expression"
            >Add Expression
            </button>
        </div>
    </div>
</form>
