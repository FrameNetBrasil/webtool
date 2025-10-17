export default function () {
    return {
        searchQuery: "",
        currentToast: null,

        onSearchStart(event) {
            // Store the current query for later use
            window.currentSearchQuery = this.searchQuery;

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
        }
    };
}
