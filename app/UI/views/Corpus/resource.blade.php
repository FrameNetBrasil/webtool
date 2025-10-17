<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Corpus/Document']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        Corpus/Document
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New Corpus"
            color="secondary"
            hx-get="/corpus/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
        <x-button
            label="New Document"
            color="secondary"
            hx-get="/document/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:search>
        <x-form-search>
            <div class="field">
                <x-search-field
                    id="corpus"
                    placeholder="Search Corpus"
                    hx-post="/corpus/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#corpusTreeWrapper"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
            <div class="field">
                <x-search-field
                    id="document"
                    placeholder="Search Document"
                    hx-post="/corpus/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#corpusTreeWrapper"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
        </x-form-search>
    </x-slot:search>
    <x-slot:grid>
        <div
            hx-trigger="load"
            hx-target="this"
            hx-swap="outerHTML"
            hx-get="/corpus/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div id="editArea">

        </div>
    </x-slot:edit>
</x-layout.resource>
