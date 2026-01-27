<div class="p-4">
    <h4 class="ui header">Type Graph</h4>
    <p class="text-gray-600">Construction relationships within the unified Type Graph ontology (V5)</p>

    <div id="hierarchyGraph" class="border rounded" style="height: 600px; background: #f9f9f9;"></div>

    <div class="ui message">
        <div class="header">Node Types</div>
        <div class="ui labels">
            <span class="ui label blue">MWE Construction</span>
            <span class="ui label green">Phrasal Construction</span>
            <span class="ui label orange">Clausal Construction</span>
            <span class="ui label red">Sentential Construction</span>
        </div>
        <div class="ui labels mt-2">
            <span class="ui label" style="background: #81C784">Phrasal CE</span>
            <span class="ui label" style="background: #FFB74D">Clausal CE</span>
            <span class="ui label" style="background: #E57373">Sentential CE</span>
        </div>
    </div>

    <div class="ui message">
        <div class="header">Relationship Types</div>
        <div class="ui labels">
            <span class="ui label green">produces</span>
            <span class="ui label blue">requires</span>
            <span class="ui label purple">inherits</span>
            <span class="ui label red">conflicts_with</span>
        </div>
        <p class="text-sm mt-2">Solid lines = mandatory, Dashed lines = optional</p>
    </div>

    <script src="/scripts/jointjs/dist/joint.js"></script>
    <script src="/scripts/dagre/dist/dagre.min.js"></script>
    <script>
        // Expose graphlib globally for joint.layout.DirectedGraph
        if (typeof dagre !== 'undefined' && dagre.graphlib) {
            window.graphlib = dagre.graphlib;
        }
    </script>
    <script src="/scripts/jointjs/dist/joint.layout.DirectedGraph.js"></script>
    <link rel="stylesheet" href="/scripts/jointjs/dist/joint.css">

    <script>
        (function() {
            const graphData = @json($graphData);
            const container = document.getElementById('hierarchyGraph');

            if (!graphData.nodes || graphData.nodes.length === 0) {
                container.innerHTML = '<div class="ui warning message">No Type Graph data available. The Type Graph may need to be built for this grammar.</div>';
                return;
            }

            if (graphData.error) {
                container.innerHTML = `<div class="ui error message">${graphData.error}</div>`;
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

            // Create nodes (constructions and CE labels)
            graphData.nodes.forEach(node => {
                const isConstruction = node.type === 'construction';
                const isCELabel = node.type === 'ce_label';
                const isCenterNode = graphData.centerNodeId && node.id === graphData.centerNodeId;

                let element;

                if (isConstruction) {
                    // Rectangle for constructions
                    const width = Math.max(node.label.length * 9, 120);
                    const borderWidth = node.borderWidth || 2;

                    element = new joint.shapes.standard.Rectangle({
                        id: node.id,
                        size: { width: width, height: 60 },
                        attrs: {
                            body: {
                                fill: node.color,
                                stroke: node.borderColor || '#333',
                                strokeWidth: borderWidth,
                                rx: 8,
                                ry: 8
                            },
                            label: {
                                text: node.label,
                                fill: '#fff',
                                fontWeight: isCenterNode ? 'bold' : 'normal',
                                fontSize: isCenterNode ? 14 : 12,
                                textVerticalAnchor: 'middle'
                            }
                        }
                    });
                } else if (isCELabel) {
                    // Ellipse for CE labels
                    const width = Math.max(node.label.length * 10, 100);

                    element = new joint.shapes.standard.Ellipse({
                        id: node.id,
                        size: { width: width, height: 50 },
                        attrs: {
                            body: {
                                fill: node.color,
                                stroke: node.borderColor || '#666',
                                strokeWidth: node.borderWidth || 2
                            },
                            label: {
                                text: node.label,
                                fill: '#333',
                                fontWeight: 'normal',
                                fontSize: 11,
                                textVerticalAnchor: 'middle'
                            }
                        }
                    });
                }

                elements.push(element);
            });

            // Create relationship links
            graphData.edges.forEach(edge => {
                const strokeDasharray = edge.style === 'dashed' ? '5,5' : 'none';

                const link = new joint.shapes.standard.Link({
                    id: edge.id,
                    source: { id: edge.source },
                    target: { id: edge.target },
                    attrs: {
                        line: {
                            stroke: edge.color || '#666',
                            strokeWidth: edge.mandatory ? 2 : 1.5,
                            strokeDasharray: strokeDasharray,
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
                                fill: '#555',
                                fontWeight: edge.mandatory ? 'bold' : 'normal'
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
