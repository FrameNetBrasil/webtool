<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['','LU Candidate']]"
        ></x-partial::breadcrumb>
        <main
            class="app-main"
            x-data="{
                sort: '',
                order: '',
                handleSort(col) {
                    if (this.sort === col) {
                        this.order = this.order === 'asc' ? 'desc' : this.order === 'desc' ? '' : 'asc';
                        if (this.order === '') this.sort = '';
                    } else {
                        this.sort = col;
                        this.order = 'asc';
                    }
                }
            }"
            x-init="$nextTick(() => { $('.menu .item').tab(); })"
        >
            <div class="ui container page">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            LU Candidate
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    <a href="/luCandidate/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New LU Candidate
                    </a>
                </div>
                <div>
                    {{-- Fomantic UI Tabs --}}
                    <div class="ui secondary pointing menu">
                        <a class="item {{ $defaultTab === 'webtool' ? 'active' : '' }}" data-tab="webtool">
                            WEBTOOL
                            <div class="ui tiny label ml-2">{{ count($dataWebTool) }}</div>
                        </a>
                        <a class="item {{ $defaultTab === 'lome' ? 'active' : '' }}" data-tab="lome">
                            LOME
                            <div class="ui tiny label ml-2">{{ count($dataLome) }}</div>
                        </a>
                        <a class="item {{ $defaultTab === 'fnbk' ? 'active' : '' }}" data-tab="fnbk">
                            FNBK
                            <div class="ui tiny label ml-2">{{ count($dataFnbk) }}</div>
                        </a>
                    </div>
                </div>
                <div class="page-content">
                    <div class="h-full">
                        {{-- Tab Content: WEBTOOL--}}
                        <div class="ui tab {{ $defaultTab === 'webtool' ? 'active' : '' }} h-full" data-tab="webtool">
                            @include('LUCandidate.tabContent', ['data' => $dataWebTool, 'origin' => 'WEBTOOL', 'creators' => $creators])
                        </div>

                        {{-- Tab Content: LOME --}}
                        <div class="ui tab {{ $defaultTab === 'lome' ? 'active' : '' }} h-full" data-tab="lome">
                            @include('LUCandidate.tabContent', ['data' => $dataLome, 'origin' => 'LOME', 'creators' => $creators])
                        </div>

                        {{-- Tab Content: FNBK --}}
                        <div class="ui tab {{ $defaultTab === 'fnbk' ? 'active' : '' }} h-full" data-tab="fnbk">
                            @include('LUCandidate.tabContent', ['data' => $dataFnbk, 'origin' => 'FNBK', 'creators' => $creators])
                        </div>
                    </div>
                </div>
            </div>

        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
