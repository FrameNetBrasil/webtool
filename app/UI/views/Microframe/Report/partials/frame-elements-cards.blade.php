{{--
    Frame Elements Cards - Organized sections for different FE types
    Parameters:
    - $fe: Frame elements array
    - $fecoreset: FE core set information (optional)
--}}
<div class="frame-elements-section">
    <h2 class="ui header section-title">Frame Elements</h2>

    <div class="fe-sections">
        {{-- Core FEs Section --}}
        @if(isset($fe['domain']) && count($fe['domain']) > 0)
            <div class="fe-section core-section">
                <div class="ui card fluid data-card section-card">
                    <div class="content">
                        <div class="data-card-header">
                            <div class="data-card-title">
                                <h3 class="ui header" id="core">
                                    <a href="#core">Domain</a>
                                </h3>
                            </div>
                            {{--                            <button class="ui icon basic button section-toggle"--}}
                            {{--                                    onclick="toggleSection('core-fes')"--}}
                            {{--                                    aria-expanded="true">--}}
                            {{--                                <i class="chevron up icon"></i>--}}
                            {{--                            </button>--}}
                        </div>
                        <div class="section-content" id="core-fes">
                            <div class="fe-cards-grid">
                                @foreach($fe['domain'] as $feObj)
                                    @include('Frame.Report.partials.fe-card', [
                                        'feObj' => $feObj,
                                        'semanticTypes' => $fe['semanticTypes'] ?? [],
                                        'feType' => 'core'
                                    ])
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="content">
                        <div class="data-card-header">
                            <div class="data-card-title">
                                <h3 class="ui header" id="core">
                                    <a href="#core">Range</a>
                                </h3>
                            </div>
                            {{--                            <button class="ui icon basic button section-toggle"--}}
                            {{--                                    onclick="toggleSection('core-fes')"--}}
                            {{--                                    aria-expanded="true">--}}
                            {{--                                <i class="chevron up icon"></i>--}}
                            {{--                            </button>--}}
                        </div>
                        <div class="section-content" id="core-fes">
                            <div class="fe-cards-grid">
                                @foreach($fe['range'] as $feObj)
                                    @include('Frame.Report.partials.fe-card', [
                                        'feObj' => $feObj,
                                        'semanticTypes' => $fe['semanticTypes'] ?? [],
                                        'feType' => 'core'
                                    ])
                                @endforeach
                            </div>
                        </div>
                    </div>


                </div>
            </div>
    </div>
    @endif

    {{--        @if(isset($fe['range']) && count($fe['range']) > 0)--}}
    {{--            <div class="fe-section core-section">--}}
    {{--                <div class="ui card fluid data-card section-card">--}}
    {{--                    <div class="content">--}}
    {{--                        <div class="data-card-header">--}}
    {{--                            <div class="data-card-title">--}}
    {{--                                <h3 class="ui header" id="core">--}}
    {{--                                    <a href="#core">Range Frame Elements</a>--}}
    {{--                                </h3>--}}
    {{--                            </div>--}}
    {{--                            <button class="ui icon basic button section-toggle"--}}
    {{--                                    onclick="toggleSection('core-fes')"--}}
    {{--                                    aria-expanded="true">--}}
    {{--                                <i class="chevron up icon"></i>--}}
    {{--                            </button>--}}
    {{--                        </div>--}}
    {{--                        <div class="section-content" id="core-fes">--}}
    {{--                            <div class="fe-cards-grid">--}}
    {{--                                @foreach($fe['range'] as $feObj)--}}
    {{--                                    @include('Frame.Report.partials.fe-card', [--}}
    {{--                                        'feObj' => $feObj,--}}
    {{--                                        'semanticTypes' => $fe['semanticTypes'] ?? [],--}}
    {{--                                        'feType' => 'core'--}}
    {{--                                    ])--}}
    {{--                                @endforeach--}}
    {{--                            </div>--}}
    {{--                        </div>--}}
    {{--                    </div>--}}
    {{--                </div>--}}
    {{--            </div>--}}
    {{--        @endif--}}

</div>
</div>
