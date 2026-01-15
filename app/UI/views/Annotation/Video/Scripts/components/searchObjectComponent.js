// Search form component for handling input and HTMX events
function searchObjectComponent() {
    return {
        searchQueryFrame: "",
        searchQueryLU: "",
        searchQueryIdDynamicObject: "",
        currentToast: null,
        frameInput: 0,

        onSearchStart(event) {
            // Store the current query for later use
            window.currentSearchQueryFrame = this.searchQueryFrame;
            window.currentSearchQueryLU = this.searchQueryLU;

            // Show Fomantic UI toast
            this.showSearchToast();
        },

        onSearchComplete(event) {
            console.log("Search completed");

            // Hide the search toast
            this.hideSearchToast();
        },

        onResultsUpdated(event) {
            // Re-initialize the grid component on the new content
            const gridArea = document.getElementById("gridArea");
            if (gridArea) {
                Alpine.initTree(gridArea);
            }

            // Update query display in the new content
            this.updateQueryDisplay();
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
            const queryDisplay = document.getElementById("queryDisplay");
            const query = window.currentSearchQuery || this.searchQueryFrame || this.searchQueryLU;
            if (queryDisplay && query && query.trim() !== "") {
                queryDisplay.textContent = `Results for: "${query}"`;
            } else if (queryDisplay) {
                queryDisplay.textContent = "";
            }
        },

        onSeekObject(e) {
            this.frameInput = e.detail.frameNumber;
        },
    };
}
