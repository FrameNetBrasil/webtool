<x-layout.report>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Frame Report']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:search>
        <x-form-search
                id="frameSearch"
                hx-post="/report/frame/grid"
                hx-target="#gridArea"
        >
            <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
            <x-search-field
                    id="frame"
                    value="{{$search->frame}}"
                    placeholder="Search Frame"
                    class="w-full"
            ></x-search-field>
        </x-form-search>
    </x-slot:search>
    <x-slot:grid>
        <div
                id="gridArea"
                class="h-full"
        >
            @include("Frame.Report._grid")
        </div>
    </x-slot:grid>
    <x-slot:pane>
        <div
                id="reportArea"
                class="h-full overflow-y-auto"
        >
            @includeWhen(!is_null($idFrame),"Frame.Report.report")
        </div>
    </x-slot:pane>
</x-layout.report>
