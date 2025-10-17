<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Group/User']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        Group/User
    </x-slot:title>
    <x-slot:search>
        <x-form-search>
            <div class="field">
                <x-search-field
                    id="group"
                    placeholder="Search Group"
                    hx-post="/user/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#userTreeWrapper"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
            <div class="field">
                <x-search-field
                    id="user"
                    placeholder="Search Login/Email/Name"
                    hx-post="/user/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#userTreeWrapper"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
        </x-form-search>
    </x-slot:search>
    <x-slot:actions>
        <x-button
            label="New Group"
            color="secondary"
            hx-get="/group/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
        <x-button
            label="New User"
            color="secondary"
            hx-get="/user/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:grid>
        <div
            hx-trigger="load"
            hx-target="this"
            hx-swap="outerHTML"
            hx-get="/user/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div id="editArea">

        </div>
    </x-slot:edit>
</x-layout.resource>
