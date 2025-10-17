<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Project/Dataset']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        Project/Dataset
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New Project"
            color="secondary"
            hx-get="/project/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
        <x-button
            label="New Dataset"
            color="secondary"
            hx-get="/dataset/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:search>
        <x-form-search>
            <div class="field">
                <x-search-field
                    id="project"
                    placeholder="Search Project"
                    hx-post="/dataset/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#gridDataset"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
            <div class="field">
                <x-search-field
                    id="dataset"
                    placeholder="Search Dataset"
                    hx-post="/dataset/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#gridDataset"
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
            hx-get="/dataset/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div id="editArea">

        </div>
    </x-slot:edit>
</x-layout.resource>
