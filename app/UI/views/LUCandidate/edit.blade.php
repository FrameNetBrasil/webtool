@use("Carbon\Carbon")
<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/luCandidate','LU Candidate'],['', 'LU #' . $luCandidate->idLU]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container h-full d-flex flex-col">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span>{{$luCandidate->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$luCandidate->idLU}}
                            </div>
                            @if($isManager)
                                <button
                                    class="ui danger button"
                                    x-data
                                    @click.prevent="messenger.confirmDelete(`Removing LU candidate '{{$luCandidate->name}}'.`, '/luCandidate/{{$luCandidate->idLU}}')"
                                >Delete
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="page-subtitle">
                        Created by {{$luCandidate->userName}} [{{$luCandidate->email}}]
                        at {!! $luCandidate->createdAt ? Carbon::parse($luCandidate->createdAt)->format("d/m/Y") : '-' !!}
                    </div>
                </div>
                <div class="page-content">
                    <form>
                        <input type="hidden" name="idLU" value="{{$luCandidate->idLU}}">
                        <div class="ui fluid card form-card">
                            <div class="content">
                                <div class="header">
                                    Edit LU Candidate
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="ui form">
                                    @if($isManager)
                                        <div class="field">
                                            <x-search::lemma
                                                id="idLemma"
                                                label="Lemma"
                                                search-field="lemmaName"
                                                :value="$luCandidate->idLemma"
                                                :display-value="$luCandidate->name"
                                            ></x-search::lemma>
                                        </div>
                                    @else
                                        <input type="hidden" name="idLemma" value="{{$luCandidate->idLemma}}">
                                    @endif
                                    <div class="field">
                                        <x-multiline-field
                                            label="Sense Description"
                                            id="senseDescription"
                                            :value="$luCandidate->senseDescription ?? ''"
                                        ></x-multiline-field>
                                    </div>
                                    <div class="fields">
                                        <div class="field mr-1">
                                            <x-combobox.frame
                                                id="idFrame"
                                                label="Suggested frame"
                                                placeholder="Frame (min: 3 chars)"
                                                style="width:250px"
                                                class="mb-2"
                                                :value="$luCandidate?->idFrame ?? 0"
                                                :name="$luCandidate->frameName ?? ''"
                                                :hasDescription="false"
                                                onSelect="htmx.ajax('GET','/luCandidate/fes/' + result.idFrame,'#fes');"
                                            ></x-combobox.frame>
                                        </div>
                                        <div id="fes">
                                            <div class="field w-20rem mr-1">
                                                <x-combobox.fe-frame
                                                    id="incorporatedFE"
                                                    name="incorporatedFE"
                                                    label="Incorporated FE"
                                                    style="width:250px"
                                                    :value="$luCandidate?->incorporatedFE ?? 0"
                                                    :idFrame="$luCandidate?->idFrame ?? 0"
                                                    :hasNull="false"
                                                ></x-combobox.fe-frame>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label for="suggestedNewFrame">Suggestion for new Frame</label>
                                            <div class="ui small input">
                                                <input type="text" id="suggestedNewFrame" name="suggestedNewFrame" value="{{$luCandidate?->suggestedNewFrame}}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <x-multiline-field
                                            label="Discussion"
                                            id="discussion"
                                            :value="$luCandidate->discussion ?? ''"
                                        ></x-multiline-field>
                                    </div>
                                    <div class="field">
                                        <label>Reference</label>
                                        <hr/>
                                    </div>
                                    <div class="fields">
                                        <div class="field mr-1">
                                            <x-number-field
                                                label="#idSentence"
                                                id="idDocumentSentence"
                                                :value="$luCandidate->idDocumentSentence"
                                            ></x-number-field>
                                        </div>
                                        <div class="field mr-1">
                                            <x-number-field
                                                label="#idStaticObject"
                                                id="idStaticObject"
                                                :value="$luCandidate->idStaticObject"
                                            ></x-number-field>
                                        </div>
                                        <div class="field mr-1">
                                            <x-number-field
                                                label="#idDynamicObject"
                                                id="idDynamicObject"
                                                :value="$luCandidate->idDynamicObject"
                                            ></x-number-field>
                                        </div>
                                        <div class="field mr-1">
                                            <label></label>
                                            <div
                                                hx-trigger="load, annotationset_deleted from:body"
                                                hx-get="/luCandidate/{{$luCandidate->idLU}}/asLOME"
                                                hx-target="this"
                                                hx-swap="innerHTML"
                                            ></div>
                                        </div>
                                    </div>
                                    <div class="extra content">
                                        <div class="ui buttons">
                                            <button
                                                class="ui button primary"
                                                hx-put="/luCandidate"
                                            >Update LU Candidate
                                            </button>
                                            @if($isManager)
                                                <button
                                                    class="ui secondary button"
                                                    hx-post="/luCandidate/createLU"
                                                >Create LU
                                                </button>
                                            @endif
                                        </div>
                                    </div>
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
