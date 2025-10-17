@php
    $gridId = uniqid("dynamicObjectsGrid");
    
    // Define columns for the dynamic objects datagrid
    $objectsColumns = [
        [
            'field' => 'idDynamicObject',
            'title' => 'Object ID',
            'width' => '100%',
            'align' => 'left'
        ]
    ];
    
    // Dynamic Objects datagrid configuration
    $objectsConfig = [
        'showHeader' => false,
        'rownumbers' => false,
        'showFooter' => false,
        'border' => false,
        'singleSelect' => true,
        'emptyMsg' => 'No dynamic objects available',
        'striped' => true,
        'fit' => true
    ];
@endphp

@if(isset($objects) && count($objects) > 0)
    <div class="dynamic-objects-list-section mb-4">
        <div class="section-header">
            <h3 class="ui header section-title" id="dynamic-objects-list">
                <a href="#dynamic-objects-list">Dynamic Objects</a>
            </h3>
            <button class="ui button basic icon section-toggle" 
                    onclick="toggleSection('dynamic-objects-list-content')" 
                    aria-expanded="true">
                <i class="chevron up icon"></i>
            </button>
        </div>
        <div class="section-content" id="dynamic-objects-list-content">
            <div class="ui card fluid data-card dynamic-objects-list-card">
                <div class="content">
                    <div class="datagrid-wrapper" style="border: 1px solid rgba(34, 36, 38, 0.15); border-radius: 4px; max-height: 400px; min-height: 200px;">
                        <script>
                            window.dynamicObjectsData = @json($objects);
                            window.dynamicObjectsCols = @json($objectsColumns);
                            window.dynamicObjectsClick = function(index, row) {
                                htmx.ajax('GET', `/report/lu/dynamic/object/${row.idDynamicObject}`, '#objectImageAreaScript');
                            };
                        </script>
                        <div
                            x-data="dataGrid({
                                data: window.dynamicObjectsData || [],
                                columns: window.dynamicObjectsCols || [],
                                showHeader: false,
                                rownumbers: false,
                                showFooter: false,
                                border: false,
                                singleSelect: true,
                                emptyMsg: 'No dynamic objects available',
                                striped: true,
                                fit: true,
                                onRowClick: window.dynamicObjectsClick
                            })"
                            x-init="init()"
                            class="datagrid-container"
                            style="overflow: auto; max-height: 398px;"
                        >
                            <div x-show="!isLoading">
                                <table :class="tableClasses">
                                    <tbody>
                                        <template x-for="(row, index) in data" :key="index">
                                            <tr 
                                                :class="getRowClasses(index)"
                                                @click="handleRowClick(index, row, $event)"
                                                @mouseenter="handleRowHover(index)"
                                                @mouseleave="handleRowLeave()"
                                                style="cursor: pointer;"
                                            >
                                                <template x-for="column in visibleColumns" :key="column.field">
                                                    <td 
                                                        :class="column.align ? column.align + ' aligned' : ''"
                                                        :style="'width: ' + getColumnWidth(column)"
                                                        x-html="getCellValue(row, column)"
                                                    ></td>
                                                </template>
                                            </tr>
                                        </template>
                                        
                                        <tr x-show="!hasData">
                                            <td :colspan="visibleColumns.length" class="center aligned">
                                                <div class="ui message">
                                                    <span x-text="emptyMsg"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="ui placeholder segment">
        <div class="ui icon header">
            <i class="list icon"></i>
            No dynamic objects available
        </div>
    </div>
@endif

