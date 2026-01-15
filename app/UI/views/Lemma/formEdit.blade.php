<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idLemma" value="{{$lemma->idLemma}}">
            <div class="ui form">
                <div class="three fields">
                    <div class="field">
                        <x-ui::text-field
                            label="Name (unflected form)"
                            id="name"
                            :value="$lemma->name"
                        ></x-ui::text-field>
                    </div>
                    <div class="field">
                        <x-combobox::language
                            id="idLanguage"
                            label="Language"
                            :value="$lemma->idLanguage"
                        ></x-combobox::language>
                    </div>

                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/lemma"
            >
                Save
            </button>
        </div>
    </div>
</form>
