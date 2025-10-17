<x-layout.report>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','QualiaStructure Report']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:search>
        <x-form-search
            id="qualiaSearch"
            hx-post="/report/qualia/grid"
            hx-target="#gridArea"
        >
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            <x-search-field
                id="qualia"
                value="{{$search->qualia}}"
                placeholder="Search qualia relation"
                class="w-full"
            ></x-search-field>
        </x-form-search>
    </x-slot:search>
    <x-slot:grid>
        <div
            id="gridArea"
            class="h-full"
        >
            @include("Qualia.Report.grid")
        </div>
    </x-slot:grid>
    <x-slot:pane>
        <div
            id="reportArea"
            class="h-full overflow-y-auto"
        >
            @includeWhen(!is_null($idQualia),"Qualia.Report.report")
        </div>
    </x-slot:pane>
</x-layout.report>
