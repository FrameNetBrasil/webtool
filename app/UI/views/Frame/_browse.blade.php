<x-layout.browser>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Frames']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div class="ui card h-full w-full">
            <div class="flex-grow-0 content h-4rem bg-gray-100">
                <div class="flex justify-content-between">
                    <div>
                        <x-form-search
                            id="frameSearch"
                            hx-post="/frame/grid"
                            hx-target="#gridArea"
                        >
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                            <div class="field">
                            <x-search-field
                                id="frame"
                                value="{{$search->frame}}"
                                placeholder="Search Frame"
                                class="w-20rem"
                            ></x-search-field>
                            </div>
                            <div class="field">
                            <x-search-field
                                id="lu"
                                value="{{$search->lu}}"
                                placeholder="Search LU"
                                class="w-20rem"
                            ></x-search-field>
                            </div>
                            <x-submit
                                label="Search"
                                class="mb-2"
                            ></x-submit>
                            <x-button
                                label="Domains"
                                hx-post="/frame/grid"
                                hx-vals="js:{frame:'',lu:'',byGroup:'domain'}"
                                hx-target="#gridArea"
                                hx-on:htmx:before-request="$('#frame').val('');$('#lu').val('');"
                            ></x-button>
                            <x-button
                                label="Types"
                                hx-post="/frame/grid"
                                hx-vals="js:{frame:'',lu:'',byGroup:'type'}"
                                hx-target="#gridArea"
                                hx-on:htmx:before-request="$('#frame').val('');$('#lu').val('');"
                            ></x-button>
                            <x-button
                                label="Scenarios"
                                hx-post="/frame/grid"
                                hx-vals="js:{frame:'',lu:'',byGroup:'scenario'}"
                                hx-target="#gridArea"
                                hx-on:htmx:before-request="$('#frame').val('');$('#lu').val('');"
                            ></x-button>
                        </x-form-search>
                    </div>
                    <div>
                        <x-link-button
                            label="Create frame"
                            color="secondary"
                            href="/frame/new"
                        ></x-link-button>
                    </div>
                </div>
            </div>
            <div class="flex-grow-1 content h-full">
                <div
                    id="gridArea"
                    class="h-full"
                    hx-trigger="load"
                    hx-post="/frame/grid"
                >
                </div>
            </div>
        </div>
    </x-slot:main>
</x-layout.browser>
