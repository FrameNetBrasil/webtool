export default function () {
    return {
        // Configuration (will be overridden by data attributes)
        title: "",
        baseUrl: "/report/frame/data",
        searchEndpoint: "/report/frame/data",

        // Data
        items: [],

        // State
        expandedNodes: {},
        loadingNodes: {},
        loadedNodes: {},
        selectedItem: null,
        loading: false,
        error: null,

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
        },

        // Read data attributes from the element
        readDataAttributes() {
            const element = this.$el;

            // Read title
            if (element.dataset.title) {
                this.title = element.dataset.title;
            }

            // Read search endpoint
            if (element.dataset.searchEndpoint) {
                this.searchEndpoint = element.dataset.searchEndpoint;
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

        // Collect search parameters from the parent form
        collectSearchParams() {
            const searchSection = document.querySelector('.search-section');
            if (!searchSection) return {};

            const params = {};
            const inputs = searchSection.querySelectorAll('input[name]:not([name="_token"])');
            
            inputs.forEach(input => {
                if (input.value && input.value.trim() !== '') {
                    params[input.name] = input.value.trim();
                }
            });

            return params;
        },

        // Load data via Ajax with dynamic parameters
        async loadData(searchParams = {}) {
            this.loading = true;
            this.error = null;

            try {
                // If no params provided, collect them from the form
                if (Object.keys(searchParams).length === 0) {
                    searchParams = this.collectSearchParams();
                }

                const formData = new FormData();
                
                // Add all search parameters dynamically
                Object.entries(searchParams).forEach(([key, value]) => {
                    if (value) {
                        formData.append(key, value);
                    }
                });
                
                const response = await fetch(this.searchEndpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                
                if (result.success) {
                    this.items = result.data;
                    this.reload(); // Reset tree state
                    console.log(`Loaded ${result.count} items for search:`, searchParams);
                } else {
                    throw new Error(result.message || 'Unknown error occurred');
                }
            } catch (error) {
                console.error('Failed to load tree data:', error);
                this.error = error.message;
                this.items = [];
            } finally {
                this.loading = false;
            }
        },

        // Toggle node expansion
        toggleNode(itemId) {
            const wasExpanded = this.expandedNodes[itemId];
            this.expandedNodes[itemId] = !wasExpanded;

            console.log(`Node ${itemId} ${this.expandedNodes[itemId] ? "expanded" : "collapsed"}`);
        },

        // Select item
        selectItem(itemId, type) {
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
                    type: type
                },
                bubbles: true
            }));
        },


        // Public API methods
        reload() {
            // Explicitly reset all state to ensure clean initialization
            this.expandedNodes = {};
            this.loadingNodes = {};
            this.loadedNodes = {};
            this.selectedItem = null;
            
            console.log("Tree component reloaded - all nodes collapsed");
            
            // Force reactivity update by triggering a dummy change
            this.$nextTick(() => {
                // This ensures Alpine's reactivity system recognizes the state change
                this.expandedNodes = {...this.expandedNodes};
            });
        },

        expandAll() {
            this.items.forEach(item => {
                this.expandedNodes[item.id] = true;
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
