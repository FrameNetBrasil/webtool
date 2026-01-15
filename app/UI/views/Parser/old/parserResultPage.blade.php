<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
                :sections="[['/','Home'],['/parser','Parser'],['/parser/result/' . $result->idParserGraph,'Result #' . $result->idParserGraph]]"></x-partial::breadcrumb>

        <main class="app-main">
            <div class="page-content">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">Parse Result #{{ $result->idParserGraph }}</div>
                        <div class="page-subtitle">Graph-Based Predictive Parser Result</div>
                    </div>
                </div>

                @include('Parser.old.parserResults')
            </div>
        </main>

        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
