<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb :sections="[['/','Home'],['/parser','Parser'],['/parser/grammar','Grammars']]"></x-partial::breadcrumb>

        <main class="app-main">
            <div class="ui container overflow-y-auto">
            <div class="page-content">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">{{ $grammar->name }}</div>
                        <div class="page-subtitle">Language: {{ $grammar->language }}</div>
                    </div>
                </div>

                @if($grammar->description)
                <div class="ui segment">
                    <p>{{ $grammar->description }}</p>
                </div>
                @endif

                <div class="ui statistics">
                    <div class="statistic">
                        <div class="value">{{ count($grammar->nodes ?? []) }}</div>
                        <div class="label">Nodes</div>
                    </div>
                    <div class="statistic">
                        <div class="value">{{ count($grammar->edges ?? []) }}</div>
                        <div class="label">Edges</div>
                    </div>
                    <div class="statistic">
                        <div class="value">{{ count($grammar->mwes ?? []) }}</div>
                        <div class="label">MWEs</div>
                    </div>
                </div>

                <div class="ui divider"></div>

                <div class="grapher-controls">
                    <div class="ui form">
                        <div class="ui fields">
                            <div class="field">
                                <label>Filter by word:</label>
                                <input
                                    type="text"
                                    id="grammarFilter"
                                    placeholder="Enter word to filter nodes..."
                                    value="{{ request()->get('filter', '') }}"
                                />
                            </div>
                            <div class="field">
                                <button
                                    class="ui primary button"
                                    onclick="loadGraphVisualization()"
                                    type="button"
                                >
                                    <i class="project diagram icon"></i>
                                    Show Graph Visualization
                                </button>
                            </div>
                            <div class="field">
                                <button
                                    class="ui button"
                                    onclick="document.getElementById('grammarFilter').value = ''; document.getElementById('graph').innerHTML = ''; document.getElementById('filteredTables').innerHTML = '';"
                                >
                                    <i class="times icon"></i>
                                    Clear
                                </button>
                            </div>
                            <div class="field">
                                <button
                                    class="ui button"
                                    hx-get="/parser/grammar/{{ $grammar->idGrammarGraph }}/tables"
                                    hx-target="#filteredTables"
                                    hx-swap="innerHTML"
                                    hx-include="#grammarFilter"
                                    hx-vals='js:{"filter": document.getElementById("grammarFilter").value}'
                                >
                                    <i class="list icon"></i>
                                    Show Tables
                                </button>
                            </div>
                            <div class="field">
                                <button
                                    class="ui button"
                                    onclick="$('#grapherOptionsModal').modal('show');"
                                    type="button"
                                >
                                    <i class="list icon"></i>
                                    Grapher options
                                </button>
                            </div>
                            <div class="field">
                                <a href="/parser" class="ui button">
                                    <i class="arrow left icon"></i>
                                    Back to Parser
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <div id="filteredTables"></div>
                </div>

                @if(count($errors) > 0)
                <div class="ui divider"></div>
                <div class="ui warning message">
                    <div class="header">Grammar Validation Warnings</div>
                    <ul class="list">
                        @foreach($errors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            <!-- Graph Visualization Modal -->
            <div class="ui large modal" id="graphVisualizationModal">
                <i class="close icon"></i>
                <div class="header">
                    <span id="modalHeaderText">Grammar Graph Visualization</span>
                    <span class="ui label" id="modalFilterBadge" style="display: none;">
                        <i class="filter icon"></i>
                        Filtered by: "<span id="modalFilterLabel"></span>"
                    </span>
                </div>
                <div class="scrolling content">
                    <div id="graphModalContent"></div>
                    <div id="grapherAppModal" x-data="grapher({})" style="display: none;">
                        <div id="graphModal" class="wt-layout-grapher" style="min-height: 400px;"></div>
                    </div>
                </div>
                <div class="actions">
                    <div class="ui cancel button">Close</div>
                </div>
            </div>

            @include('Grapher.controls')
            </div>
        </main>

        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>

<script>
    // Load graph visualization via HTMX and show modal
    function loadGraphVisualization() {
        const filterValue = document.getElementById('grammarFilter').value;

        // Update modal filter label if filter is applied
        const filterBadge = document.getElementById('modalFilterBadge');
        const filterLabel = document.getElementById('modalFilterLabel');

        if (filterValue && filterBadge && filterLabel) {
            filterLabel.textContent = filterValue;
            filterBadge.style.display = 'inline-block';
        } else if (filterBadge) {
            filterBadge.style.display = 'none';
        }

        // Show modal immediately
        $('#graphVisualizationModal').modal('show');

        // Load graph data via HTMX into modal content area
        htmx.ajax('GET', '/parser/grammar/{{ $grammar->idGrammarGraph }}/visualization', {
            target: '#graphModalContent',
            swap: 'innerHTML',
            values: { filter: filterValue }
        });
    }

    // Execute scripts after HTMX swaps content into modal
    document.body.addEventListener("htmx:afterSwap", function(evt) {
        if (evt.detail.target && evt.detail.target.id === "graphModalContent") {
            // Find and execute any script tags in the swapped content
            const scripts = evt.detail.target.querySelectorAll("script");
            scripts.forEach(script => {
                const newScript = document.createElement("script");
                if (script.src) {
                    newScript.src = script.src;
                } else {
                    // Modify script to target modal's grapherApp instead of main page
                    let scriptContent = script.textContent;
                    scriptContent = scriptContent.replace(/getElementById\('grapherApp'\)/g, "getElementById('grapherAppModal')");
                    scriptContent = scriptContent.replace(/getElementById\('graph'\)/g, "getElementById('graphModal')");
                    newScript.textContent = scriptContent;
                }
                // Replace the old script with a new one to trigger execution
                script.parentNode.replaceChild(newScript, script);
            });

            // Show the graph container
            const grapherAppModal = document.getElementById('grapherAppModal');
            if (grapherAppModal) {
                grapherAppModal.style.display = 'block';
            }
        }
    });

    // Clean up modal content when closed
    $('#graphVisualizationModal').modal({
        onHidden: function() {
            document.getElementById('graphModalContent').innerHTML = '';
            const grapherAppModal = document.getElementById('grapherAppModal');
            if (grapherAppModal) {
                grapherAppModal.style.display = 'none';
            }
        }
    });
</script>

<style>
    .mt-6 {
        margin-top: 1.5rem;
    }

    /* Modal styling for graph visualization */
    #graphVisualizationModal {
        width: 90vw !important;
        max-width: 90vw !important;
    }

    #graphVisualizationModal .scrolling.content {
        height: 80vh !important;
        max-height: 80vh !important;
    }

    #graphVisualizationModal #graphModal {
        height: 100%;
        min-height: 500px;
        border: 1px solid #ddd;
        background: #ffffff;
    }

    #graphVisualizationModal #grapherAppModal {
        min-height: 500px;
    }

    /* Ensure JointJS tooltips appear above modal */
    .joint-tools {
        z-index: 1001 !important;
    }
</style>
