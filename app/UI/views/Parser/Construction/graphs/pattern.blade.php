<div class="p-4">
    <h4 class="ui header">Pattern Graph: {{ $construction->name }}</h4>
    <p class="text-gray-600">Visual representation of the BNF pattern structure</p>

    <div id="patternGraph" class="border rounded" style="height: 500px; background: #f9f9f9;"></div>

    <div class="ui message">
        <div class="header">Pattern</div>
        <code>{{ $construction->pattern }}</code>
    </div>

    <script src="/scripts/jointjs/dist/joint.js"></script>
    <script src="/scripts/jointjs/dist/joint.layout.DirectedGraph.js"></script>
    <link rel="stylesheet" href="/scripts/jointjs/dist/joint.css">

    <script>
        (function() {
            const graphData = @json($graphData);
            const container = document.getElementById('patternGraph');

            if (!graphData.nodes || graphData.nodes.length === 0) {
                container.innerHTML = '<div class="ui message">No pattern data available</div>';
                return;
            }

            // Create JointJS graph
            const graph = new joint.dia.Graph();
            const paper = new joint.dia.Paper({
                el: container,
                model: graph,
                width: container.clientWidth || 800,
                height: 500,
                gridSize: 1,
                background: { color: '#f9f9f9' },
                interactive: false
            });

            const elements = [];
            const links = [];

            // Create nodes
            graphData.nodes.forEach(node => {
                let element;

                if (node.shape === 'circle') {
                    element = new joint.shapes.standard.Circle({
                        id: node.id,
                        size: { width: 60, height: 60 },
                        attrs: {
                            body: { fill: node.color, stroke: '#333', strokeWidth: 2 },
                            label: { text: node.label, fill: '#fff', fontWeight: 'bold', fontSize: 12 }
                        }
                    });
                } else {
                    const labelLength = node.label.length;
                    const width = Math.max(labelLength * 10, 80);

                    element = new joint.shapes.standard.Rectangle({
                        id: node.id,
                        size: { width: width, height: 50 },
                        attrs: {
                            body: { fill: node.color, stroke: '#333', strokeWidth: 2, rx: 5, ry: 5 },
                            label: { text: node.label, fill: '#fff', fontWeight: 'bold', fontSize: 13 }
                        }
                    });
                }

                elements.push(element);
            });

            // Create links
            graphData.edges.forEach(edge => {
                const link = new joint.shapes.standard.Link({
                    id: edge.id,
                    source: { id: edge.source },
                    target: { id: edge.target },
                    attrs: {
                        line: { stroke: '#333', strokeWidth: 2 },
                        wrapper: { strokeWidth: 10 }
                    },
                    labels: edge.label ? [{
                        attrs: { text: { text: edge.label, fontSize: 11 } }
                    }] : []
                });

                links.push(link);
            });

            // Add elements to graph
            graph.resetCells(elements.concat(links));

            // Apply layout
            joint.layout.DirectedGraph.layout(graph, {
                rankDir: graphData.direction || 'LR',
                nodeSep: 60,
                edgeSep: 40,
                rankSep: 100
            });

            // Center and fit
            paper.scaleContentToFit({ padding: 20, maxScale: 1.5 });
        })();
    </script>
</div>
