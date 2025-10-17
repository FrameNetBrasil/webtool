<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Layers']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        Layers
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New LayerGroup"
            color="secondary"
            hx-get="/layers/layergroup/new"
            hx-target="#editarea"
            hx-swap="innerHTML"
        ></x-button>
        <x-button
            label="New LayerType"
            color="secondary"
            hx-get="/layers/layertype/new"
            hx-target="#editarea"
            hx-swap="innerHTML"
        ></x-button>
        <x-button
            label="New GenericLabel"
            color="secondary"
            hx-get="/layers/genericlabel/new"
            hx-target="#editarea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:search>
        <x-form-search>
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            <div class="field">
                <x-search-field
                    id="layer"
                    value="{{$search->layer}}"
                    placeholder="Search Layer"
                    hx-post="/layers/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#layersTreeWrapper"
                    hx-swap="innerHTML"
                ></x-search-field>
            </div>
            <div class="field">
                <x-search-field
                    id="genericlabel"
                    value="{{$search->genericlabel}}"
                    placeholder="Search GenericLabel"
                    hx-post="/lexicon/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#layersTreeWrapper"
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
            hx-post="/layers/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div
            id="editarea"
            hx-on:clear-editarea="this.innerHTML=''"
        >
        </div>
    </x-slot:edit>
</x-layout.resource>
