<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Relations']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        Relations
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New RelationGroup"
            color="secondary"
            hx-get="/relations/relationgroup/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
        <x-button
            label="New RelationType"
            color="secondary"
            hx-get="/relations/relationtype/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:search>
        <x-form-search>
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            <div class="field">
                <x-search-field
                    id="relationGroup"
                    value="{{$search->relationGroup}}"
                    placeholder="Search RelationGroup"
                    hx-post="/relations/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#relationsTreeWrapper"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
            <div class="field">
                <x-search-field
                    id="relationType"
                    value="{{$search->relationType}}"
                    placeholder="Search RelationType"
                    hx-post="/relations/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#relationsTreeWrapper"
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
            hx-post="/relations/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div
            id="editArea"
            hx-on:clear-editarea="this.innerHTML=''"
        >
        </div>
    </x-slot:edit>
</x-layout.resource>
