/**
 * Alpine.js DataGrid Component
 * Replaces jQuery EasyUI DataGrid with modern Alpine.js implementation
 * 
 * Usage:
 * <div x-data="dataGrid(config)" x-init="init()">
 *   <!-- Template content -->
 * </div>
 */

export default function dataGrid(config = {}) {
    return {
        // Configuration properties
        data: config.data || [],
        columns: config.columns || [],
        showHeader: config.showHeader ?? true,
        rownumbers: config.rownumbers ?? false,
        showFooter: config.showFooter ?? false,
        border: config.border ?? true,
        singleSelect: config.singleSelect ?? true,
        emptyMsg: config.emptyMsg || "No records",
        fit: config.fit ?? false,
        striped: config.striped ?? true,

        // State properties
        selectedRows: [],
        selectedIndex: -1,
        hoveredRow: -1,
        isLoading: false,

        // Event callbacks
        onRowClick: config.onRowClick || null,
        onRowSelect: config.onRowSelect || null,
        onRowUnselect: config.onRowUnselect || null,

        // Computed properties
        get hasData() {
            return this.data && this.data.length > 0;
        },

        get visibleColumns() {
            return this.columns.filter(col => !col.hidden);
        },

        get tableClasses() {
            let classes = ['ui', 'table'];
            
            if (this.striped) classes.push('striped');
            if (!this.border) classes.push('basic');
            if (this.fit) classes.push('unstackable');
            
            return classes.join(' ');
        },

        // Initialization
        init() {
            // Validate configuration
            this.validateConfig();
            
            // Convert string callbacks to functions
            this.processCallbacks();
            
            // Set up initial state
            this.selectedRows = [];
            this.selectedIndex = -1;
            
            console.log('DataGrid initialized:', {
                rows: this.data.length,
                columns: this.columns.length,
                singleSelect: this.singleSelect
            });
        },

        // Configuration validation
        validateConfig() {
            if (!Array.isArray(this.data)) {
                console.warn('DataGrid: data should be an array');
                this.data = [];
            }
            
            if (!Array.isArray(this.columns)) {
                console.warn('DataGrid: columns should be an array');
                this.columns = [];
            }
        },

        // Process callback strings into functions
        processCallbacks() {
            // Handle onRowClick callback
            if (typeof this.onRowClick === 'string') {
                try {
                    // Create a function from the string
                    this.onRowClick = new Function('index', 'row', 'event', this.onRowClick);
                } catch (e) {
                    console.error('DataGrid: Invalid onRowClick callback:', e);
                    this.onRowClick = null;
                }
            }
            
            // Handle onRowSelect callback
            if (typeof this.onRowSelect === 'string') {
                try {
                    this.onRowSelect = new Function('index', 'row', this.onRowSelect);
                } catch (e) {
                    console.error('DataGrid: Invalid onRowSelect callback:', e);
                    this.onRowSelect = null;
                }
            }
            
            // Handle onRowUnselect callback
            if (typeof this.onRowUnselect === 'string') {
                try {
                    this.onRowUnselect = new Function('index', 'row', this.onRowUnselect);
                } catch (e) {
                    console.error('DataGrid: Invalid onRowUnselect callback:', e);
                    this.onRowUnselect = null;
                }
            }
        },

        // Row selection methods
        isRowSelected(index) {
            return this.selectedRows.includes(index);
        },

        selectRow(index, row) {
            if (index < 0 || index >= this.data.length) return;

            if (this.singleSelect) {
                // Single selection mode
                const previousIndex = this.selectedIndex;
                this.selectedRows = [index];
                this.selectedIndex = index;

                // Trigger callbacks
                if (previousIndex !== -1 && previousIndex !== index && this.onRowUnselect) {
                    this.onRowUnselect(previousIndex, this.data[previousIndex]);
                }
                
                if (this.onRowSelect) {
                    this.onRowSelect(index, row);
                }
            } else {
                // Multi-selection mode
                if (!this.selectedRows.includes(index)) {
                    this.selectedRows.push(index);
                    if (this.onRowSelect) {
                        this.onRowSelect(index, row);
                    }
                }
            }
        },

        unselectRow(index) {
            const rowIndex = this.selectedRows.indexOf(index);
            if (rowIndex > -1) {
                this.selectedRows.splice(rowIndex, 1);
                
                if (this.selectedIndex === index) {
                    this.selectedIndex = -1;
                }
                
                if (this.onRowUnselect) {
                    this.onRowUnselect(index, this.data[index]);
                }
            }
        },

        clearSelection() {
            this.selectedRows = [];
            this.selectedIndex = -1;
        },

        // Row interaction methods
        handleRowClick(index, row, event) {
            // Select the row
            this.selectRow(index, row);
            
            // Trigger row click callback
            if (this.onRowClick && typeof this.onRowClick === 'function') {
                try {
                    this.onRowClick(index, row, event);
                } catch (e) {
                    console.error('DataGrid: Error executing onRowClick callback:', e);
                }
            }
        },

        handleRowHover(index) {
            this.hoveredRow = index;
        },

        handleRowLeave() {
            this.hoveredRow = -1;
        },

        // Cell value retrieval
        getCellValue(row, column) {
            const value = row[column.field];
            
            // Apply formatter if provided
            if (column.formatter && typeof column.formatter === 'function') {
                return column.formatter(value, row, column);
            }
            
            return value !== undefined && value !== null ? value : '';
        },

        // Row CSS classes
        getRowClasses(index) {
            let classes = [];
            
            if (this.isRowSelected(index)) {
                classes.push('selected');
            }
            
            if (this.hoveredRow === index) {
                classes.push('hover');
            }
            
            return classes.join(' ');
        },

        // Column width calculation
        getColumnWidth(column) {
            if (column.width) {
                if (typeof column.width === 'string') {
                    return column.width;
                }
                return column.width + 'px';
            }
            return 'auto';
        },

        // Public API methods
        getSelectedRow() {
            if (this.selectedIndex >= 0) {
                return this.data[this.selectedIndex];
            }
            return null;
        },

        getSelectedRows() {
            return this.selectedRows.map(index => this.data[index]);
        },

        reload(newData) {
            this.data = newData || [];
            this.clearSelection();
        },

        // Utility methods
        refresh() {
            this.$nextTick(() => {
                console.log('DataGrid refreshed');
            });
        }
    };
}