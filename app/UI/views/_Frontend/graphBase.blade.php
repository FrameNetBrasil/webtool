{{--Base page for graph visualization with grapher component --}}
{{--Goal: Page for select data for graph visualization --}}
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb :sections="[['/','Home']]"></x-partial::breadcrumb>
        <main class="app-main">
            <div class="page-content" id="grapherApp">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Title Grapher
                        </div>
                    </div>
                </div>
                <div class="grapher-controls">
                    <form>
                        <div class="ui fields">
                            <div class="field">
                            </div>
                            <div class="field">
                            </div>
                            <div class="field">
                                <label></label>
                                <button
                                    class="ui primary button"
                                    hx-post="/url/for/base/graph"
                                    hx-target="#graph"
                                    hx-swap="innerHTML"
                                >
                                    <i class="project diagram icon"></i>
                                    Show Graph Visualization
                                </button>
                            </div>
                            <div class="field">
                                <label></label>
                                <button
                                    class="ui button"
                                    hx-target="#graph"
                                    hx-post="/url/for/base/graph/0"
                                >
                                    <i class="times icon"></i>
                                    Clear
                                </button>
                            </div>
                            <div class="field">
                                <label></label>
                                <button
                                    class="ui button"
                                    onclick="$('#grapherOptionsModal').modal('show');"
                                    type="button"
                                >
                                    <i class="list icon"></i>
                                    Grapher options
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="grapher-canvas">
                    <div id="graph" class="wt-layout-grapher"></div>
                </div>
                @include('Grapher.controls')
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>

<script>
    // Execute scripts after HTMX swaps content into #graph
    document.body.addEventListener('htmx:afterSwap', function (evt) {
        if (evt.detail.target && evt.detail.target.id === 'graph') {
            // Find and execute any script tags in the swapped content
            const scripts = evt.detail.target.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                if (script.src) {
                    newScript.src = script.src;
                } else {
                    newScript.textContent = script.textContent;
                }
                // Replace the old script with a new one to trigger execution
                script.parentNode.replaceChild(newScript, script);
            });
        }

        // Handle modal graph swaps
        if (evt.detail.target && evt.detail.target.id === 'feRelationsGraph') {
            const scripts = evt.detail.target.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                if (script.src) {
                    newScript.src = script.src;
                } else {
                    newScript.textContent = script.textContent;
                }
                script.parentNode.replaceChild(newScript, script);
            });

            // Show modal after content loads and scripts execute
            setTimeout(() => {
                $('#grapherFERelationsModal').modal('show');
            }, 100);
        }
    });

    // Wire context menu actions to grapher component
    document.addEventListener('grapher-context-action', function (evt) {
        const grapherApp = document.getElementById('grapherApp');
        if (grapherApp && Alpine && Alpine.$data) {
            const grapher = Alpine.$data(grapherApp);
            if (grapher && grapher.handleContextMenuAction) {
                grapher.handleContextMenuAction(evt.detail.action, evt.detail.nodeId);
            }
        }
    });
</script>
