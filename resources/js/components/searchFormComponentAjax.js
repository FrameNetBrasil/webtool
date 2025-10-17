export default function () {
    return {
        searchParams: {},
        currentToast: null,

        // Initialize search parameters from form inputs
        init() {
            this.initSearchParams();
        },

        // Initialize search parameters with field names from form
        initSearchParams() {
            const form = this.$el.querySelector('form');
            if (!form) return;

            const params = {};
            const inputs = form.querySelectorAll('input[name]:not([name="_token"])');
            
            inputs.forEach(input => {
                params[input.name] = '';
            });

            this.searchParams = params;
        },

        // Collect current form data values
        collectFormData() {
            const form = this.$el.querySelector('form');
            if (!form) return {};

            const params = {};
            const inputs = form.querySelectorAll('input[name]:not([name="_token"])');
            
            inputs.forEach(input => {
                if (input.value && input.value.trim() !== '') {
                    params[input.name] = input.value.trim();
                }
            });

            return params;
        },

        async performSearch() {
            // Use the reactive searchParams directly from Alpine.js
            const searchParams = {};
            
            // Only include non-empty values
            Object.keys(this.searchParams).forEach(key => {
                if (this.searchParams[key] && this.searchParams[key].trim() !== '') {
                    searchParams[key] = this.searchParams[key].trim();
                }
            });
            
            // Store for later use
            window.currentSearchParams = searchParams;

            // Show search toast
            this.showSearchToast();

            try {
                // Get the current page's data endpoint from the tree component
                const treeElement = document.querySelector('x-ui\\:tree, [x-ui\\:tree]');
                let searchEndpoint = '/lexicon3/data'; // default fallback
                
                if (treeElement) {
                    const urlAttribute = treeElement.getAttribute('url');
                    if (urlAttribute) {
                        searchEndpoint = urlAttribute;
                    }
                }
                
                console.log("Calling API directly:", searchEndpoint, "with params:", searchParams);

                // Call the API directly
                const formData = new FormData();
                Object.entries(searchParams).forEach(([key, value]) => {
                    formData.append(key, value);
                });
                
                const response = await fetch(searchEndpoint, {
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
                console.log("API Response:", result);
                
                if (result.success) {
                    // Update the tree view with new data
                    this.updateTreeView(result.data);
                    console.log(`Search completed - loaded ${result.count} items`);
                } else {
                    throw new Error(result.message || 'Unknown error occurred');
                }

            } catch (error) {
                console.error("Search failed:", error);
            } finally {
                // Hide the search toast
                this.hideSearchToast();
            }
        },

        updateTreeView(data) {
            // Find the results container and update it
            const resultsTree = document.querySelector('.search-results-tree');
            if (resultsTree && Object.keys(data).length > 0) {
                // Build simple tree HTML structure
                let html = '<div class="ui tree-container" x-data="treeComponent()" x-init="init()">';
                html += '<div class="tree-body"><table class="ui very basic table tree-table"><tbody>';
                
                Object.values(data).forEach(item => {
                    html += `<tr class="row-data transition-enabled">
                        <td class="center aligned"><i class="ui icon"></i></td>
                        <td class="content-cell">
                            <span class="ui tree-item-text clickable" 
                                  onclick="if('${item.type}' === 'lemma') { window.location.assign('/lexicon3/lemma/${item.id}'); } 
                                          else if('${item.type}' === 'form') { window.location.assign('/lexicon3/form/${item.id}'); }">
                                ${item.text}
                            </span>
                        </td>
                    </tr>`;
                });
                
                html += '</tbody></table></div></div>';
                resultsTree.innerHTML = html;
            } else if (resultsTree) {
                // Show empty state
                resultsTree.innerHTML = `
                    <div class="empty-state">
                        <i class="search icon empty-icon"></i>
                        <h3 class="empty-title">No results found.</h3>
                        <p class="empty-description">Try different search terms.</p>
                    </div>
                `;
            }
        },

        onSearchStart(event) {
            // Prevent default form submission
            event.preventDefault();
            
            // Perform the Ajax search instead
            this.performSearch();
        },

        showSearchToast() {
            // Close any existing toast first
            this.hideSearchToast();
            // Create and show the search toast
            this.currentToast = $("body").toast({
                message: "Searching ...",
                class: "info",
                showIcon: "search",
                displayTime: 0, // Don't auto-hide
                position: "top center",
                showProgress: false,
                closeIcon: false,
                silent: true
            });
        },

        hideSearchToast() {
            // Remove the search toast
            if (this.currentToast) {
                $(".ui.toast").toast("close");
                this.currentToast = null;
            }
        },

        updateQueryDisplay() {
        }
    };
}
