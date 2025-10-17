<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','LU_candidate']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        LU Candidate
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New LU Candidate"
            color="secondary"
            hx-get="/luCandidate/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:search>
        <x-form-search>
            <div class="field">
                <x-search-field
                    id="lu"
                    placeholder="Search LU"
                    hx-post="/luCandidate/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#luTreeWrapper"
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
            hx-get="/luCandidate/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div id="editArea">

        </div>
    </x-slot:edit>
</x-layout.resource>
