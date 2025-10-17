{{--
    Visual Units Section - Frame Report Partial
    Shows documents and images related to the frame for visual annotation

    Parameters:
    - $frame: Frame object
    - $vus: Visual units data (array of documents with images)
--}}

@php
    $gridId = uniqid("visualUnitsGrid");

    // Define columns for the visual units datagrid
    $vuColumns = [
        [
            'field' => 'idDocument',
            'title' => 'Document ID',
            'hidden' => true
        ],
        [
            'field' => 'corpusName',
            'title' => 'Corpus',
            'width' => '50%',
            'align' => 'left'
        ],
        [
            'field' => 'documentName',
            'title' => 'Document',
            'width' => '35%',
            'align' => 'left'
        ],
        [
            'field' => 'idImage',
            'title' => 'Image',
            'width' => '15%',
            'align' => 'center'
        ]
    ];

    // Visual Units datagrid configuration
    $vuConfig = [
        'showHeader' => false,
        'rownumbers' => false,
        'showFooter' => false,
        'border' => false,
        'singleSelect' => true,
        'emptyMsg' => 'No visual units available',
        'striped' => true,
        'fit' => true
    ];
@endphp

@if(isset($vus) && count($vus) > 0)
    <div class="visual-units-section mb-8">
        <div class="section-header">
            <h2 class="ui header section-title" id="visual-units-vu">
                <a href="#visual-units-vu">Visual Units</a>
            </h2>
            <button class="ui button basic icon section-toggle"
                    onclick="toggleSection('visual-units-content')"
                    aria-expanded="true">
                <i class="chevron up icon"></i>
            </button>
        </div>
        <div class="section-content" id="visual-units-content">
            <div class="ui card fluid data-card visual-units-card">
                <div class="content">
                    <div class="ui grid" style="margin: 0;">
                        <div class="five wide column" style="padding-right: 0.5rem;">
                            <div class="datagrid-wrapper" style="border: 1px solid rgba(34, 36, 38, 0.15); border-radius: 4px; max-height: 600px; min-height: 300px;">
                                <div
                                    x-data="dataGrid({
                                        data: @js($vus),
                                        columns: @js($vuColumns),
                                        showHeader: false,
                                        rownumbers: false,
                                        showFooter: false,
                                        border: false,
                                        singleSelect: true,
                                        emptyMsg: 'No visual units available',
                                        striped: true,
                                        fit: true,
                                        onRowClick: 'htmx.ajax(\'GET\', `/report/frame/static/object/$\{row.idDocument}/$\{row.idImage}/{{ $frame->idFrame }}`, \'#visualUnitsImageArea\');'
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
                            <div id="visualUnitsImageArea" class="visual-units-image-container" style="min-height: 300px;">
                                <div class="ui placeholder segment" style="min-height: 300px; margin: 0; display: flex; align-items: center; justify-content: center; border: none;">
                                    <div class="ui icon header">
                                        <i class="image outline icon"></i>
                                        Select a document to view visual units
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
