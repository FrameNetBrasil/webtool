<div class="p-4">
    <h4 class="ui header">Hierarchy Graph</h4>
    <p class="text-gray-600">Constructions sharing CE labels within this grammar</p>

    <div id="hierarchyGraph" class="border rounded" style="height: 600px; background: #f9f9f9;"></div>

    <div class="ui message">
        <div class="header">Relationship Types</div>
        <div class="ui labels">
            <span class="ui label green">Phrasal CE</span>
            <span class="ui label orange">Clausal CE</span>
            <span class="ui label red">Sentential CE</span>
        </div>
    </div>

    <script src="/scripts/jointjs/dist/joint.js"></script>
    <script src="/scripts/jointjs/dist/joint.layout.DirectedGraph.js"></script>
    <link rel="stylesheet" href="/scripts/jointjs/dist/joint.css">

    <script>
        (function() {
            const graphData = @json($graphData);
            const container = document.getElementById('hierarchyGraph');

            if (!graphData.nodes || graphData.nodes.length === 0) {
                container.innerHTML = '<div class="ui message">No hierarchy data available. Constructions need shared CE labels to form relationships.</div>';
                return;
            }

            // Create JointJS graph
            const graph = new joint.dia.Graph();
            const paper = new joint.dia.Paper({
                el: container,
                model: graph,
                width: container.clientWidth || 800,
                height: 600,
                gridSize: 1,
                background: { color: '#f9f9f9' },
                interactive: true
            });

            const elements = [];
            const links = [];

            // Create construction nodes
            graphData.nodes.forEach(node => {
                const labelText = `${node.label}\n[${node.type}]`;
                const width = Math.max(node.label.length * 9, 120);

                const element = new joint.shapes.standard.Rectangle({
                    id: node.id,
                    size: { width: width, height: 60 },
                    attrs: {
                        body: {
                            fill: node.color,
                            stroke: '#333',
                            strokeWidth: 2,
                            rx: 8,
                            ry: 8
                        },
                        label: {
                            text: labelText,
                            fill: '#fff',
                            fontWeight: 'bold',
                            fontSize: 12,
                            textVerticalAnchor: 'middle'
                        }
                    }
                });

                elements.push(element);
            });

            // Create relationship links
            graphData.edges.forEach(edge => {
                const link = new joint.shapes.standard.Link({
                    id: edge.id,
                    source: { id: edge.source },
                    target: { id: edge.target },
                    attrs: {
                        line: {
                            stroke: edge.color || '#666',
                            strokeWidth: 2,
                            targetMarker: {
                                type: 'path',
                                d: 'M 10 -5 0 0 10 5 z',
                                fill: edge.color || '#666'
                            }
                        }
                    },
                    labels: [{
                        attrs: {
                            text: {
                                text: edge.label,
                                fontSize: 10,
                                fill: '#555'
                            },
                            rect: {
                                fill: '#fff',
                                stroke: edge.color || '#666',
                                strokeWidth: 1,
                                rx: 3,
                                ry: 3
                            }
                        }
                    }]
                });

                links.push(link);
            });

            // Add elements to graph
            graph.resetCells(elements.concat(links));

            // Apply layout if there are edges
            if (links.length > 0) {
                joint.layout.DirectedGraph.layout(graph, {
                    rankDir: graphData.direction || 'TB',
                    nodeSep: 80,
                    edgeSep: 50,
                    rankSep: 120
                });
            } else {
                // Simple grid layout if no edges
                let x = 50, y = 50;
                elements.forEach((el, idx) => {
                    el.position(x, y);
                    x += 180;
                    if ((idx + 1) % 4 === 0) {
                        x = 50;
                        y += 100;
                    }
                });
            }

            // Center and fit
            paper.scaleContentToFit({ padding: 30, maxScale: 1.2 });
        })();
    </script>
</div>
