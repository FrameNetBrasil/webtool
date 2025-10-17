export default function () {
    return {
        currentToast: null,

        onSearchStart(event) {
            this.showSearchToast();
        },

        onSearchComplete(event) {
            this.hideSearchToast();
        },

        onResultsUpdated(event) {
            const gridArea = document.getElementById("gridArea");
            if (gridArea) {
                // First, find and reset any existing tree components
                const treeContainers = gridArea.querySelectorAll('[x-data*="treeComponent"]');
                treeContainers.forEach(container => {
                    // Get the Alpine component instance and call reload if available
                    const alpineData = Alpine.$data(container);
                    if (alpineData && typeof alpineData.reload === 'function') {
                        alpineData.reload();
                    }
                });
                
                // Then reinitialize Alpine for any new components
                Alpine.initTree(gridArea);
            }
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

    };
}
