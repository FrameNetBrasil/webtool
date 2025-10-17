<x-layout.browser>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Network Report']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:actions>
    </x-slot:actions>
    <x-slot:main>
        <div class="ui card h-full w-full">
            <div class="flex-grow-0 content h-4rem bg-gray-100">
                <x-form-search
                    id="frameSearch"
                    hx-post="/network/grid"
                    hx-target="#gridArea"
                >
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <x-search-field
                        id="frame"
                        value="{{$search->frame}}"
                        placeholder="Search Frame"
                        class="w-4em"
                    ></x-search-field>
                </x-form-search>
            </div>
            <div class="flex-grow-1 content h-full">
                <div id="gridArea" class="h-full"
                     hx-trigger="load"
                     hx-post="/network/grid"
                >
                </div>
            </div>
        </div>
    </x-slot:main>
</x-layout.browser>

