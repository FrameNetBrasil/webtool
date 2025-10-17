export default function () {
    return {
        // Configuration
        context: 'main',
        defaultTab: '',
        tabs: [],
        
        // State
        currentTab: '',
        loadedTabs: {},
        loadingTabs: {},
        
        // Initialization
        init() {
            // Read configuration from data attributes
            this.readDataAttributes();
            
            // Initialize Fomantic UI tabs
            this.initializeFomanticTabs();
            
            // Set up HTMX event listeners
            this.setupHTMXEventListeners();
            
            // Load default tab content
            if (this.defaultTab) {
                this.currentTab = this.defaultTab;
                this.$nextTick(() => {
                    this.loadTabContent(this.defaultTab);
                });
            }
        },

        // Read data attributes from the element
        readDataAttributes() {
            const element = this.$el;
            
            this.context = element.dataset.context || 'main';
            this.defaultTab = element.dataset.defaultTab || '';
            
            if (element.dataset.tabs) {
                try {
                    this.tabs = JSON.parse(element.dataset.tabs);
                } catch (e) {
                    console.error('Invalid tabs JSON:', e);
                    this.tabs = [];
                }
            }
        },

        // Initialize Fomantic UI tabs with callbacks
        initializeFomanticTabs() {
            const self = this;
            
            // Initialize Fomantic UI tabs
            $(this.$el).find('.ui.tabs.menu .item').tab({
                context: this.$el,
                onFirstLoad: function(tabPath) {
                    // Load content when tab is first accessed
                    self.loadTabContent(tabPath);
                },
                onLoad: function(tabPath) {
                    // Load content when tab is switched to
                    self.currentTab = tabPath;
                    if (!self.hasTabContent(tabPath)) {
                        self.loadTabContent(tabPath);
                    }
                }
            });
        },

        // Set up HTMX event listeners
        setupHTMXEventListeners() {
            // Listen for HTMX loading events
            this.$el.addEventListener('tab-loading-start', (e) => {
                this.showTabLoading(e.detail);
            });
            
            this.$el.addEventListener('tab-loading-end', (e) => {
                this.hideTabLoading(e.detail);
            });
            
            this.$el.addEventListener('tab-loading-error', (e) => {
                this.showTabError(e.detail);
            });
        },

        // Utility Functions (AlpineJS)
        
        // Switch to a specific tab programmatically
        switchToTab(tabId) {
            const tabElement = this.$el.querySelector(`.ui.tabs.menu .item[data-tab="${tabId}"]`);
            if (tabElement) {
                $(tabElement).tab('change tab', tabId);
            }
        },

        // Check if tab content is already loaded
        hasTabContent(tabId) {
            const contentDiv = this.$el.querySelector(`#${tabId}-content`);
            return contentDiv && (
                this.loadedTabs[tabId] || 
                (contentDiv.children.length > 0 && contentDiv.textContent.trim() !== '')
            );
        },

        // Load tab content via HTMX
        loadTabContent(tabId) {
            if (this.loadingTabs[tabId] || this.hasTabContent(tabId)) {
                return;
            }

            const tab = this.tabs.find(t => t.id === tabId);
            if (!tab || !tab.url) {
                console.warn(`No URL defined for tab: ${tabId}`);
                return;
            }

            // Mark as loading
            this.loadingTabs[tabId] = true;
            
            // Show loading indicator
            this.showTabLoading(tabId);

            // Trigger HTMX load
            document.body.dispatchEvent(new CustomEvent(`load-${tabId}`));
        },

        // Retry loading content for a tab
        retryLoadContent(tabId) {
            this.loadedTabs[tabId] = false;
            this.loadingTabs[tabId] = false;
            this.hideTabError(tabId);
            this.loadTabContent(tabId);
        },

        // Loading State Management
        
        showTabLoading(tabId) {
            const loadingIndicator = this.$el.querySelector(`[data-tab="${tabId}"] .tab-loading-indicator`);
            const contentDiv = this.$el.querySelector(`#${tabId}-content`);
            const errorDiv = this.$el.querySelector(`[data-tab="${tabId}"] .tab-error`);
            
            if (loadingIndicator) loadingIndicator.style.display = 'block';
            if (contentDiv) contentDiv.style.display = 'none';
            if (errorDiv) errorDiv.style.display = 'none';
        },

        hideTabLoading(tabId) {
            const loadingIndicator = this.$el.querySelector(`[data-tab="${tabId}"] .tab-loading-indicator`);
            const contentDiv = this.$el.querySelector(`#${tabId}-content`);
            
            if (loadingIndicator) loadingIndicator.style.display = 'none';
            if (contentDiv) {
                contentDiv.style.display = '';  // Reset to default, let CSS handle visibility
            }
            
            // Mark as loaded and not loading
            this.loadedTabs[tabId] = true;
            this.loadingTabs[tabId] = false;
        },

        showTabError(tabId) {
            const loadingIndicator = this.$el.querySelector(`[data-tab="${tabId}"] .tab-loading-indicator`);
            const contentDiv = this.$el.querySelector(`#${tabId}-content`);
            const errorDiv = this.$el.querySelector(`[data-tab="${tabId}"] .tab-error`);
            
            if (loadingIndicator) loadingIndicator.style.display = 'none';
            if (contentDiv) contentDiv.style.display = 'none';
            if (errorDiv) errorDiv.style.display = 'block';
            
            // Mark as not loading
            this.loadingTabs[tabId] = false;
        },

        hideTabError(tabId) {
            const errorDiv = this.$el.querySelector(`[data-tab="${tabId}"] .tab-error`);
            if (errorDiv) errorDiv.style.display = 'none';
        },

        // Public API methods for external access
        
        // Get current active tab
        getCurrentTab() {
            return this.currentTab;
        },

        // Get all loaded tabs
        getLoadedTabs() {
            return Object.keys(this.loadedTabs).filter(tabId => this.loadedTabs[tabId]);
        },

        // Reload a specific tab
        reloadTab(tabId) {
            const contentDiv = this.$el.querySelector(`#${tabId}-content`);
            if (contentDiv) {
                contentDiv.innerHTML = '';
            }
            this.loadedTabs[tabId] = false;
            this.loadTabContent(tabId);
        },

        // Reload all tabs
        reloadAllTabs() {
            this.tabs.forEach(tab => {
                this.reloadTab(tab.id);
            });
        }
    };
}