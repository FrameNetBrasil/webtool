@php
    $gridId = uniqid("dynamicDocumentsGrid");
    $idVideo = uniqid("videoContainer");

    // Define columns for the dynamic documents datagrid
    $dynamicColumns = [
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

    // Dynamic Documents datagrid configuration
    $dynamicConfig = [
        'showHeader' => false,
        'rownumbers' => false,
        'showFooter' => false,
        'border' => false,
        'singleSelect' => true,
        'emptyMsg' => 'No documents available',
        'striped' => true,
        'fit' => true
    ];
@endphp

@if(isset($documents) && count($documents) > 0)
    <div class="dynamic-objects-section mb-8">
        <div class="section-header">
            <h1 class="ui header section-title" id="dynamic-objects-do">
                <a href="#dynamic-objects-do">Dynamic Objects</a>
            </h1>
            <button class="ui button basic icon section-toggle"
                    onclick="toggleSection('dynamic-objects-content')"
                    aria-expanded="true">
                <i class="chevron up icon"></i>
            </button>
        </div>
        <div class="section-content" id="dynamic-objects-content">
            <div class="ui card fluid data-card dynamic-objects-card">
                <div class="content">
                    <div class="ui grid" style="margin: 0;">
                        <div class="five wide column" style="padding-right: 0.5rem;">
                            <div class="dynamic-documents-section" style="margin-bottom: 1rem;">
                                <h4 class="ui header" style="margin-bottom: 0.5rem;">Documents</h4>
                                <div class="datagrid-wrapper" style="border: 1px solid rgba(34, 36, 38, 0.15); border-radius: 4px; max-height: 200px; min-height: 150px;">
                                    <script>
                                        window.dynamicDocsData = @json($documents);
                                        window.dynamicDocsCols = @json($dynamicColumns);
                                        window.dynamicDocsClick = function(index, row) {
                                            htmx.ajax('GET', `/report/lu/{{ $lu->idLU }}/dynamic/objects/${row.idDocument}`, '#dynamicObjects');
                                        };
                                    </script>
                                    <div
                                        x-data="dataGrid({
                                            data: window.dynamicDocsData || [],
                                            columns: window.dynamicDocsCols || [],
                                            showHeader: false,
                                            rownumbers: false,
                                            showFooter: false,
                                            border: false,
                                            singleSelect: true,
                                            emptyMsg: 'No documents available',
                                            striped: true,
                                            fit: true,
                                            onRowClick: window.dynamicDocsClick
                                        })"
                                        x-init="init()"
                                        class="datagrid-container"
                                        style="overflow: auto; max-height: 198px;"
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
                            <div class="dynamic-objects-list" style="border: 1px solid rgba(34, 36, 38, 0.15); border-radius: 4px; min-height: 300px; max-height: 400px; overflow-y: auto;">
                                <div id="dynamicObjects" style="padding: 1rem;">
                                    <div class="ui placeholder segment" style="margin: 0; border: none; box-shadow: none;">
                                        <div class="ui icon header">
                                            <i class="list icon"></i>
                                            Select a document to view objects
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="eleven wide column" style="padding-left: 0.5rem;">
                            <div class="dynamic-video-container" style="min-height: 600px; border: 1px solid rgba(34, 36, 38, 0.15); border-radius: 4px; background-color: #fafafa; padding: 1rem;">
                                <div x-data="imageComponent()" class="video-player-container">
                                    <div class="video-wrapper" style="position: relative; width: 415px; height: 245px;">
                                        <video :id="idVideo"
                                               preload="metadata"
                                               crossorigin="anonymous"
                                               x-init="init()"
                                               @loadedmetadata="onLoadedMetadata()"
                                               @timeupdate="onTimeUpdate()"
                                               @play="onPlay()"
                                               @pause="onPause()"
                                               style="width: 100%; height: 100%;">
                                        </video>

                                        <div id="boxesContainer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                                        </div>
                                    </div>

                                    <!-- Control bar -->
                                    <div class="video-controls" style="margin-top: 0.5rem;">
                                        <div class="ui grid" style="margin: 0;">
                                            <div class="four wide column">
                                                <div class="ui label">
                                                    <span x-text="frame.current"></span> [<span x-text="formatTime(time.current)"></span>s]
                                                </div>
                                            </div>
                                            <div class="eight wide column center aligned">
                                                <div class="ui buttons">
                                                    <button class="ui button" @click="gotoPreviousFrame()" title="Previous frame">
                                                        <i class="step backward icon"></i>
                                                    </button>
                                                    <button class="ui button" @click="togglePlay()" title="Play/Pause">
                                                        <i :class="isPlaying ? 'pause icon' : 'play icon'"></i>
                                                    </button>
                                                    <button class="ui button" @click="gotoNextFrame()" title="Next frame">
                                                        <i class="step forward icon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="four wide column right aligned">
                                                <div class="ui label">
                                                    <span x-text="frame.last"></span> [<span x-text="formatTime(duration)"></span>s]
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="objectImageAreaScript">
                                    </div>
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

<script>
@include("Annotation.Dynamic.Scripts.components.imageComponent")
</script>
