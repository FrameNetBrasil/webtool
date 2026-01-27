<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb :sections="[['/','Home'],['/grapher','Grapher'],['','Frame']]"></x-partial::breadcrumb>
        <main class="app-main">
            <div class="page-content" id="grapherApp">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Frame Grapher
                        </div>
                    </div>
                </div>
                <div class="grapher-controls">
                    <form>
                        <div class="ui fields">
                            <div class="field w-15em">
                                <x-search::frame
                                    name="idFrame"
                                    placeholder="Select a frame"
                                    value=""
                                    display-value=""
                                    modal-title="Search Frame"
                                />
                            </div>
                            {{--                            <x-combobox.frame--}}
                            {{--                                id="idFrame"--}}
                            {{--                                label=""--}}
                            {{--                                placeholder="Frame (min: 3 chars)"--}}
                            {{--                                :hasDescription="false"--}}
                            {{--                                style="width:250px"--}}
                            {{--                            ></x-combobox.frame>--}}
                            <div class="field">
                                <x-checkbox.relation
                                    id="frameRelation"
                                    label="Relations to show"
                                    :relations="$relations"
                                ></x-checkbox.relation>
                            </div>
                            <div class="field">
                                <button
                                    class="ui primary button"
                                    hx-post="/grapher/frame/graph"
                                    hx-target="#graph"
                                    hx-swap="innerHTML"
                                >
                                    <i class="project diagram icon"></i>
                                    Show Graph Visualization
                                </button>
                            </div>
                            <div class="field">
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
                @include('Grapher.report')
                @include('Grapher.contextMenu')
                @include('Grapher.Frame.feRelationsModal')
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>

<script>
    // Function to clean up stray Fomantic-UI dimmers
    function cleanupStrayDimmers() {
        // Remove any dimmers that are direct children of body (not controlled by Alpine.js)
        const strayDimmers = document.querySelectorAll('body > .ui.dimmer.modals.page');
        strayDimmers.forEach(dimmer => {
            // Only remove dimmers that don't have Alpine.js x-show attribute
            if (!dimmer.hasAttribute('x-show')) {
                console.log('Removing stray dimmer from body');
                dimmer.remove();
            }
        });
    }

    // Run cleanup immediately (in case dimmers already exist)
    cleanupStrayDimmers();

    // Clean up on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', cleanupStrayDimmers);

    // Clean up after Alpine initializes
    document.addEventListener('alpine:initialized', cleanupStrayDimmers);

    // Watch for new dimmers being added and clean them up
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 &&
                    node.classList &&
                    node.classList.contains('dimmer') &&
                    node.classList.contains('modals') &&
                    !node.hasAttribute('x-show')) {
                    console.log('Detected and removing stray dimmer');
                    node.remove();
                }
            });
        });
    });
    observer.observe(document.body, { childList: true });

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
