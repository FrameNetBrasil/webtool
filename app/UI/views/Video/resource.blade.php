<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Video/Document']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        Video
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New Video"
            color="secondary"
            hx-get="/video/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:search>
        <x-search-field
            id="video"
            placeholder="Search Video"
            hx-post="/video/grid/search"
            hx-trigger="input changed delay:500ms, search"
            hx-target="#videoTreeWrapper"
            hx-swap="innerHTML"
        ></x-search-field>
    </x-slot:search>
    <x-slot:grid>
        <div
            hx-trigger="load"
            hx-target="this"
            hx-swap="outerHTML"
            hx-get="/video/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div id="editArea">

        </div>
    </x-slot:edit>
</x-layout.resource>
