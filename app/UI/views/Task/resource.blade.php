<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Task/User']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        Task/User
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New Task"
            color="secondary"
            hx-get="/task/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:search>
        <x-form-search>

            <div class="field">
                <x-search-field
                    id="task"
                    placeholder="Search Task"
                    hx-post="/task/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#gridDataset"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
            <div class="field">
                <x-search-field
                    id="user"
                    placeholder="Search User"
                    hx-post="/task/grid/search"
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
            hx-get="/task/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div id="editArea">

        </div>
    </x-slot:edit>
</x-layout.resource>
