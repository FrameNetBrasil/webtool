<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/lexicon3','Lexicon'],['', 'Lemma #' . $lemma->idLexicon]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container h-full d-flex flex-col">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span>{{$lemma->fullNameUD}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$lemma->idLexicon}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Lemma '{{$lemma->fullNameUD}}'.`, '/lexicon3/lemma/{{$lemma->idLexicon}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <dic class="page-subtitle">
                        Lemma
                    </dic>
                </div>
                <div class="page-content">
                    <form>
                        <input type="hidden" name="idLexiconGroup" value="2">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="idLexicon" value="{{$lemma->idLexicon}}">
                        <div class="ui fluid card form-card">
                            <div class="content">
                                <div class="header">
                                    Edit Lemma
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
                                                :value="$lemma->name"
                                            ></x-ui::text-field>
                                        </div>
                                        <div class="field">
                                            <x-combobox::ud-pos
                                                id="idUDPOS"
                                                label="POS"
                                                :value="$lemma->idUDPOS"
                                            ></x-combobox::ud-pos>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="extra content">
                                <div class="ui buttons">
                                    <button
                                        class="ui button primary"
                                        hx-put="/lexicon3/lemma"
                                    >Update</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="ui warning message">
                        <div class="header">
                            Warning!
                        </div>
                        If lemma is a MWE, each expression can be another lemma or a word. Choose wisely.
                    </div>
                    <form>
                        <input type="hidden" name="idLemmaBase" value="{{$lemma->idLexicon}}">

                        <div class="ui fluid card form-card">
                            <div class="content">
                                <div class="header">
                                    Add Expression
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="ui form">
                                    <div class="fields">
                                        <div class="field w-8rem">
                                            <x-combobox::options
                                                label="Type"
                                                id="idLexiconGroup"
                                                :options="[1 => 'word', 2 => 'lemma']"
                                                value=""
                                            ></x-combobox::options>
                                        </div>
                                        <div class="field">
                                            <x-ui::text-field
                                                label="Form"
                                                id="form"
                                                value=""
                                            ></x-ui::text-field>
                                        </div>
                                        <div class="field">
                                            <x-combobox::ud-pos
                                                id="idUDPOSExpression"
                                                label="UDPOS"
                                                :value="$lemma->idUDPOS"
                                            ></x-combobox::ud-pos>
                                        </div>
                                    </div>
                                    <div class="fields">
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
                                <div class="ui buttons">
                                    <button
                                        class="ui button primary"
                                        hx-post="/lexicon3/expression/new"
                                    >Add Expression</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <h3 class="ui header">Expressions</h3>
                    @include("Lexicon3.expressions")
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
