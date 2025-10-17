@php
    $gridId = uniqid("staticObjectsGrid");

    // Define columns for the static objects datagrid
    $staticColumns = [
        [
            'field' => 'idDocument',
            'title' => 'Document ID',
            'hidden' => true
        ],
        [
            'field' => 'documentName',
            'title' => 'Document',
            'width' => '100%',
            'align' => 'left'
        ]
    ];

    // Static Objects datagrid configuration
    $staticConfig = [
        'showHeader' => false,
        'rownumbers' => false,
        'showFooter' => false,
        'border' => false,
        'singleSelect' => true,
        'emptyMsg' => 'No static objects available',
        'striped' => true,
        'fit' => true
    ];
@endphp

@if(isset($objects) && count($objects) > 0)
    <div class="static-objects-section mb-8">
        <div class="section-header">
            <h1 class="ui header section-title" id="static-objects-so">
                <a href="#static-objects-so">Static Objects</a>
            </h1>
            <button class="ui button basic icon section-toggle"
                    onclick="toggleSection('static-objects-content')"
                    aria-expanded="true">
                <i class="chevron up icon"></i>
            </button>
        </div>
        <div class="section-content" id="static-objects-content">
            <div class="ui card fluid data-card static-objects-card">
                <div class="content">
                    <div class="ui grid" style="margin: 0;">
                        <div class="five wide column" style="padding-right: 0.5rem;">
                            <div class="datagrid-wrapper" style="border: 1px solid rgba(34, 36, 38, 0.15); border-radius: 4px; max-height: 600px; min-height: 300px;">
                                <div
                                    x-data="dataGrid({
                                        data: @js($objects),
                                        columns: @js($staticColumns),
                                        showHeader: false,
                                        rownumbers: false,
                                        showFooter: false,
                                        border: false,
                                        singleSelect: true,
                                        emptyMsg: 'No static objects available',
                                        striped: true,
                                        fit: true,
                                        onRowClick: 'htmx.ajax(\'GET\', `/report/lu/static/object/$\{row.idDocument}/{{ $lu->idLU }}`, \'#objectImageArea\');'
                                    })"
                                    x-init="init()"
                                    class="datagrid-container"
                                    style="overflow: auto; max-height: 598px;"
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
                        <div class="eleven wide column" style="padding-left: 0.5rem;">
                            <div id="objectImageArea" class="static-objects-image-container" style="min-height: 300px;">
                                <div class="ui placeholder segment" style="min-height: 300px; margin: 0; display: flex; align-items: center; justify-content: center; border: none;">
                                    <div class="ui icon header">
                                        <i class="image outline icon"></i>
                                        Select a document to view static objects
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

