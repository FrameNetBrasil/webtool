<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Domain/SemanticType']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        Domain/SemanticType
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New Domain"
            color="secondary"
            hx-get="/domain/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
        <x-button
            label="New ST"
            color="secondary"
            hx-get="/semanticType/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:search>
        <x-form-search
            hx-post="/semanticType/grid/search"
            hx-target="#semanticTypeTreeWrapper"
            hx-swap="innerHTML"
        >
            <div class="field">
                <x-search-field
                    id="semanticType"
                    placeholder="Search SemanticType"
                    hx-trigger="input changed delay:500ms, search"
                ></x-search-field>
            </div>
        </x-form-search>
    </x-slot:search>
    <x-slot:grid>
        <div
            id="gridArea"
            class="h-full"
        >
            @include("SemanticType.grid")
        </div>
    </x-slot:grid>
    <x-slot:edit>
        <div id="editArea">

        </div>
    </x-slot:edit>
</x-layout.resource>
