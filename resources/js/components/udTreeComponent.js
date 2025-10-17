/**
 * Alpine.js UD Tree Component
 * Renders hierarchical tree structures for Universal Dependencies parsing
 * Uses JointJS for visualization
 * Supports: horizontal tree, vertical tree, and dependency arc diagrams
 *
 * Usage:
 * <div x-data="udTree(config)" x-init="init()" id="tree-container"></div>
 */

export default function udTreeComponent(config = {}) {
    return {
        // Configuration properties
        data: config.data || {},
        orientation: config.orientation || 'horizontal', // 'horizontal', 'vertical', or 'arc'
        width: config.width || 928,
        nodeSize: config.nodeSize || { dx: 10, dy: null },
        containerSelector: config.containerSelector || null,
        linkColor: config.linkColor || '#555',
        linkOpacity: config.linkOpacity || 0.4,
        linkWidth: config.linkWidth || 1.5,
        nodeCircleSize: config.nodeCircleSize || 2.5,
        nodeParentColor: config.nodeParentColor || '#555',
        nodeLeafColor: config.nodeLeafColor || '#999',
        fontSize: config.fontSize || '10px',
        fontFamily: config.fontFamily || 'sans-serif',
        onNodeClick: config.onNodeClick || null,
        // Arc-specific colors
        deprelColor: config.deprelColor || '#00008b',
        posColor: config.posColor || '#004048',

        // State properties
        svgElement: null,
        root: null,
        tree: null,
        arcNodes: null, // For arc visualization

        // Computed properties
        get container() {
            if (this.containerSelector) {
                return document.querySelector(this.containerSelector);
            }
            return this.$el;
        },

        get hasData() {
            return this.data && Object.keys(this.data).length > 0;
        },

        // Initialization
        init() {
            if (!this.hasData) {
                console.warn('D3 Tree: No data provided');
                this.showEmptyMessage();
                return;
            }

            if (!window.d3) {
                console.error('D3 Tree: D3.js library not found');
                return;
            }

            this.render();
        },

        // Main render method
        render() {
            // Clear previous tree
            this.destroy();

            if (this.orientation === 'arc') {
                // Build and render arc diagram
                this.buildArcTree();
                this.renderArcLinks();
                this.renderArcNodes();
            } else {
                // Build D3 hierarchy and layout
                this.buildTree();
                // Render links and nodes
                this.renderLinks();
                this.renderNodes();
            }

            // Append to container
            if (this.svgElement && this.container) {
                this.container.appendChild(this.svgElement.node());
            }
        },

        // Build D3 tree structure
        buildTree() {
            const d3 = window.d3;

            // Create hierarchy
            this.root = d3.hierarchy(this.data);

            // Configure node spacing based on orientation
            // For vertical trees, we need more horizontal space (dx) to prevent label overlap
            let dx = this.nodeSize.dx;
            if (this.orientation === 'vertical') {
                dx = Math.max(dx, 80); // Increase minimum spacing for vertical orientation
            }
            const dy = this.nodeSize.dy || this.width / (this.root.height + 1);

            // Create tree layout
            this.tree = d3.tree().nodeSize([dx, dy]);

            // Sort nodes
            this.root.sort((a, b) => d3.ascending(a.data.name, b.data.name));

            // Apply layout
            this.tree(this.root);

            // Calculate dimensions for both x and y
            let x0 = Infinity;
            let x1 = -x0;
            let y0 = Infinity;
            let y1 = -y0;
            this.root.each(d => {
                if (d.x > x1) x1 = d.x;
                if (d.x < x0) x0 = d.x;
                if (d.y > y1) y1 = d.y;
                if (d.y < y0) y0 = d.y;
            });

            // Add margins for better visibility
            const marginTop = dx;
            const marginRight = dy;
            const marginBottom = dx;
            const marginLeft = dy;

            // Calculate SVG dimensions based on orientation
            let svgWidth, svgHeight, viewBoxX, viewBoxY, viewBoxWidth, viewBoxHeight;

            if (this.orientation === 'vertical') {
                // Vertical: tree extends in Y direction (depth)
                svgWidth = x1 - x0 + dx * 2 + marginLeft + marginRight;
                svgHeight = y1 - y0 + dy * 2 + marginTop + marginBottom;
                viewBoxX = x0 - marginLeft;
                viewBoxY = y0 - marginTop;
                viewBoxWidth = x1 - x0 + dx * 2 + marginLeft + marginRight;
                viewBoxHeight = y1 - y0 + dy * 2 + marginTop + marginBottom;
            } else {
                // Horizontal: tree extends in Y direction (depth) but rotated
                svgWidth = this.width;
                svgHeight = x1 - x0 + dx * 2;
                viewBoxX = -marginLeft;
                viewBoxY = x0 - marginTop;
                viewBoxWidth = this.width + marginLeft + marginRight;
                viewBoxHeight = x1 - x0 + dx * 2 + marginTop + marginBottom;
            }

            // Create SVG with proper viewBox to prevent cutting
            this.svgElement = d3.create("svg")
                .attr("width", svgWidth)
                .attr("height", svgHeight)
                .attr("viewBox", [viewBoxX, viewBoxY, viewBoxWidth, viewBoxHeight])
                .attr("style", `max-width: 100%; height: auto; font: ${this.fontSize} ${this.fontFamily};`);
        },

        // Render tree links (edges)
        renderLinks() {
            const d3 = window.d3;

            if (this.orientation === 'vertical') {
                // Vertical tree (top to bottom)
                this.svgElement.append("g")
                    .attr("fill", "none")
                    .attr("stroke", this.linkColor)
                    .attr("stroke-opacity", this.linkOpacity)
                    .attr("stroke-width", this.linkWidth)
                    .selectAll()
                    .data(this.root.links())
                    .join("path")
                    .attr("d", d3.linkVertical()
                        .x(d => d.x)
                        .y(d => d.y));
            } else {
                // Horizontal tree (left to right)
                this.svgElement.append("g")
                    .attr("fill", "none")
                    .attr("stroke", this.linkColor)
                    .attr("stroke-opacity", this.linkOpacity)
                    .attr("stroke-width", this.linkWidth)
                    .selectAll()
                    .data(this.root.links())
                    .join("path")
                    .attr("d", d3.linkHorizontal()
                        .x(d => d.y)
                        .y(d => d.x));
            }
        },

        // Render tree nodes
        renderNodes() {
            const d3 = window.d3;

            const nodeGroup = this.svgElement.append("g")
                .attr("stroke-linejoin", "round")
                .attr("stroke-width", 3)
                .selectAll()
                .data(this.root.descendants())
                .join("g");

            // Position nodes based on orientation
            if (this.orientation === 'vertical') {
                nodeGroup.attr("transform", d => `translate(${d.x},${d.y})`);
            } else {
                nodeGroup.attr("transform", d => `translate(${d.y},${d.x})`);
            }

            // Add click handler if provided
            if (this.onNodeClick) {
                nodeGroup.style("cursor", "pointer")
                    .on("click", (event, d) => {
                        if (typeof this.onNodeClick === 'function') {
                            this.onNodeClick(d, event);
                        }
                    });
            }

            // Render circles
            nodeGroup.append("circle")
                .attr("fill", d => d.children ? this.nodeParentColor : this.nodeLeafColor)
                .attr("r", this.nodeCircleSize);

            // Render text labels
            if (this.orientation === 'vertical') {
                // Vertical: labels below nodes
                nodeGroup.append("text")
                    .attr("dy", "0.71em")
                    .attr("x", 0)
                    .attr("text-anchor", "middle")
                    .text(d => d.data.name)
                    .attr("stroke", "white")
                    .attr("paint-order", "stroke");
            } else {
                // Horizontal: labels left/right of nodes
                nodeGroup.append("text")
                    .attr("dy", "0.31em")
                    .attr("x", d => d.children ? -6 : 6)
                    .attr("text-anchor", d => d.children ? "end" : "start")
                    .text(d => d.data.name)
                    .attr("stroke", "white")
                    .attr("paint-order", "stroke");
            }
        },

        // Update tree with new data
        update(newData) {
            this.data = newData;
            this.render();
        },

        // Set orientation
        setOrientation(orientation) {
            if (orientation === 'horizontal' || orientation === 'vertical' || orientation === 'arc') {
                this.orientation = orientation;
                this.render();
            }
        },

        // Build arc diagram tree structure
        buildArcTree() {
            const d3 = window.d3;

            // Flatten tree to get all nodes and preserve parent relationships
            const nodes = [];
            const nodeMap = new Map(); // Map node.data reference to array index

            const collectNodes = (node, parentRef = null, depth = 0) => {
                // Parse node name: "word [POS] [rel]"
                const match = node.name.match(/^(.+?)\s+\[([^\]]+)\]\s+\[([^\]]+)\]$/);
                const word = match ? match[1] : node.name;
                const pos = match ? match[2] : '';
                const rel = match ? match[3] : '';

                const nodeData = {
                    id: node.id || nodes.length + 1, // Sentence position (1-indexed)
                    word: word,
                    pos: pos,
                    rel: rel,
                    depth: depth,
                    parentRef: parentRef, // Reference to parent node.data
                    data: node
                };

                nodes.push(nodeData);
                nodeMap.set(node, nodes.length - 1);

                if (node.children) {
                    node.children.forEach(child => collectNodes(child, node, depth + 1));
                }
            };

            collectNodes(this.data);

            // Sort nodes by ID to get sentence order
            nodes.sort((a, b) => a.id - b.id);

            // Build parent indices based on sentence-ordered positions
            nodes.forEach((node, i) => {
                if (node.parentRef) {
                    const parentIndex = nodeMap.get(node.parentRef);
                    if (parentIndex !== undefined) {
                        // Find where parent is in sorted array
                        const parentSortedIndex = nodes.findIndex(n => nodeMap.get(n.data) === parentIndex);
                        node.parentIndex = parentSortedIndex;
                    }
                }
            });

            this.arcNodes = nodes;

            // Calculate layout dimensions
            const wordSpacing = 80;
            const baselineY = 250;
            const marginLeft = 40;
            const marginRight = 40;
            const marginTop = 150;
            const marginBottom = 100;

            // Position nodes horizontally in sentence order
            nodes.forEach((node, i) => {
                node.x = marginLeft + i * wordSpacing;
                node.y = baselineY;
            });

            // Calculate arc layers to avoid crossings
            this.calculateArcLayers(nodes);

            // Calculate SVG dimensions with enough space for tall arcs
            const svgWidth = nodes.length * wordSpacing + marginLeft + marginRight;
            const svgHeight = baselineY + marginBottom;

            // Create SVG
            this.svgElement = d3.create("svg")
                .attr("width", svgWidth)
                .attr("height", svgHeight)
                .attr("viewBox", [0, 0, svgWidth, svgHeight])
                .attr("style", `max-width: 100%; height: auto; font: ${this.fontSize} ${this.fontFamily};`);
        },

        // Calculate arc layers to avoid crossings and show hierarchy
        calculateArcLayers(nodes) {
            // Create arc information for each dependency
            const arcs = [];
            nodes.forEach((node, i) => {
                if (node.parentIndex !== undefined && node.parentIndex !== null) {
                    const start = Math.min(i, node.parentIndex);
                    const end = Math.max(i, node.parentIndex);
                    const distance = end - start;
                    arcs.push({
                        nodeIndex: i,
                        start: start,
                        end: end,
                        distance: distance,
                        layer: 0
                    });
                }
            });

            // Sort arcs by distance (shorter arcs get lower layers)
            arcs.sort((a, b) => a.distance - b.distance);

            // Assign layers to avoid crossings
            arcs.forEach(arc => {
                let layer = 0;
                let hasConflict = true;

                while (hasConflict) {
                    hasConflict = false;
                    // Check if this arc conflicts with any arc at current layer
                    for (const otherArc of arcs) {
                        if (otherArc === arc) continue;
                        if (otherArc.layer !== layer) continue;

                        // Check if arcs cross
                        if (this.arcsCross(arc.start, arc.end, otherArc.start, otherArc.end)) {
                            hasConflict = true;
                            break;
                        }
                    }
                    if (hasConflict) {
                        layer++;
                    }
                }
                arc.layer = layer;
            });

            // Store arc information in nodes
            arcs.forEach(arc => {
                nodes[arc.nodeIndex].arcLayer = arc.layer;
                nodes[arc.nodeIndex].arcDistance = arc.distance;
            });
        },

        // Check if two arcs cross each other
        arcsCross(a1, a2, b1, b2) {
            // Two arcs cross if one's start is between the other's start and end
            // and they don't share endpoints
            return (a1 < b1 && b1 < a2 && a2 < b2) || (b1 < a1 && a1 < b2 && b2 < a2);
        },

        // Render arc links (curved dependency arcs)
        renderArcLinks() {
            const d3 = window.d3;
            const nodes = this.arcNodes;

            // Create arcs for dependencies
            const links = [];
            nodes.forEach((node, i) => {
                if (node.parentIndex !== undefined && node.parentIndex !== null) {
                    links.push({
                        source: nodes[node.parentIndex],
                        target: node,
                        layer: node.arcLayer || 0,
                        distance: node.arcDistance || 1
                    });
                }
            });

            const linkGroup = this.svgElement.append("g")
                .attr("fill", "none")
                .attr("stroke", this.linkColor)
                .attr("stroke-width", this.linkWidth);

            linkGroup.selectAll("path")
                .data(links)
                .join("path")
                .attr("d", d => {
                    const x1 = d.source.x;
                    const x2 = d.target.x;
                    const y = d.source.y;

                    // Calculate arc height based on:
                    // 1. Distance between words (longer = taller)
                    // 2. Arc layer (higher layer = taller to avoid crossings)
                    const baseHeight = 20;
                    const distanceMultiplier = 15;
                    const layerMultiplier = 25;

                    const arcHeight = baseHeight +
                                     (d.distance * distanceMultiplier) +
                                     (d.layer * layerMultiplier);

                    // Create curved arc
                    const midX = (x1 + x2) / 2;
                    const controlY = y - arcHeight;

                    return `M ${x1},${y} Q ${midX},${controlY} ${x2},${y}`;
                })
                .attr("stroke-opacity", this.linkOpacity);
        },

        // Render arc nodes (words with multi-line labels)
        renderArcNodes() {
            const d3 = window.d3;
            const nodes = this.arcNodes;

            const nodeGroup = this.svgElement.append("g");

            const nodeElements = nodeGroup.selectAll("g")
                .data(nodes)
                .join("g")
                .attr("transform", d => `translate(${d.x},${d.y})`);

            // Add click handler if provided
            if (this.onNodeClick) {
                nodeElements.style("cursor", "pointer")
                    .on("click", (event, d) => {
                        if (typeof this.onNodeClick === 'function') {
                            this.onNodeClick(d, event);
                        }
                    });
            }

            // Add small circle at baseline
            nodeElements.append("circle")
                .attr("r", 2)
                .attr("fill", "#333");

            // Add word (line 1)
            nodeElements.append("text")
                .attr("y", 20)
                .attr("text-anchor", "middle")
                .attr("font-size", "12px")
                .attr("fill", "#000")
                .text(d => d.word);

            // Add dependency relation (line 2) - blue
            nodeElements.append("text")
                .attr("y", 35)
                .attr("text-anchor", "middle")
                .attr("font-size", "10px")
                .attr("fill", this.deprelColor)
                .text(d => d.rel);

            // Add POS tag (line 3) - teal
            nodeElements.append("text")
                .attr("y", 48)
                .attr("text-anchor", "middle")
                .attr("font-size", "10px")
                .attr("fill", this.posColor)
                .text(d => d.pos);
        },

        // Clean up SVG
        destroy() {
            if (this.container) {
                this.container.innerHTML = '';
            }
            this.svgElement = null;
        },

        // Show empty message
        showEmptyMessage() {
            if (this.container) {
                this.container.innerHTML = '<div class="ui message warning">No tree data available.</div>';
            }
        },

        // Public API methods
        refresh() {
            this.render();
        },

        clear() {
            this.data = {};
            this.destroy();
            this.showEmptyMessage();
        }
    };
}
