@use("Carbon\Carbon")
<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/luCandidate','LU Candidate'],['','New']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container h-full d-flex flex-col">
                <div class="page-content">
                    <form>
                        <div class="ui fluid card form-card">
                            <div class="content">
                                <div class="header">
                                    Create LU Candidate
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="ui form">
                                    <div class="field">
                                        <div class="field">
                                            <x-search::lemma
                                                id="idLemma"
                                                label="Lemma"
                                                search-field="lemmaName"
                                                value=""
                                                display-value=""
                                            ></x-search::lemma>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <x-multiline-field
                                            label="Sense Description"
                                            id="senseDescription"
                                            value=""
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
                                                <input type="text" id="suggestedNewFrame" name="suggestedNewFrame" value="">
                                            </div>
                                        </div>
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
                                                value=""
                                            ></x-number-field>
                                        </div>
                                        <div class="field mr-1">
                                            <x-number-field
                                                label="#idStaticObject"
                                                id="idStaticObject"
                                                value=""
                                            ></x-number-field>
                                        </div>
                                        <div class="field mr-1">
                                            <x-number-field
                                                label="#idDynamicObject"
                                                id="idDynamicObject"
                                                value=""
                                            ></x-number-field>
                                        </div>
                                    </div>
                                    <div class="extra content">
                                        <div class="ui buttons">
                                            <button
                                                class="ui button primary"
                                                hx-post="/luCandidate"
                                            >Create LU Candidate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</x-layout.index>

