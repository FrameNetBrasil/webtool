<x-layout.report>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','MoCCA']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:search>
        <x-form-search
            id="conceptSearch"
            hx-post="/report/c5/grid"
            hx-target="#gridArea"
        >
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            <div class="field mr-0">
                <x-search-field
                    id="concept"
                    value="{{$search->concept}}"
                    placeholder="Search Concept"
                    class="w-full"
                ></x-search-field>
            </div>
            <div class="field">
                <x-button-reload></x-button-reload>
            </div>
        </x-form-search>
    </x-slot:search>
    <x-slot:grid>
        <div
            id="gridArea"
            class="h-full"
        >
            @include("C5.Report.grid")
        </div>
    </x-slot:grid>
    <x-slot:pane>
        <div
            id="reportArea"
            class="h-full overflow-y-auto"
        >
            @includeWhen(!is_null($idConcept),"C5.Report.report")
        </div>
    </x-slot:pane>
</x-layout.report>
