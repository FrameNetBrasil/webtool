<x-layout.report>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','Reframming']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:search>
        <x-form-search
            id="luSearch"
            hx-post="/reframing/grid"
            hx-target="#gridArea"
        >
            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
            <x-search-field
                id="lu"
                :value="$search->lu"
                placeholder="Search LU"
                class="w-full"
            ></x-search-field>
        </x-form-search>
    </x-slot:search>
    <x-slot:grid>
        <div
            id="gridArea"
            class="h-full"
            hx-trigger="load"
            hx-post="/reframing/grid"
        >
        </div>
    </x-slot:grid>
    <x-slot:pane>
        <div
            id="reframingArea"
            class="h-full overflow-y-auto"
            @if(isset($idLU))
                hx-trigger="load"
                hx-get="/reframing/content/{{$idLU}}"
            @endif
        >
        </div>
    </x-slot:pane>
</x-layout.report>
