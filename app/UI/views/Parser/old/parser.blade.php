<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb :sections="[['/','Home'],['/parser','Parser']]"></x-partial::breadcrumb>

        <main class="app-main">
            <div class="page-content" id="grapherApp">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">Graph-Based Predictive Parser</div>
                        <div class="page-subtitle">Multi-word expression processing with activation-based mechanisms</div>
                    </div>
                </div>

                <div class="grapher-controls">
                    <form class="ui form" id="parserForm">
                        <div class="ui fields">
                            <div class="field" style="flex: 1;">
                                <label for="sentence">Sentence</label>
                                <input
                                    type="text"
                                    id="sentence"
                                    name="sentence"
                                    placeholder="Enter a sentence to parse (e.g., 'Tomei café da manhã cedo')"
                                    required
                                />
                            </div>
                        </div>

                        <div class="ui fields">
                            <div class="field">
                                <label for="idGrammarGraph">Grammar</label>
                                <select id="idGrammarGraph" name="idGrammarGraph" class="ui dropdown" required>
                                    @foreach($grammars as $grammar)
                                        <option value="{{ $grammar->idGrammarGraph }}">
                                            {{ $grammar->name }} ({{ $grammar->language }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label for="queueStrategy">Queue Strategy</label>
                                <select id="queueStrategy" name="queueStrategy" class="ui dropdown">
                                    <option value="fifo">FIFO (First In, First Out)</option>
                                    <option value="lifo">LIFO (Last In, First Out)</option>
                                </select>
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
                                <button
                                    class="ui primary button"
                                    type="button"
                                    hx-post="/parser/parse"
                                    hx-target="#graph"
                                    hx-swap="innerHTML"
                                    hx-indicator="#parseLoader"
                                >
                                    <i class="play icon"></i>
                                    Parse Sentence
                                </button>
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
                                <button
                                    class="ui button"
                                    type="button"
                                    onclick="document.getElementById('sentence').value = ''; document.getElementById('graph').innerHTML = '';"
                                >
                                    <i class="times icon"></i>
                                    Clear
                                </button>
                            </div>

                            <div class="field">
                                <label>&nbsp;</label>
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
                                <label>&nbsp;</label>
                                <div id="parseLoader" class="ui active inline loader htmx-indicator"></div>
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
            </div>
        </main>

        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>

<script>
    // Initialize Fomantic-UI dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        $('.ui.dropdown').dropdown();
    });

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
    });

    // HTMX error handlers
    document.body.addEventListener('htmx:responseError', function(evt) {
        if (evt.detail.target.id === 'graph') {
            evt.detail.target.innerHTML = '<div class="ui negative message"><p>Parse error. Please try again.</p></div>';
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
