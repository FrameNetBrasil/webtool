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
        @if(isset($fe['core']) && count($fe['core']) > 0)
            <div class="fe-section core-section">
                <div class="ui card fluid data-card section-card">
                    <div class="content">
                        <div class="data-card-header">
                            <div class="data-card-title">
                                <h3 class="ui header" id="core">
                                    <a href="#core">Core Frame Elements</a>
                                </h3>
                            </div>
                            <button class="ui icon basic button section-toggle"
                                    onclick="toggleSection('core-fes')"
                                    aria-expanded="true">
                                <i class="chevron up icon"></i>
                            </button>
                        </div>
                        <div class="section-content" id="core-fes">
                            <div class="fe-cards-grid">
                                @foreach($fe['core'] as $feObj)
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
        @endif

        {{-- Core Unexpressed FEs Section --}}
        @if(isset($fe['core_unexpressed']) && count($fe['core_unexpressed']) > 0)
            <div class="fe-section core-unexpressed-section">
                <div class="ui card fluid data-card section-card">
                    <div class="content">
                        <div class="data-card-header">
                            <div class="data-card-title">
                                <h3 class="ui header" id="core-unexpressed">
                                    <a href="#core-unexpressed">Core Unexpressed</a>
                                </h3>
                            </div>
                            <button class="ui button basic icon section-toggle"
                                    onclick="toggleSection('core-unexpressed-fes')"
                                    aria-expanded="false">
                                <i class="chevron down icon"></i>
                            </button>
                        </div>
                        <div class="section-content" id="core-unexpressed-fes" style="display: none;">
                            <div class="fe-cards-grid">
                                @foreach($fe['core_unexpressed'] as $feObj)
                                    @include('Frame.Report.partials.fe-card', [
                                        'feObj' => $feObj,
                                        'semanticTypes' => $fe['semanticTypes'] ?? [],
                                        'feType' => 'core-unexpressed'
                                    ])
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- FE Core Set(s) --}}
        @if($fecoreset)
            <div class="fe-section coreset-section">
                <div class="ui card fluid data-card">
                    <div class="content">
                        <div class="header">FE Core Set(s)</div>
                        <div class="description">{{ $fecoreset }}</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Peripheral FEs Section --}}
        @if(isset($fe['peripheral']) && count($fe['peripheral']) > 0)
            <div class="fe-section peripheral-section">
                <div class="ui card fluid data-card section-card">
                    <div class="content">
                        <div class="data-card-header">
                            <div class="data-card-title">
                                <h3 class="ui header" id="peripheral">
                                    <a href="#peripheral">Peripheral Frame Elements</a>
                                </h3>
                            </div>
                            <button class="ui button basic icon section-toggle"
                                    onclick="toggleSection('peripheral-fes')"
                                    aria-expanded="false">
                                <i class="chevron down icon"></i>
                            </button>
                        </div>
                        <div class="section-content" id="peripheral-fes" style="display: none;">
                            <div class="fe-cards-grid">
                                @foreach($fe['peripheral'] as $feObj)
                                    @include('Frame.Report.partials.fe-card', [
                                        'feObj' => $feObj,
                                        'semanticTypes' => $fe['semanticTypes'] ?? [],
                                        'feType' => 'peripheral'
                                    ])
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Extra-thematic FEs Section --}}
        @if(isset($fe['extra_thematic']) && count($fe['extra_thematic']) > 0)
            <div class="fe-section extra-thematic-section">
                <div class="ui card fluid data-card section-card">
                    <div class="content">
                        <div class="data-card-header">
                            <div class="data-card-title">
                                <h3 class="ui header" id="extra-thematic">
                                    <a href="#extra-thematic">Extra-thematic Frame Elements</a>
                                </h3>
                            </div>
                            <button class="ui button basic icon section-toggle"
                                    onclick="toggleSection('extra-thematic-fes')"
                                    aria-expanded="false">
                                <i class="chevron down icon"></i>
                            </button>
                        </div>
                        <div class="section-content" id="extra-thematic-fes" style="display: none;">
                            <div class="fe-cards-grid">
                                @foreach($fe['extra_thematic'] as $feObj)
                                    @include('Frame.Report.partials.fe-card', [
                                        'feObj' => $feObj,
                                        'semanticTypes' => $fe['semanticTypes'] ?? [],
                                        'feType' => 'extra-thematic'
                                    ])
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
