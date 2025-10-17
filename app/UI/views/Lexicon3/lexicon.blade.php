<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/lexicon3','Lexicon'],['', 'Lexicon #' . $lexicon->idLexicon]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container h-full d-flex flex-col">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span>{{$lexicon->form}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$lexicon->idLexicon}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Form '{{$lexicon->form}}'.`, '/lexicon3/form/{{$lexicon->idLexicon}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <dic class="page-subtitle">
                        {{$lexicon->group->name}}
                    </dic>
                </div>
                <div class="page-content">
                    <form>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="idLexicon" value="{{$lexicon->idLexicon}}">
                        <div class="ui fluid card form-card">
                            <div class="content">
                                <div class="header">
                                    Edit Form
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
                                                :value="$lexicon->form"
                                            ></x-ui::text-field>
                                        </div>
                                        <div class="field">
                                            <x-combobox::lexicon-group
                                                id="idLexiconGroup"
                                                label="Group"
                                                :value="$lexicon->idLexiconGroup"
                                            ></x-combobox::lexicon-group>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="extra content">
                                <div class="ui buttons">
                                    <button
                                        class="ui button primary"
                                        hx-put="/lexicon3/lexicon"
                                    >Update</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <form>
                        <input type="hidden" name="idLexiconBase" value="{{$lexicon->idLexicon}}">

                        <div class="ui fluid card form-card">
                            <div class="content">
                                <div class="header">
                                    Add Feature
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="ui form">
                                    <div class="fields">
                                        <div class="field">
                                            <x-combobox::ud-feature
                                                id="idUDFeature"
                                                label="UD Feature"
                                                :value="0"
                                            ></x-combobox::ud-feature>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="extra content">
                                <div class="ui buttons">
                                    <button
                                        class="ui button primary"
                                        hx-post="/lexicon3/feature/new"
                                    >Add Feature</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <h3 class="ui header">Features</h3>
                    @include("Lexicon3.features")
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
