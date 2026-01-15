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
                        <div class="section-content" id="core-fes">
                            <div class="fe-cards-grid">
                                @foreach($fe['core'] as $feObj)
                                    @include('Class.Report.partials.fe-card', [
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

    </div>
</div>
