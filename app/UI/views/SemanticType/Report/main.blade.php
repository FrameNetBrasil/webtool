<x-layout.report>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','SemanticType Report']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:search>
        <x-form-search
            id="semanticTypeSearch"
            hx-post="/report/semanticType/grid"
            hx-target="#gridArea"
        >
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            <x-search-field
                id="semanticType"
                value="{{$search->semanticType}}"
                placeholder="Search SemanticType"
                class="w-full"
            ></x-search-field>
        </x-form-search>
    </x-slot:search>
    <x-slot:grid>
        <div
            id="gridArea"
            class="h-full"
        >
            @include("SemanticType.Report.grid")
        </div>
    </x-slot:grid>
    <x-slot:pane>
        <div
            id="reportArea"
            class="h-full overflow-y-auto"
        >
            @includeWhen(!is_null($idSemanticType),"SemanticType.Report.report")
        </div>
    </x-slot:pane>
</x-layout.report>
