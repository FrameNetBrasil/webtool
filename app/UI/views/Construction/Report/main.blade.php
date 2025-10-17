<x-layout.report>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Construction Report']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:search>
        <x-form-search
            id="cxnSearch"
            hx-post="/report/cxn/grid"
            hx-target="#gridArea"
        >
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            <x-search-field
                id="cxn"
                value="{{$search->cxn}}"
                placeholder="Search Cxn"
                class="w-full"
            ></x-search-field>
        </x-form-search>
    </x-slot:search>
    <x-slot:grid>
        <div
            id="gridArea"
            class="h-full"
        >
            @include("Construction.Report.grid")
        </div>
    </x-slot:grid>
    <x-slot:pane>
        <div
            id="reportArea"
            class="h-full overflow-y-auto"
        >
            @includeWhen(!is_null($idConstruction),"Construction.Report.report")
        </div>
    </x-slot:pane>
</x-layout.report>
