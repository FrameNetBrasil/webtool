<div class="ui container">
    <div class="page-header">
        @include('LU.Report.partials.lu-header')
    </div>
    <div class="page-content">
        {{-- LU Metadata Section --}}
        <div class="lu-metadata-section">
            @include('LU.Report.partials.lu-metadata')
        </div>

        {{-- INC Section --}}
        @if(isset($incorporatedFE))
            <div class="definition-section mb-8">
                @include('LU.Report.partials.inc-card')
            </div>
        @endif

        {{-- Annotation Types Section --}}
        <div class="annotation-types-section mb-8">
            @include('LU.Report.partials.annotation-types-nav')
        </div>
    </div>
</div>
