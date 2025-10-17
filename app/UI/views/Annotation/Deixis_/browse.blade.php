<x-layout.browser>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Deixis Annotation']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div class="ui card h-full w-full">
            <div class="flex-grow-0 content h-4rem bg-gray-100">
                <div class="flex flex align-items-center justify-content-between">
                    <div><h2 class="ui header">Deixis Annotation</h2></div>
                </div>
            </div>
            <div class="flex-grow-0 content h-4rem bg-gray-100">
                <x-form-search
                    hx-post="/annotation/deixis/grid"
                    hx-target="#gridArea"
                >
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <div class="field">
                        <x-search-field
                            id="corpus"
                            value="{{$search->corpus}}"
                            placeholder="Search Corpus"
                        ></x-search-field>
                    </div>
                    <div class="field">
                        <x-search-field
                            id="document"
                            value="{{$search->document}}"
                            placeholder="Search Document"
                        ></x-search-field>
                    </div>
                    <x-submit label="Search"></x-submit>
                </x-form-search>
            </div>
            <div class="flex-grow-1 content h-full">
                <div
                    id="gridArea"
                    class="h-full"
                    hx-trigger="load"
                    hx-post="/annotation/deixis/grid"
                >
                </div>
            </div>
        </div>
    </x-slot:main>
</x-layout.browser>
