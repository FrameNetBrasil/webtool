<div class="page-metadata">
    <div class="metadata-left">
    </div>
    <div class="metadata-center">
        <div class="ui label">
            Core FEs
            <div class="detail">{{ count($fe['core'] ?? []) }}</div>
        </div>

        @if(isset($fe['core_unexpressed']) && count($fe['core_unexpressed']) > 0)
            <div class="ui label">
                Core Unexpressed
                <div class="detail">{{ count($fe['core_unexpressed']) }}</div>
            </div>
        @endif
        @if(isset($fe['peripheral']) && count($fe['peripheral']) > 0)
            <div class="ui label">
                Peripheral FEs
                <div class="detail">{{ count($fe['peripheral']) }}</div>
            </div>
        @endif
        @if(isset($fe['extra_thematic']) && count($fe['extra_thematic']) > 0)
            <div class="ui label">
                Extra-thematic
                <div class="detail">{{ count($fe['extra_thematic']) }}</div>
            </div>
        @endif
        <div class="ui label">
            LUs
            <div class="detail">{{ count($fe['extra_thematic']) }}</div>
        </div>
        <div class="ui label">
            Extra-thematic
            <div class="detail">{{ count($lus) }}</div>
        </div>
        <div class="ui label">
            Relation Types
            <div class="detail">{{ count($relations) }}</div>
        </div>
    </div>
    <div class="metadata-right">
    </div>
</div>
