<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/lexicon3','Lexicon'],['','New Lemma']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container h-full">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Form
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <form
                        hx-post="/lexicon3/form/new"
                    >
                        <input type="hidden" name="idLexiconGroup" value="2">
                        <div class="ui fluid card form-card">
                            <div class="content">
                                <div class="header">
                                    New Form
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="ui form">
                                    <div class="two fields">
                                        <div class="field">
                                            <x-ui::text-field
                                                label="Form"
                                                id="form"
                                                value=""
                                            ></x-ui::text-field>
                                        </div>
                                        <div class="field">
                                            <x-combobox::lexicon-group
                                                id="idLexiconGroup"
                                                label="Group"
                                                :value="0"
                                            ></x-combobox::lexicon-group>
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
