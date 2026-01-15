<div class="graph-visualization-container">
    <h4 class="ui header">Parse Graph Visualization</h4>

    <div class="ui segment">
        <div id="graphCanvas" class="graph-canvas"></div>
    </div>

    <div class="ui statistics small">
        <div class="statistic">
            <div class="value">{{ $stats['totalNodes'] }}</div>
            <div class="label">Total Nodes</div>
        </div>
        <div class="statistic">
            <div class="value">{{ $stats['totalEdges'] }}</div>
            <div class="label">Total Edges</div>
        </div>
        <div class="statistic">
            <div class="value">{{ number_format($stats['avgDegree'], 2) }}</div>
            <div class="label">Avg Degree</div>
        </div>
    </div>

    @if(isset($stats['nodesByType']))
    <div class="ui segment">
        <h5 class="ui header">Node Distribution</h5>
        <div class="ui labels">
            @foreach($stats['nodesByType'] as $type => $count)
                <span class="ui label" style="background-color: {{ config('parser.visualization.nodeColors.' . $type, '#999') }}; color: white;">
                    {{ $type }}: {{ $count }}
                </span>
            @endforeach
        </div>
    </div>
    @endif
</div>

<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
    (function() {
        const data = {!! $d3Data !!};
        const width = 900;
        const height = 600;

        // Clear previous graph
        d3.select('#graphCanvas').selectAll('*').remove();

        // Create SVG
        const svg = d3.select('#graphCanvas')
            .append('svg')
            .attr('width', width)
            .attr('height', height)
            .attr('viewBox', [0, 0, width, height])
            .attr('style', 'max-width: 100%; height: auto;');

        // Create force simulation
        const simulation = d3.forceSimulation(data.nodes)
            .force('link', d3.forceLink(data.links).id(d => d.id).distance(100))
            .force('charge', d3.forceManyBody().strength(-300))
            .force('center', d3.forceCenter(width / 2, height / 2))
            .force('collision', d3.forceCollide().radius(d => d.size + 5));

        // Create arrow markers for directed edges
        svg.append('defs').selectAll('marker')
            .data(['end'])
            .enter().append('marker')
            .attr('id', 'arrowhead')
            .attr('viewBox', '0 -5 10 10')
            .attr('refX', 20)
            .attr('refY', 0)
            .attr('markerWidth', 6)
            .attr('markerHeight', 6)
            .attr('orient', 'auto')
            .append('path')
            .attr('d', 'M0,-5L10,0L0,5')
            .attr('fill', '#999');

        // Create links
        const link = svg.append('g')
            .attr('class', 'links')
            .selectAll('line')
            .data(data.links)
            .enter().append('line')
            .attr('stroke', d => d.color)
            .attr('stroke-width', d => d.width)
            .attr('marker-end', 'url(#arrowhead)');

        // Create nodes
        const node = svg.append('g')
            .attr('class', 'nodes')
            .selectAll('circle')
            .data(data.nodes)
            .enter().append('circle')
            .attr('r', d => d.size)
            .attr('fill', d => d.color)
            .attr('stroke', '#fff')
            .attr('stroke-width', 2)
            .call(d3.drag()
                .on('start', dragstarted)
                .on('drag', dragged)
                .on('end', dragended));

        // Add labels
        const labels = svg.append('g')
            .attr('class', 'labels')
            .selectAll('text')
            .data(data.nodes)
            .enter().append('text')
            .text(d => d.label)
            .attr('font-size', 12)
            .attr('dx', 15)
            .attr('dy', 4);

        // Add tooltips
        node.append('title')
            .text(d => `${d.label}\nType: ${d.type}\nActivation: ${d.activation}/${d.threshold}`);

        // Update positions on simulation tick
        simulation.on('tick', () => {
            link
                .attr('x1', d => d.source.x)
                .attr('y1', d => d.source.y)
                .attr('x2', d => d.target.x)
                .attr('y2', d => d.target.y);

            node
                .attr('cx', d => d.x)
                .attr('cy', d => d.y);

            labels
                .attr('x', d => d.x)
                .attr('y', d => d.y);
        });

        // Drag functions
        function dragstarted(event, d) {
            if (!event.active) simulation.alphaTarget(0.3).restart();
            d.fx = d.x;
            d.fy = d.y;
        }

        function dragged(event, d) {
            d.fx = event.x;
            d.fy = event.y;
        }

        function dragended(event, d) {
            if (!event.active) simulation.alphaTarget(0);
            d.fx = null;
            d.fy = null;
        }
    })();
</script>

<style>
    .graph-canvas {
        min-height: 600px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .graph-visualization-container {
        margin-top: 2rem;
    }

    .links line {
        stroke-opacity: 0.6;
    }

    .nodes circle {
        cursor: pointer;
    }

    .labels text {
        pointer-events: none;
        font-family: sans-serif;
    }
</style>
