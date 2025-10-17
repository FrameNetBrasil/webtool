export default function (config) {
    return {
        // Configuration
        name: config.name,
        searchUrl: config.searchUrl,
        displayField: config.displayField,
        searchField: config.searchField || config.displayField, // Falls back to displayField
        displayFormatter: config.displayFormatter,
        valueField: config.valueField,
        onChange: config.onChange,
        resolveUrl: config.resolveUrl,

        // State
        isModalOpen: false,
        isLoading: false,
        searchPerformed: false,
        selectedValue: config.initialValue || '',
        displayValue: config.initialDisplayValue || '',
        rawDisplayValue: config.initialDisplayValue || '', // Clean text version for display
        searchValue: config.initialSearchValue || '', // Value to use for search pre-population
        searchResults: [],
        errorMessage: '',

        // Search parameters (from config)
        searchParams: {},

        // Helper method to format result display
        formatResultDisplay(result) {
            if (this.displayFormatter && typeof window[this.displayFormatter] === 'function') {
                return window[this.displayFormatter](result);
            }
            return result[this.displayField] || '';
        },

        // Helper method to resolve display value from value ID
        async resolveDisplayValue(value) {
            if (!value || !this.resolveUrl) return { formatted: '', raw: '', search: '' };

            try {
                const url = new URL(this.resolveUrl, window.location.origin);
                url.searchParams.append(this.valueField, value);

                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                const item = data.result || data;

                const rawValue = item[this.displayField] || '';
                const searchValue = item[this.searchField] || rawValue;
                const formattedValue = (this.displayFormatter && typeof window[this.displayFormatter] === 'function')
                    ? window[this.displayFormatter](item)
                    : rawValue;

                return { formatted: formattedValue, raw: rawValue, search: searchValue };

            } catch (error) {
                console.error('Error resolving display value:', error);
                const fallback = `ID: ${value}`;
                return { formatted: fallback, raw: fallback, search: fallback };
            }
        },

        init() {
            // Initialize search parameters from config
            if (Array.isArray(config.searchFields)) {
                config.searchFields.forEach(field => {
                    this.searchParams[field] = '';
                });
            } else {
                this.searchParams = config.searchFields || {};
            }

            // Resolve display value if we have a value but no display value
            if (this.selectedValue && !this.displayValue && this.resolveUrl) {
                this.resolveDisplayValue(this.selectedValue).then(resolved => {
                    this.displayValue = resolved.formatted;
                    this.rawDisplayValue = resolved.raw;
                    this.searchValue = resolved.search;
                });
            }

            // Ensure modal starts closed
            this.isModalOpen = false;

            // Close modal on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isModalOpen) {
                    this.closeModal();
                }
            });
        },

        openModal() {
            this.isModalOpen = true;
            this.errorMessage = '';
            document.body.classList.add('modal-open');

            // Populate search fields with current search value if available
            const valueToSearch = this.searchValue || this.rawDisplayValue;
            if (valueToSearch && valueToSearch.trim() !== '') {
                // Get the first search field name to populate with current search value
                const firstFieldName = Object.keys(this.searchParams)[0];
                if (firstFieldName) {
                    this.searchParams[firstFieldName] = valueToSearch;

                    // Perform search to show results matching current value
                    this.$nextTick(() => {
                        this.performSearch();
                    });
                }
            }

            // Focus first search field
            this.$nextTick(() => {
                const firstInput = document.querySelector('.ui.modal.active input[type="search"]');
                if (firstInput) firstInput.focus();
            });
        },

        closeModal() {
            this.isModalOpen = false;
            document.body.classList.remove('modal-open');
            this.resetSearch();
        },

        resetSearch() {
            // Clear search parameters
            Object.keys(this.searchParams).forEach(key => {
                this.searchParams[key] = '';
            });
            this.searchResults = [];
            this.searchPerformed = false;
            this.errorMessage = '';
        },

        async performSearch() {
            // Check if any search parameter has value
            const hasSearchTerms = Object.values(this.searchParams).some(value => value.trim() !== '');

            if (!hasSearchTerms) {
                this.searchResults = [];
                this.searchPerformed = false;
                return;
            }

            this.isLoading = true;
            this.errorMessage = '';

            try {
                // Build URL with search parameters
                const url = new URL(this.searchUrl, window.location.origin);
                Object.entries(this.searchParams).forEach(([key, value]) => {
                    if (value.trim() !== '') {
                        url.searchParams.append(key, value);
                    }
                });

                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                this.searchResults = data.results || data || [];
                this.searchPerformed = true;

            } catch (error) {
                console.error('Search error:', error);
                this.errorMessage = 'An error occurred while searching. Please try again.';
                this.searchResults = [];
            } finally {
                this.isLoading = false;
            }
        },

        selectResult(result) {
            this.selectedValue = result[this.valueField];
            this.displayValue = this.formatResultDisplay(result);
            this.rawDisplayValue = result[this.displayField] || '';
            // Store the searchField value for future modal searches
            this.searchValue = result[this.searchField] || result[this.displayField] || '';
            this.closeModal();

            // Trigger change event for form validation and custom handlers
            this.$nextTick(() => {
                const hiddenInput = document.querySelector(`input[name="${this.name}"]`);
                if (hiddenInput) {
                    // Dispatch standard change event
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));

                    // Dispatch custom event with kebab-case name for Alpine.js
                    hiddenInput.dispatchEvent(new CustomEvent('search-component-change', {
                        bubbles: true,
                        detail: {
                            value: this.selectedValue,
                            displayValue: this.displayValue,
                            selectedItem: result,
                            componentName: this.name
                        }
                    }));
                }

                // Dispatch event on the component container for Alpine.js
                this.$el.dispatchEvent(new CustomEvent('search-component-change', {
                    bubbles: true,
                    detail: {
                        value: this.selectedValue,
                        displayValue: this.displayValue,
                        selectedItem: result,
                        componentName: this.name
                    }
                }));

                // Call custom onChange function if provided
                if (this.onChange && typeof window[this.onChange] === 'function') {
                    window[this.onChange]({
                        detail: {
                            value: this.selectedValue,
                            displayValue: this.displayValue,
                            selectedItem: result,
                            componentName: this.name
                        }
                    });
                }
            });
        },

        clearSelection() {
            this.selectedValue = '';
            this.displayValue = '';
            this.rawDisplayValue = '';
            this.searchValue = '';

            // Also clear the search fields and results
            this.resetSearch();

            // Trigger change event when clearing selection
            this.$nextTick(() => {
                const hiddenInput = document.querySelector(`input[name="${this.name}"]`);
                if (hiddenInput) {
                    // Dispatch standard change event
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));

                    // Dispatch custom event with kebab-case name for Alpine.js
                    hiddenInput.dispatchEvent(new CustomEvent('search-component-change', {
                        bubbles: true,
                        detail: {
                            value: '',
                            displayValue: '',
                            selectedItem: null,
                            componentName: this.name,
                            action: 'clear'
                        }
                    }));
                }

                // Dispatch event on the component container for Alpine.js
                this.$el.dispatchEvent(new CustomEvent('search-component-change', {
                    bubbles: true,
                    detail: {
                        value: '',
                        displayValue: '',
                        selectedItem: null,
                        componentName: this.name,
                        action: 'clear'
                    }
                }));

                // Call custom onChange function if provided (for clear action)
                if (this.onChange && typeof window[this.onChange] === 'function') {
                    window[this.onChange]({
                        detail: {
                            value: '',
                            displayValue: '',
                            selectedItem: null,
                            componentName: this.name,
                            action: 'clear'
                        }
                    });
                }
            });
            // Don't close modal - just clear the selection
        }
    };
}
