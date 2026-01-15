<div class="graph-visualization-container">
    <h4 class="ui header">
        Grammar Graph Visualization
        @if(!empty($filter))
            <span class="ui label">
                <i class="filter icon"></i>
                Filtered by: "{{ $filter }}"
            </span>
        @endif
    </h4>

    <div class="ui statistics small">
        <div class="statistic">
            <div class="value">{{ $stats['totalNodes'] }}</div>
            <div class="label">
                @if(!empty($filter))
                    Filtered Nodes
                @else
                    Total Nodes
                @endif
            </div>
        </div>
        <div class="statistic">
            <div class="value">{{ $stats['totalEdges'] }}</div>
            <div class="label">
                @if(!empty($filter))
                    Filtered Edges
                @else
                    Total Edges
                @endif
            </div>
        </div>
        <div class="statistic">
            <div class="value">{{ number_format($stats['avgDegree'], 2) }}</div>
            <div class="label">Avg Degree</div>
        </div>
        @if(!empty($filter))
        <div class="statistic">
            <div class="value">{{ $stats['unfilteredTotalNodes'] }}</div>
            <div class="label">Total in Grammar</div>
        </div>
        @endif
    </div>

    @if(isset($stats['nodesByType']) && count($stats['nodesByType']) > 0)
    <div class="ui segment">
        <h5 class="ui header">Node Distribution by Type</h5>
        <div class="ui labels">
            @foreach($stats['nodesByType'] as $type => $count)
                <span class="ui label" style="background-color: {{ config('parser.visualization.nodeColors.' . $type, '#999') }}; color: white;">
                    {{ $type }}: {{ $count }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <div class="ui segment">
        <h5 class="ui header">Legend</h5>
        <div class="ui list">
            <div class="item">
                <strong>Node Types:</strong>
                <span class="ui mini label" style="background-color: {{ config('parser.visualization.nodeColors.E') }}; color: white;">E</span> Entity
                <span class="ui mini label" style="background-color: {{ config('parser.visualization.nodeColors.R') }}; color: white;">R</span> Relational
                <span class="ui mini label" style="background-color: {{ config('parser.visualization.nodeColors.A') }}; color: white;">A</span> Attribute
                <span class="ui mini label" style="background-color: {{ config('parser.visualization.nodeColors.F') }}; color: white;">F</span> Function
            </div>
            <div class="item">
                <strong>Edge Types:</strong>
                <span class="ui mini label" style="background-color: {{ config('parser.visualization.edgeColors.prediction') }}; color: white;">Prediction</span>
                <span class="ui mini label" style="background-color: {{ config('parser.visualization.edgeColors.activate') }}; color: white;">Activate</span>
                <span class="ui mini label" style="background-color: {{ config('parser.visualization.edgeColors.sequential') }}; color: white;">Sequential</span>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Alpine component for grammar graph visualization using JointJS
    if (window.Alpine && document.getElementById('grapherApp')) {
        Alpine.data('grapherInstance', () => window.grapherComponent({
            nodes: {{ Js::from($graph['nodes']) }},
            links: {{ Js::from($graph['links']) }}
        }));

        // Apply Alpine to the container if not already applied
        const container = document.getElementById('grapherApp');
        if (!container.hasAttribute('x-data')) {
            container.setAttribute('x-data', 'grapherInstance');
            Alpine.initTree(container);

            // Get the Alpine component instance and initialize it
            const component = Alpine.$data(container);
            if (component && component.init) {
                component.init();
            }
        } else {
            // If already initialized, just update the data
            const component = Alpine.$data(container);
            if (component && component.updateData) {
                component.updateData({{ Js::from($graph['nodes']) }}, {{ Js::from($graph['links']) }});
            }
        }
    }
</script>
