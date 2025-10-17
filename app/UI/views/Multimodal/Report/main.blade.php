<x-layout.report>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Multimodal Report']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:search>
        <x-form-search
            id="multimodalSearch"
            hx-post="/report/multimodal/grid"
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
{{--            <div class="field">--}}
{{--                <x-search-field--}}
{{--                    id="document"--}}
{{--                    value="{{$search->document}}"--}}
{{--                    placeholder="Search Document"--}}
{{--                ></x-search-field>--}}
{{--            </div>--}}
        </x-form-search>
    </x-slot:search>
    <x-slot:grid>
        <div class="flex flex-column">
            <div
                id="gridArea"
                class="mb-2"
                style="height:300px"
            >
                @include("Multimodal.Report.grid")
            </div>
            <div
            >
                @include("Multimodal.Report.video")
            </div>
        </div>
    </x-slot:grid>
    <x-slot:pane>
        <div
            id="reportArea"
            class="h-full overflow-y-auto"
        >
            @includeWhen(!is_null($idDocument),"Multimodal.Report.report")
        </div>
    </x-slot:pane>
</x-layout.report>
