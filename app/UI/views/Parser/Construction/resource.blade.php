<x-layout.resource>
    <x-slot:head>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['','Parser V4 Constructions']]">
        </x-partial::breadcrumb>
    </x-slot:head>

    <x-slot:title>Constructions</x-slot:title>

    <x-slot:actions>
        <x-button
            label="New Construction"
            color="secondary"
            hx-get="/parser/construction/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
        <x-button
            label="Import"
            color="primary"
            icon="upload"
            hx-get="/parser/construction/import-form"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
        <x-button
            label="Export"
            color="primary"
            icon="download"
            hx-get="/parser/construction/export-form"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>

    <x-slot:search>
        <x-form-search>
            <div class="field">
                <select
                    id="idGrammarGraph"
                    name="idGrammarGraph"
                    class="ui dropdown"
                    hx-post="/parser/construction/grid/search"
                    hx-trigger="change"
                    hx-target="#gridConstruction"
                    hx-swap="innerHTML"
                    hx-include="[name='name'],[name='constructionType'],[name='enabled']"
                >
                    <option value="">All Grammars</option>
                    @foreach($grammars as $grammar)
                        <option value="{{ $grammar->idGrammarGraph }}">{{ $grammar->name }} ({{ $grammar->language }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <x-search-field
                    id="name"
                    placeholder="Search by name"
                    hx-post="/parser/construction/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#gridConstruction"
                    hx-swap="innerHTML"
                    hx-include="[name='idGrammarGraph'],[name='constructionType'],[name='enabled']"
                ></x-search-field>
            </div>
            <div class="field">
                <select
                    id="constructionType"
                    name="constructionType"
                    class="ui dropdown"
                    hx-post="/parser/construction/grid/search"
                    hx-trigger="change"
                    hx-target="#gridConstruction"
                    hx-swap="innerHTML"
                    hx-include="[name='idGrammarGraph'],[name='name'],[name='enabled']"
                >
                    <option value="">All Types</option>
                    <option value="mwe">MWE</option>
                    <option value="phrasal">Phrasal</option>
                    <option value="clausal">Clausal</option>
                    <option value="sentential">Sentential</option>
                </select>
            </div>
            <div class="field">
                <select
                    id="enabled"
                    name="enabled"
                    class="ui dropdown"
                    hx-post="/parser/construction/grid/search"
                    hx-trigger="change"
                    hx-target="#gridConstruction"
                    hx-swap="innerHTML"
                    hx-include="[name='idGrammarGraph'],[name='name'],[name='constructionType']"
                >
                    <option value="">All Status</option>
                    <option value="1">Enabled</option>
                    <option value="0">Disabled</option>
                </select>
            </div>
        </x-form-search>
    </x-slot:search>

    <x-slot:grid>
        <div
            id="gridConstruction"
            hx-trigger="load"
            hx-target="this"
            hx-swap="outerHTML"
            hx-get="/parser/construction/grid"
        ></div>
    </x-slot:grid>

    <x-slot:edit>
        <div id="editArea"></div>
    </x-slot:edit>
</x-layout.resource>
