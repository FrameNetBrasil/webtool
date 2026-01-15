export default function () {
    return {
        // Configuration (will be overridden by data attributes)
        title: "",
        baseUrl: "/api/tree",

        // Data
        items: [],

        // State
        expandedNodes: {},
        loadingNodes: {},
        loadedNodes: {},
        selectedItem: null,

        // Events
        onItemClick: null, // Function to be set by parent

        // Initialization
        init() {
            // Read configuration from data attributes
            this.readDataAttributes();

            // console.log("Tree component initialized with:", {
            //     title: this.title,
            //     baseUrl: this.baseUrl,
            //     itemsCount: this.items.length
            // });

            // Set up HTMX event listeners
            this.setupHTMXEvents();
        },

        // Read data attributes from the element
        readDataAttributes() {
            const element = this.$el;

            // Read title
            if (element.dataset.title) {
                this.title = element.dataset.title;
            }

            // Read base URL
            if (element.dataset.baseUrl) {
                this.baseUrl = element.dataset.baseUrl;
            }

            // Read items
            if (element.dataset.items) {
                try {
                    this.items = JSON.parse(element.dataset.items);
                } catch (e) {
                    console.error("Invalid items JSON:", e);
                    this.items = [];
                }
            }
        },

        // Setup HTMX event handling
        setupHTMXEvents() {
            // Listen for HTMX events on the document
            document.addEventListener("htmx:responseError", (event) => {
                console.error("HTMX Error:", event.detail);
                this.loadingNodes[this.getItemIdFromTarget(event.target)] = false;
            });
        },

        // Toggle node expansion
        toggleNode(itemId) {
            const wasExpanded = this.expandedNodes[itemId];
            this.expandedNodes[itemId] = !wasExpanded;

            // If expanding and not loaded yet, trigger HTMX load
            if (!wasExpanded && !this.loadedNodes[itemId]) {
                this.$nextTick(() => {
                    // Trigger HTMX request
                    document.body.dispatchEvent(new CustomEvent(`load-${itemId}`));
                });
            }

            console.log(`Node ${itemId} ${this.expandedNodes[itemId] ? "expanded" : "collapsed"}`);
        },

        // Select item
        selectItem(itemId, type, item) {
            this.selectedItem = itemId;
            // console.log(`Item selected: ${itemId}  ${type}`);

            // Call custom callback if provided
            if (this.onItemClick && typeof this.onItemClick === "function") {
                this.onItemClick(itemId);
            }

            // Dispatch custom event
            this.$el.dispatchEvent(new CustomEvent("tree-item-selected", {
                detail: {
                    tree: this,
                    id: itemId,
                    type: type,
                    item: item
                },
                bubbles: true
            }));
        },

        // Process loaded content from HTMX
        processLoadedContent(target) {
            const itemId = this.getItemIdFromTarget(target);
            this.loadedNodes[itemId] = true;

            // Re-initialize AlpineJS for dynamically loaded content if needed
            // This would be needed if the loaded content contains Alpine directives
            // console.log(`Content loaded for: ${itemId}`);
        },

        // Get item ID from HTMX target
        getItemIdFromTarget(target) {
            const treeDiv = target.closest("[id^=\"tree_\"]");
            return treeDiv ? treeDiv.id.replace("tree_", "") : null;
        },

        // Public API methods
        reload() {
            this.expandedNodes = {};
            this.loadingNodes = {};
            this.loadedNodes = {};
            this.selectedItem = null;
            // console.log("Tree reloaded");
        },

        expandAll() {
            this.items.forEach(item => {
                this.expandedNodes[item.id] = true;
                if (!this.loadedNodes[item.id]) {
                    this.$nextTick(() => {
                        document.body.dispatchEvent(new CustomEvent(`load-${item.id}`));
                    });
                }
            });
        },

        collapseAll() {
            this.expandedNodes = {};
        },

        expandNode(itemId) {
            if (!this.expandedNodes[itemId]) {
                this.toggleNode(itemId);
            }
        },

        collapseNode(itemId) {
            if (this.expandedNodes[itemId]) {
                this.toggleNode(itemId);
            }
        },

        getExpandedNodes() {
            return Object.keys(this.expandedNodes).filter(id => this.expandedNodes[id]);
        },

        setItems(newItems) {
            this.items = newItems;
            this.reload();
        },

        // Update configuration from attributes (useful for dynamic updates)
        updateFromAttributes() {
            this.readDataAttributes();
        },

        toggleNodeState(itemId) {
            this.toggleNode(itemId);
        },

        // Set configuration programmatically
        setConfig(config) {
            if (config.title !== undefined) this.title = config.title;
            if (config.baseUrl !== undefined) this.baseUrl = config.baseUrl;
            if (config.items !== undefined) this.items = config.items;
        }
    };
}
