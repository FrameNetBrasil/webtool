<x-layout.resource>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Sentence']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:title>
        Sentence
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New Sentence"
            color="secondary"
            hx-get="/sentence/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:search>
        <x-form-search>
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                <div class="field">
                    <x-search-field
                        id="document"
                        value="{{$search->document}}"
                        placeholder="Search Document"
                        hx-post="/sentence/grid/search"
                        hx-trigger="input changed delay:500ms, search"
                        hx-target="#sentenceTreeWrapper"
                        hx-swap="innerHTML"
                    ></x-search-field>
                </div>
                <div class="field w-30rem">
                    <x-search-field
                        id="sentence"
                        value="{{$search->sentence}}"
                        placeholder="Search Sentence"
                        hx-post="/sentence/grid/search"
                        hx-trigger="input changed delay:500ms, search"
                        hx-target="#sentenceTreeWrapper"
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
            hx-post="/sentence/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div id="editArea">

        </div>
    </x-slot:edit>
</x-layout.resource>
