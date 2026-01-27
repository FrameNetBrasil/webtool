<x-layout.resource>
    <x-slot:head>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['','Parser V4 Grammars']]">
        </x-partial::breadcrumb>
    </x-slot:head>

    <x-slot:title>Grammar Graphs</x-slot:title>

    <x-slot:actions>
        <x-button
            label="New Grammar Graph"
            color="secondary"
            hx-get="/parser/grammar/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>

    <x-slot:search>
        <x-form-search>
            <div class="field">
                <x-search-field
                    id="name"
                    placeholder="Search by name"
                    hx-post="/parser/grammar/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#gridGrammar"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
            <div class="field">
                <x-search-field
                    id="language"
                    placeholder="Language (pt, en, etc.)"
                    hx-post="/parser/grammar/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#gridGrammar"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
        </x-form-search>
    </x-slot:search>

    <x-slot:grid>
        <div
            id="gridGrammar"
            hx-trigger="load"
            hx-target="this"
            hx-swap="outerHTML"
            hx-get="/parser/grammar/grid"
        ></div>
    </x-slot:grid>

    <x-slot:edit>
        <div id="editArea"></div>
    </x-slot:edit>
</x-layout.resource>
