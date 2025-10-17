<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/lexicon3','Lexicon'],['','New Form']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container h-full">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Lemma
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <form
                        hx-post="/lexicon3/lemma/new"
                    >
                        <input type="hidden" name="idLexiconGroup" value="2">
                        <div class="ui fluid card form-card">
                            <div class="content">
                                <div class="header">
                                    New Lemma
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="ui form">
                                    <div class="two fields">
                                        <div class="field">
                                            <x-ui::text-field
                                                label="Lemma"
                                                id="form"
                                                value=""
                                            ></x-ui::text-field>
                                        </div>
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
                                <div class="ui buttons">
                                    <button class="ui button primary">Save</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>

