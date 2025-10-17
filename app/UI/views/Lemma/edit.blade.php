<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/lemma','Lemmas'],['', 'Lemma #' . $lemma->idLexicon]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container h-full d-flex flex-col">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span>{{$lemma->fullName}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$lemma->idLemma}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Lemma '{{$lemma->fullName}}'.`, '/lemma/{{$lemma->idLemma}}')"
                            >Delete
                            </button>
                        </div>
                    </div>
                    <dic class="page-subtitle">
                        Lemma
                    </dic>
                </div>
                <div class="page-content">
                    <form>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
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
                                    <div class="three fields">
                                        <div class="field">
                                            <x-ui::text-field
                                                label="Name"
                                                id="name"
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
                                <div class="ui buttons">
                                    <button
                                        class="ui button primary"
                                        hx-put="/lemma/{{$lemma->idLemma}}"
                                    >Update
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <form>
                        <input type="hidden" name="idLemma" value="{{$lemma->idLemma}}">

                        <div class="ui fluid card form-card">
                            <div class="content">
                                <div class="header">
                                    Add Expressions
                                </div>
                                <div class="description">
                                </div>
                            </div>
                            <div class="content">
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
                                <div class="ui buttons">
                                    <button
                                        class="ui button primary"
                                        hx-post="/lemma/{{$lemma->idLemma}}/expression"
                                    >Add Expression
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <h3 class="ui header">Expressions</h3>
                    @include("Lemma.expressions")
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
