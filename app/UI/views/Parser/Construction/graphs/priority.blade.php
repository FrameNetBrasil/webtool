<div class="p-4">
    <h4 class="ui header">Priority Graph</h4>
    <p class="text-gray-600">Visual lanes showing construction priorities by type (drag to reorder)</p>

    <div id="priorityGraph" class="border rounded" style="height: 500px; background: #f9f9f9;"></div>

    <div class="ui message">
        <div class="header">Priority Ranges</div>
        <div class="ui labels">
            <span class="ui label red">Sentential (1-19)</span>
            <span class="ui label orange">Clausal (20-49)</span>
            <span class="ui label green">Phrasal (50-99)</span>
            <span class="ui label blue">MWE (100-199)</span>
        </div>
    </div>

    <script src="/scripts/jointjs/dist/joint.js"></script>
    <link rel="stylesheet" href="/scripts/jointjs/dist/joint.css">

    <script>
        (function() {
            const graphData = @json($graphData);
            const container = document.getElementById('priorityGraph');

            if (!graphData.nodes || graphData.nodes.length === 0) {
                container.innerHTML = '<div class="ui message">No constructions available for priority visualization.</div>';
                return;
            }

            // Create JointJS graph
            const graph = new joint.dia.Graph();
            const paper = new joint.dia.Paper({
                el: container,
                model: graph,
                width: container.clientWidth || 900,
                height: 500,
                gridSize: 10,
                background: { color: '#f9f9f9' },
                interactive: { elementMove: true }
            });

            const elements = [];
            const laneHeight = 80;
            const laneLabels = [];

            // Draw lane backgrounds and labels
            graphData.lanes.forEach((lane, idx) => {
                const y = idx * 100 + 50;

                // Lane background
                const laneRect = new joint.shapes.standard.Rectangle({
                    position: { x: 0, y: y - 35 },
                    size: { width: 900, height: laneHeight },
                    attrs: {
                        body: {
                            fill: lane.color + '22',
                            stroke: lane.color,
                            strokeWidth: 1,
                            strokeDasharray: '5,5',
                            rx: 5,
                            ry: 5
                        },
                        label: {
                            text: '',
                            fill: 'transparent'
                        }
                    }
                });
                laneRect.set('type', 'lane');
                elements.push(laneRect);

                // Lane label
                const laneLabel = new joint.shapes.standard.Rectangle({
                    position: { x: 10, y: y - 30 },
                    size: { width: 150, height: 30 },
                    attrs: {
                        body: {
                            fill: lane.color,
                            stroke: '#333',
                            strokeWidth: 2,
                            rx: 5,
                            ry: 5
                        },
                        label: {
                            text: lane.label,
                            fill: '#fff',
                            fontWeight: 'bold',
                            fontSize: 11
                        }
                    }
                });
                laneLabel.set('type', 'lane-label');
                laneLabels.push(laneLabel);
            });

            // Create construction nodes
            graphData.nodes.forEach(node => {
                const width = Math.max(node.label.length * 8, 100);
                const opacity = node.enabled ? '1.0' : '0.5';

                const element = new joint.shapes.standard.Rectangle({
                    id: node.id,
                    position: { x: 170 + node.x, y: node.y + 20 },
                    size: { width: width, height: 40 },
                    attrs: {
                        body: {
                            fill: node.color,
                            fillOpacity: opacity,
                            stroke: '#333',
                            strokeWidth: 2,
                            rx: 6,
                            ry: 6
                        },
                        label: {
                            text: `${node.label}\n(${node.priority})`,
                            fill: '#fff',
                            fontWeight: 'bold',
                            fontSize: 11,
                            textVerticalAnchor: 'middle'
                        }
                    }
                });

                element.set('constructionData', {
                    id: node.id,
                    priority: node.priority,
                    type: node.type,
                    enabled: node.enabled
                });

                elements.push(element);
            });

            // Add all elements
            graph.resetCells(elements.concat(laneLabels));

            // Drag event handling
            paper.on('element:pointerup', function(elementView) {
                const element = elementView.model;

                // Only handle construction nodes, not lanes
                if (element.get('type') === 'lane' || element.get('type') === 'lane-label') {
                    return;
                }

                const constructionData = element.get('constructionData');
                if (!constructionData) {
                    return;
                }

                const position = element.position();
                const laneIdx = Math.floor((position.y - 15) / 100);

                if (laneIdx >= 0 && laneIdx < graphData.lanes.length) {
                    const lane = graphData.lanes[laneIdx];
                    const xNormalized = Math.max(0, Math.min((position.x - 170) / 800, 1));
                    const newPriority = Math.round(lane.min + xNormalized * (lane.max - lane.min));

                    console.log(`Dragged ${constructionData.id} to lane ${lane.type}, suggested priority: ${newPriority}`);

                    // Show confirmation (in a real implementation, you'd make an HTMX call here)
                    if (newPriority !== constructionData.priority) {
                        const confirmed = confirm(`Change priority from ${constructionData.priority} to ${newPriority}?`);
                        if (!confirmed) {
                            // Revert to original position
                            element.position(170 + ((constructionData.priority - lane.min) / (lane.max - lane.min)) * 800, lane.y + 20);
                        } else {
                            // Update display
                            element.attr('label/text', `${constructionData.label}\n(${newPriority})`);
                        }
                    }
                }
            });

            // Tooltip on hover
            paper.on('element:mouseenter', function(elementView) {
                const element = elementView.model;
                const constructionData = element.get('constructionData');

                if (constructionData) {
                    elementView.$el.attr('title', `Priority: ${constructionData.priority}, Type: ${constructionData.type}, Enabled: ${constructionData.enabled}`);
                }
            });
        })();
    </script>
</div>
