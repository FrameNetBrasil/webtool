<x-layout.browser>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Constructions']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div class="ui card h-full w-full">
            <div class="flex-grow-0 content h-4rem bg-gray-100">
                <div class="flex justify-content-between">
                    <div>
                        <x-form-search
                            id="cxnSearch"
                            hx-post="/cxn/grid"
                            hx-target="#gridArea"
                        >
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                            <div class="field">
                            <x-search-field
                                id="cxn"
                                value="{{$search->cxn}}"
                                placeholder="Search Construction"
                                class="w-20rem"
                            ></x-search-field>
                            </div>
                            <div class="field">
                                <x-combobox.cxn-language
                                    id="idLanguage"
                                    value="{{$search->idLanguage}}"
                                ></x-combobox.cxn-language>
                            </div>
                            <x-submit
                                label="Search"
                                class="mb-2"
                            ></x-submit>
                        </x-form-search>
                    </div>
                    <div>
                        <x-link-button
                            label="Create Construction"
                            color="secondary"
                            href="/cxn/new"
                        ></x-link-button>
                    </div>
                </div>
            </div>
            <div class="flex-grow-1 content h-full">
                <div
                    id="gridArea"
                    class="h-full"
                    hx-trigger="load"
                    hx-post="/cxn/grid"
                >
                </div>
            </div>
        </div>
    </x-slot:main>
</x-layout.browser>
