<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Image/Document']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        Image
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New Image"
            color="secondary"
            hx-get="/image/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:search>
        <x-form-search>
            <div class="field">
                <x-search-field
                    id="dataset"
                    placeholder="Search Dataset"
                    hx-post="/image/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#imageTreeWrapper"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
            <div class="field">
                <x-search-field
                    id="image"
                    placeholder="Search Image"
                    hx-post="/image/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#imageTreeWrapper"
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
            hx-get="/image/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div id="editArea">

        </div>
    </x-slot:edit>
</x-layout.resource>
