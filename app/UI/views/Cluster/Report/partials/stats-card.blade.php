{{--
    Frame Stats Card - Quick overview of frame statistics
    Parameters:
    - $fe: Frame elements array
    - $lus: Lexical units array
    - $relations: Relations array
--}}
<div class="ui card fluid data-card stats-card">
    <div class="content">
        <div class="data-card-header">
            <div class="data-card-title">
                <div class="header">Quick Stats</div>
            </div>
        </div>
        <div class="data-card-stats">
            <div class="stat-item">
                <div class="stat-value">{{ count($fe['domain'] ?? []) }}</div>
                <div class="stat-label">Domain FEs</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ count($fe['range'] ?? []) }}</div>
                <div class="stat-label">Range FEs</div>
            </div>
{{--            @if(isset($fe['core_unexpressed']) && count($fe['core_unexpressed']) > 0)--}}
{{--                <div class="stat-item">--}}
{{--                    <div class="stat-value">{{ count($fe['core_unexpressed']) }}</div>--}}
{{--                    <div class="stat-label">Core Unexpressed</div>--}}
{{--                </div>--}}
{{--            @endif--}}
{{--            @if(isset($fe['peripheral']) && count($fe['peripheral']) > 0)--}}
{{--                <div class="stat-item">--}}
{{--                    <div class="stat-value">{{ count($fe['peripheral']) }}</div>--}}
{{--                    <div class="stat-label">Peripheral FEs</div>--}}
{{--                </div>--}}
{{--            @endif--}}
{{--            @if(isset($fe['extra_thematic']) && count($fe['extra_thematic']) > 0)--}}
{{--                <div class="stat-item">--}}
{{--                    <div class="stat-value">{{ count($fe['extra_thematic']) }}</div>--}}
{{--                    <div class="stat-label">Extra-thematic</div>--}}
{{--                </div>--}}
{{--            @endif--}}
{{--            <div class="stat-item">--}}
{{--                <div class="stat-value">{{ array_sum(array_map('count', $lus)) }}</div>--}}
{{--                <div class="stat-label">Lexical Units</div>--}}
{{--            </div>--}}
{{--            <div class="stat-item">--}}
{{--                <div class="stat-value">{{ count($relations) }}</div>--}}
{{--                <div class="stat-label">Relation Types</div>--}}
{{--            </div>--}}
        </div>
    </div>
</div>
