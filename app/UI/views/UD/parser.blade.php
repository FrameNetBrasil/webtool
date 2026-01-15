<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/annotation','Annotation'],['','UD Parser']]"></x-partial::breadcrumb>
        <main class="app-main">
            <div class="page-content" id="grapherApp">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            UD Parser
                        </div>
                    </div>
                </div>
                <div class="grapher-controls">
                    <form class="ui form">
                        <div class="ui fields w-full">
                            <div class="field w-1/3">
                                <label for="sentence">Sentence</label>
                                <div class="ui medium input">
                                    <input
                                        type="text"
                                        id="sentence"
                                        name="sentence"
                                        placeholder="Enter a sentence to parse"
                                        required
                                    />
                                </div>
                            </div>
                            <div class="field">
                                <label></label>
                                <button
                                    class="ui primary button"
                                    hx-post="/ud/parser"
                                    hx-target="#graph"
                                    hx-swap="innerHTML"
                                    hx-include="[name='sentence']"
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
                                    hx-post="/grapher/frame/graph/0"
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
                {{--                @include('Grapher.report')--}}
                @include('UD.contextMenu')
                {{--                @include('Grapher.Frame.feRelationsModal')--}}
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>

<script>
    // Execute scripts after HTMX swaps content into #graph
    document.body.addEventListener("htmx:afterSwap", function(evt) {
        if (evt.detail.target && evt.detail.target.id === "graph") {
            // Find and execute any script tags in the swapped content
            const scripts = evt.detail.target.querySelectorAll("script");
            scripts.forEach(script => {
                const newScript = document.createElement("script");
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
        if (evt.detail.target && evt.detail.target.id === "feRelationsGraph") {
            const scripts = evt.detail.target.querySelectorAll("script");
            scripts.forEach(script => {
                const newScript = document.createElement("script");
                if (script.src) {
                    newScript.src = script.src;
                } else {
                    newScript.textContent = script.textContent;
                }
                script.parentNode.replaceChild(newScript, script);
            });

            // Show modal after content loads and scripts execute
            setTimeout(() => {
                $("#grapherFERelationsModal").modal("show");
            }, 100);
        }
    });

    // Wire context menu actions to grapher component
    document.addEventListener("grapher-context-action", function(evt) {
        const grapherApp = document.getElementById("grapherApp");
        if (grapherApp && Alpine && Alpine.$data) {
            const grapher = Alpine.$data(grapherApp);
            if (grapher && grapher.handleContextMenuAction) {
                grapher.handleContextMenuAction(evt.detail.action, evt.detail.nodeId);
            }
        }
    });
</script>
