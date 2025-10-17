/**
 * Alpine.js Context Menu Component
 * Provides a reusable right-click context menu for graph nodes
 *
 * Usage:
 * <div x-data="contextMenu()" @click.outside="close()">
 */

export default function contextMenuComponent() {
    return {
        // State
        visible: false,
        positioned: false,
        x: 0,
        y: 0,
        nodeData: null,
        isMouseOver: false,

        /**
         * Show context menu at specific position (for hover)
         */
        showAtPosition(x, y, nodeData) {
            this.nodeData = nodeData;
            this.x = x;
            this.y = y;
            this.positioned = false;

            // Set visible to true but keep it hidden with opacity during calculation
            this.visible = true;

            // Adjust position if menu would go off screen
            this.$nextTick(() => {
                const menu = this.$el.querySelector('.context-menu');
                if (menu) {
                    const rect = menu.getBoundingClientRect();
                    const viewportWidth = window.innerWidth;
                    const viewportHeight = window.innerHeight;

                    // Adjust horizontal position
                    if (this.x + rect.width > viewportWidth) {
                        this.x = viewportWidth - rect.width - 10;
                    }

                    // Adjust vertical position
                    if (this.y + rect.height > viewportHeight) {
                        this.y = viewportHeight - rect.height - 10;
                    }

                    // Mark as positioned to trigger opacity transition
                    this.positioned = true;
                }
            });
        },

        /**
         * Hide context menu
         */
        hide() {
            this.visible = false;
            this.positioned = false;
            this.nodeData = null;
        },

        /**
         * Track mouse enter on menu
         */
        handleMouseEnter() {
            this.isMouseOver = true;
        },

        /**
         * Track mouse leave from menu
         */
        handleMouseLeave() {
            this.isMouseOver = false;
            // Hide menu when mouse leaves
            this.hide();
        },

        /**
         * Handle menu item click
         */
        handleAction(action) {
            if (!this.nodeData) return;

            // Dispatch custom event with action and node data
            this.$dispatch('context-menu-action', {
                action: action,
                nodeId: this.nodeData.id,
                nodeData: this.nodeData
            });

            this.hide();
        },

        /**
         * Handle ESC key to close menu
         */
        handleKeydown(event) {
            if (event.key === 'Escape' && this.visible) {
                this.hide();
            }
        }
    };
}
