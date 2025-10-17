/**
 * Alpine.js Grapher Component
 * Renders frame relation graphs using JointJS library
 * Supports interactive manipulation and layout customization
 *
 * Usage:
 * <div x-data="grapher(config)" x-init="init()" id="graph"></div>
 */

export default function grapherComponent(config = {}) {
    return {
        // Configuration properties
        nodes: config.nodes || {},
        links: config.links || {},

        // Layout configuration
        connector: 'normal',
        ranker: 'network-simplex',
        rankdir: 'BT',
        align: 'DL',
        vertices: true,
        ranksep: 50,
        edgesep: 50,
        nodesep: 50,

        // JointJS instances
        graph: null,
        paper: null,
        panAndZoom: null,

        // State
        initialized: false,
        hoverTimer: null,

        /**
         * Initialize the grapher component
         */
        init() {
            if (this.initialized) {
                return;
            }

            // Ensure JointJS is loaded
            if (typeof joint === 'undefined') {
                console.error('Grapher: JointJS library not found');
                return;
            }

            // Wait for next tick to ensure DOM is ready
            this.$nextTick(() => {
                this.initializeGraph();
                this.layout();
                this.initialized = true;
            });

            // Listen for layout changes from modal
            this.setupEventListeners();
        },

        /**
         * Setup event listeners for graph interactions
         */
        setupEventListeners() {
            // Listen for layout trigger from modal
            document.addEventListener('grapher:relayout', () => {
                this.layout();
            });
        },

        /**
         * Initialize JointJS graph and paper
         */
        initializeGraph() {
            // Create graph
            this.graph = new joint.dia.Graph();

            // Find the #graph element within the component
            const graphEl = this.$el.querySelector('#graph') || this.$el;

            // Get container dimensions
            const containerWidth = graphEl.clientWidth || 800;
            const containerHeight = graphEl.clientHeight || 600;

            // Create paper
            this.paper = new joint.dia.Paper({
                el: graphEl,
                model: this.graph,
                width: containerWidth,
                height: containerHeight,
                gridSize: 1,
                background: {
                    color: '#ffffff'
                },
                interactive: true
            });

            // Setup event handlers for pan/zoom
            this.paper.on('blank:pointerdown', (evt, x, y) => {
                if (this.panAndZoom) {
                    this.panAndZoom.enablePan();
                }
            });

            this.paper.on('cell:pointerdown', (cellView, evt) => {
                // Disable pan when interacting with cells to allow dragging
                if (this.panAndZoom) {
                    this.panAndZoom.disablePan();
                }
            });

            this.paper.on('cell:pointerup blank:pointerup', (cellView, event) => {
                if (this.panAndZoom) {
                    this.panAndZoom.disablePan();
                }
            });

            // Setup other event handlers
            this.paper.on('cell:pointerclick', (cellView) => this.cellClick(cellView));
            this.paper.on('cell:pointerdblclick', (cellView) => this.cellDblClick(cellView));
            this.paper.on('cell:contextmenu', (cellView, evt) => this.cellContextMenu(cellView, evt));
            this.paper.on('link:mouseenter', (linkView) => this.linkEnter(linkView));
            this.paper.on('link:mouseleave', (linkView) => this.linkLeave(linkView));
            this.paper.on('element:mouseenter', (elementView) => this.elementEnter(elementView));
            this.paper.on('element:mouseleave', (elementView) => this.elementLeave(elementView));

            // Build the graph
            this.buildGraph();
        },

        /**
         * Build graph elements from nodes and links data
         */
        buildGraph() {
            const elements = [];
            const links = [];

            // Get connector type
            const connectorType = this.connector;

            // Create nodes
            for (const index in this.nodes) {
                const node = this.nodes[index];
                let rect;
                const text = node.name;
                let w;

                if (node.type === 'frame') {
                    // Frame node
                    w = Math.max(text.length * 8, 100);
                    rect = new joint.shapes.standard.Rectangle({
                        id: index,
                        z: 2,
                    });
                    rect.resize(w, 30);
                    rect.attr({
                        body: {
                            class: `color_${node.type}`,
                        },
                        label: {
                            class: `color_${node.type}--text`,
                            text: text,
                        }
                    });
                }

                if (node.type === 'fe') {
                    // Frame Element node
                    w = (text.length * 8) + 20;
                    rect = new joint.shapes.standard.Rectangle({
                        id: index,
                        z: 2,
                        attrs: {
                            body: {},
                            foreignObject: {
                                width: w,
                                height: 24,
                                class: `fe`,
                            },
                            fespanicon: {
                                class: node.icon
                            },
                            fespanname: {
                                class: `color_${node.idColor}`,
                            }
                        },
                        markup: [{
                            tagName: 'foreignObject',
                            selector: 'foreignObject',
                            children: [
                                {
                                    namespaceURI: 'http://www.w3.org/1999/xhtml',
                                    tagName: 'span',
                                    selector: 'fespanicon',
                                }, {
                                    namespaceURI: 'http://www.w3.org/1999/xhtml',
                                    tagName: 'span',
                                    selector: 'fespanname',
                                    textContent: text
                                }
                            ]
                        }]
                    });
                    rect.resize(w, 28);
                }

                if (rect) {
                    elements.push(rect);
                }
            }

            // Create links
            for (const source in this.links) {
                for (const target in this.links[source]) {
                    const relation = this.links[source][target];
                    const link = new joint.shapes.standard.Link({
                        source: { id: source },
                        target: { id: target },
                        attrs: {
                            line: {
                                class: `color_${relation.relationEntry}`,
                                strokeWidth: 1.5,
                                targetMarker: {
                                    'class': `color_${relation.relationEntry}--marker`,
                                    'type': 'path',
                                    'd': 'M 8 -2 0 0 8 2 z'
                                }
                            }
                        }
                    });

                    // Set connector type
                    link.connector(connectorType);

                    // Store metadata
                    link.prop({
                        data: {
                            id: relation.idEntityRelation,
                            type: relation.type,
                        }
                    });

                    links.push(link);
                }
            }

            // Add all elements to graph
            this.graph.resetCells(elements.concat(links));
        },

        /**
         * Apply layout algorithm to graph
         */
        layout() {
            if (!this.graph) {
                return;
            }

            // Ensure dagre and graphlib are available
            if (typeof dagre === 'undefined') {
                console.error('Grapher: dagre library not found');
                return;
            }

            joint.layout.DirectedGraph.layout(this.graph, {
                dagre: dagre,
                graphlib: dagre.graphlib,
                nodeSep: this.nodesep,
                edgeSep: this.edgesep,
                rankSep: this.ranksep,
                rankDir: this.rankdir,
                align: this.align,
                ranker: this.ranker
            });

            // Update vertices visibility
            const links = this.graph.getLinks();
            links.forEach(link => {
                if (!this.vertices) {
                    link.set('vertices', []);
                }
            });

            // Fit to content
            this.paper.scaleContentToFit({
                padding: 20,
                maxScale: 1
            });

            // Initialize pan and zoom if svgPanZoom is available
            if (typeof svgPanZoom !== 'undefined' && this.paper.svg) {
                // Destroy existing pan/zoom instance if it exists
                if (this.panAndZoom) {
                    this.panAndZoom.destroy();
                }

                // Create new pan/zoom instance
                this.panAndZoom = svgPanZoom(this.paper.svg, {
                    zoomEnabled: true,
                    controlIconsEnabled: true,
                    dblClickZoomEnabled: false,
                    fit: false,
                    center: false
                });

                this.panAndZoom.enableControlIcons();
                this.panAndZoom.disablePan();
            }
        },

        /**
         * Handle cell click event
         * Removed to avoid conflict with double-click
         * Report is now accessible via context menu
         */
        cellClick(cellView) {
            // No action on single click
        },

        /**
         * Handle cell double-click event
         */
        cellDblClick(cellView) {
            const currentElement = cellView.model;
            if (cellView.model.isElement()) {
                // Expand/collapse node - make HTMX request to add related nodes
                const graphType = this.getGraphType();
                htmx.ajax('POST', `/grapher/${graphType}/graph/${currentElement.id}`, { target: '#graph' });
            } else if (cellView.model.isLink()) {
                console.log(currentElement.source(), currentElement.target());
            }
        },

        /**
         * Handle cell context menu (right-click) event
         */
        cellContextMenu(cellView, evt) {
            const currentElement = cellView.model;
            if (cellView.model.isElement()) {
                evt.preventDefault();
                evt.stopPropagation();

                // Find the context menu component and show it
                const contextMenuEl = document.getElementById('grapherContextMenu');
                if (contextMenuEl && window.Alpine) {
                    const contextMenu = window.Alpine.$data(contextMenuEl);
                    if (contextMenu && contextMenu.show) {
                        contextMenu.show(evt, {
                            id: currentElement.id,
                            name: this.nodes[currentElement.id]?.name || '',
                            type: this.nodes[currentElement.id]?.type || ''
                        });
                    }
                }
            }
        },

        /**
         * Handle context menu actions
         */
        handleContextMenuAction(action, nodeId) {
            switch (action) {
                case 'view-report':
                    htmx.ajax('GET', `/grapher/frame/report/${nodeId}`, { target: '#frameReport' });
                    $('#grapherReportModal').modal('show');
                    break;
                case 'expand':
                    const graphType = this.getGraphType();
                    htmx.ajax('POST', `/grapher/${graphType}/graph/${nodeId}`, { target: '#graph' });
                    break;
                case 'remove':
                    const cell = this.graph.getCell(nodeId);
                    if (cell) {
                        cell.remove();
                    }
                    break;
            }
        },

        /**
         * Handle link mouse enter event
         */
        linkEnter(linkView) {
            const data = linkView.model.prop('data');
            let infoButton = null;

            if (data.type === 'ff') {
                // Add button to show FE relations
                infoButton = new joint.linkTools.Button({
                    markup: [{
                        tagName: 'image',
                        attributes: {
                            'href': '/images/reorder.svg',
                            'x': -12,
                            'y': -12
                        }
                    }],
                    distance: '50%',
                    offset: 0,
                    action: function (evt) {
                        htmx.ajax('POST', `/grapher/framefe/graph/${data.id}`, { target: '#graph' });
                    }
                });
            }

            const verticesTool = new joint.linkTools.Vertices();
            const toolsView = new joint.dia.ToolsView({
                tools: infoButton ? [verticesTool, infoButton] : [verticesTool]
            });

            linkView.addTools(toolsView);
            linkView.showTools();
        },

        /**
         * Handle link mouse leave event
         */
        linkLeave(linkView) {
            linkView.hideTools();
        },

        /**
         * Handle element mouse enter event
         */
        elementEnter(elementView) {
            const currentElement = elementView.model;

            // Clear any existing timer
            if (this.hoverTimer) {
                clearTimeout(this.hoverTimer);
            }

            // Add 300ms delay before showing context menu and tools
            this.hoverTimer = setTimeout(() => {
                // Small remove button centered on top-left corner
                const removeButton = new joint.elementTools.Remove({
                    offset: { x: 0, y: 0 },
                    markup: [{
                        tagName: 'circle',
                        selector: 'button',
                        attributes: {
                            'r': 7,
                            'fill': '#FF6B6B',
                            'cursor': 'pointer'
                        }
                    }, {
                        tagName: 'path',
                        selector: 'icon',
                        attributes: {
                            'd': 'M -3 -3 3 3 M -3 3 3 -3',
                            'fill': 'none',
                            'stroke': '#FFFFFF',
                            'stroke-width': 2,
                            'pointer-events': 'none'
                        }
                    }]
                });

                const toolsView = new joint.dia.ToolsView({
                    tools: [removeButton]
                });

                elementView.addTools(toolsView);
                elementView.showTools();

                // Show context menu on hover
                const bbox = elementView.getBBox();

                // Get the actual DOM element position in viewport coordinates
                const nodeEl = elementView.el;
                const nodeRect = nodeEl.getBoundingClientRect();

                const contextMenuEl = document.getElementById('grapherContextMenu');
                if (contextMenuEl && window.Alpine) {
                    const contextMenu = window.Alpine.$data(contextMenuEl);
                    if (contextMenu && contextMenu.showAtPosition) {
                        contextMenu.showAtPosition(
                            nodeRect.left,
                            nodeRect.bottom + 5,
                            {
                                id: currentElement.id,
                                name: this.nodes[currentElement.id]?.name || '',
                                type: this.nodes[currentElement.id]?.type || ''
                            }
                        );
                    }
                }
            }, 300);
        },

        /**
         * Handle element mouse leave event
         */
        elementLeave(elementView) {
            // Clear hover timer if leaving before tools appear
            if (this.hoverTimer) {
                clearTimeout(this.hoverTimer);
                this.hoverTimer = null;
            }

            elementView.hideTools();

            // Hide context menu when leaving element (unless mouse is over the menu)
            const contextMenuEl = document.getElementById('grapherContextMenu');
            if (contextMenuEl && window.Alpine) {
                const contextMenu = window.Alpine.$data(contextMenuEl);
                if (contextMenu && contextMenu.hide) {
                    // Small delay to allow moving mouse to the menu
                    setTimeout(() => {
                        if (!contextMenu.isMouseOver) {
                            contextMenu.hide();
                        }
                    }, 100);
                }
            }
        },

        /**
         * Get graph type from current URL
         */
        getGraphType() {
            const path = window.location.pathname;
            if (path.includes('/frame')) return 'frame';
            if (path.includes('/domain')) return 'domain';
            if (path.includes('/scenario')) return 'scenario';
            return 'frame'; // default
        },

        /**
         * Update graph data and rebuild
         */
        updateData(newNodes, newLinks) {
            this.nodes = newNodes || {};
            this.links = newLinks || {};

            // Check if paper's SVG still exists in DOM
            const graphEl = this.$el.querySelector('#graph') || this.$el;
            const paperStillExists = graphEl && graphEl.querySelector('svg');

            // If paper was removed by HTMX swap, reinitialize everything
            if (this.initialized && !paperStillExists) {
                this.initializeGraph();
                this.layout();
            } else if (this.graph && this.initialized) {
                // Paper exists, just rebuild with new data
                this.buildGraph();
                this.layout();
            }
        },

        /**
         * Clear the graph
         */
        clear() {
            if (this.graph) {
                this.graph.clear();
            }
        },

        /**
         * Public API method to trigger relayout from modal
         */
        relayout() {
            // Only rebuild if graph is already initialized
            if (this.graph && this.initialized) {
                // Rebuild graph to apply connector changes
                this.buildGraph();
                // Apply layout with new settings
                this.layout();
            }
        }
    };
}
