<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb :sections="[['/','Home'],['/daisy','Daisy Parser']]"></x-partial::breadcrumb>
        <main class="app-main">
            <div class="page-content" id="daisyApp">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Daisy Semantic Parser
                        </div>
                        <div class="page-subtitle">
                            Frame semantic disambiguation using GRID algorithm
                        </div>
                    </div>
                </div>

                <div class="daisy-controls">
                    <form class="ui form">
                        <div class="five fields">
                            <!-- Sentence Input -->
                            <div class="field">
                                <label for="sentence">
                                    Sentence
                                </label>
                                <textarea
                                    id="sentence"
                                    name="sentence"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Enter a sentence to analyze..."
                                    required
                                ></textarea>
                            </div>

                            <!-- Parameters -->
                            <div class="field">
                                <!-- Language -->
                                <label for="idLanguage">
                                    Language
                                </label>
                                <select
                                    id="idLanguage"
                                    name="idLanguage"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                >
                                    <option value="1" {{ $defaultLanguage == 1 ? 'selected' : '' }}>Portuguese
                                    </option>
                                    <option value="2" {{ $defaultLanguage == 2 ? 'selected' : '' }}>English</option>
                                </select>
                            </div>

                            <!-- Search Type -->
                            <div class="field">
                                <label for="searchType">
                                    Search Type
                                </label>
                                <select
                                    id="searchType"
                                    name="searchType"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                >
                                    @foreach($searchTypes as $value => $label)
                                        <option
                                            value="{{ $value }}" {{ $defaultSearchType == $value ? 'selected' : '' }}>
                                            {{ $value }}: {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Level -->
                            <div class="field">
                                <label for="level">
                                    Relation Depth
                                </label>
                                <select
                                    id="level"
                                    name="level"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                >
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ $defaultLevel == $i ? 'selected' : '' }}>
                                            Level {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <!-- GregNet Mode -->
                            <div class="field">
                                <label></label>
                                <input
                                    type="checkbox"
                                    id="gregnetMode"
                                    name="gregnetMode"
                                    value="1"
                                    class="mr-2"
                                >
                                <span class="text-sm">GregNet Mode</span>
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="flex flex-row gap-2">
                            <x-button
                                id="btnParse"
                                label="Parse & Show Results"
                                hx-target="#results"
                                hx-post="/daisy/parse"
                            ></x-button>
                            <x-button
                                id="btnGraph"
                                label="Parse & Show Graph"
                                color="secondary"
                                hx-target="#graph"
                                hx-post="/daisy/graph"
                            ></x-button>
                            <x-button
                                id="btnClear"
                                label="Clear"
                                color="secondary"
                                type="button"
                                onclick="document.getElementById('sentence').value=''; document.getElementById('results').innerHTML=''; document.getElementById('graph').innerHTML='';"
                            ></x-button>
                        </div>

                    </form>
                </div>

                <!-- Results Area -->
                <div class="mt-6">
                    <div id="results" class="daisy-results"></div>
                </div>

                <!-- Graph Visualization Area -->
                <div class="mt-6">
                    <div id="graph" class="wt-layout-grapher" style="min-height: 500px;"></div>
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>

<script>
    // Execute scripts after HTMX swaps content
    document.body.addEventListener('htmx:afterSwap', function (evt) {
        if (evt.detail.target && (evt.detail.target.id === 'graph' || evt.detail.target.id === 'results')) {
            // Find and execute any script tags in the swapped content
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
        }
    });
</script>
