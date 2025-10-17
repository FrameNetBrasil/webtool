<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['','LU AI Suggestion']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container page-browse">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            LU by AI Suggestion
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <div class="search-container">
                        <div class="search-input-section"
                             x-data="searchFormComponent()"
                             @htmx:before-request="onSearchStart"
                             @htmx:after-request="onSearchComplete"
                             @htmx:after-swap="onResultsUpdated"
                        >
                            <div class="search-input-group">
                                <form class="ui form"
                                      hx-post="/lu/aiSuggestion"
                                      hx-target=".search-result-section"
                                      hx-swap="innerHTML"
                                      hx-trigger="submit"
                                >
                                    <div class="fields">
                                        <div class="six wide field">
                                            <x-search::frame
                                                id="idFrame"
                                                label=""
                                                placeholder="Select a Frame"
                                                search-url="/frame/list/forSelect"
                                                value="{{ old('idFrame', '') }}"
                                                display-value="{{ old('frame', '') }}"
                                                modal-title="Search Frame"
                                            ></x-search::frame>
                                        </div>
                                        <div class="field">
                                            <label>Model</label>
                                            <div
                                                class="ui selection dropdown"
                                                x-init="$($el).dropdown()"
                                            >
                                                <input type="hidden" name="model" value="llama">
                                                <i class="dropdown icon"></i>
                                                <div class="default text">LLama</div>
                                                <div class="menu">
                                                    <div class="item" data-value="llama">LLama</div>
                                                    <div class="item" data-value="openai">OpenAI</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label>POS</label>
                                            <select
                                                name="pos[]"
                                                class="ui fluid search dropdown"
                                                multiple=""
                                                x-init="$($el).dropdown({keepSearchTerm: true})"
                                            >
                                                <option value="NOUN">NOUN</option>
                                                <option value="VERB">VERB</option>
                                                <option value="ADJ">ADJ</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label></label>
                                            <button
                                                class="ui primary button">
                                                Get suggestions
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="search-result-section">
                            @fragment("search")
                                @if(count($data) > 0)
                                    <div class="search-result-data">
                                        <table
                                            class="ui selectable striped compact table"
                                        >
                                            <thead>
                                            <tr
                                            >
                                                <th>Suggested lemma
                                                </th>
                                                <th>POS
                                                </th>
                                                <th>Gloss
                                                </th>
                                                <th>LU
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($data['items'] as $item)
                                                @php
                                                $confidence = $item['confidence'] ?? 0;
                                                $confidenceColor = $confidence > 0.8 ? 'green' : ($confidence > 0.6 ? 'yellow' : 'red');

                                                $tableData = [
                                                    $item['lemma'] ?? '',
                                                    $item['pos'] ?? '',
                                                    $item['gloss_pt'] ?? '',
                                                    $item['idLU'] ?? null
//                                                    substr($item['gloss_pt'] ?? '', 0, 50) . (strlen($item['gloss_pt'] ?? '') > 50 ? '...' : ''),
//                                                    number_format($confidence, 2),
//                                                    substr($item['rationale_short'] ?? '', 0, 60) . (strlen($item['rationale_short'] ?? '') > 60 ? '...' : ''),
                                                ];


                                                @endphp
                                                <tr>
                                                    <td>
                                                        {{$tableData[0]}}
                                                    </td>
                                                    <td>
                                                        {{$tableData[1]}}
                                                    </td>
                                                    <td>
                                                        {{$tableData[2]}}
                                                    </td>
                                                    <td>
                                                        @if(is_null($tableData[3]))
                                                            <button
                                                                type="button"
                                                                class="ui button"
                                                            >Create LU</button>
                                                        @else
                                                            <label class="ui red label">LU already exists</label>
                                                        @endif
                                                    </td>
                                                </tr>

                                            @endforeach
                                            </tbody>
                                        </table>

                                    </div>
                                @else
                                    <div class="search-result-empty" id="emptyState">
                                        <i class="search icon empty-icon"></i>
                                        <h3 class="empty-title">No results found.</h3>
                                        <p class="empty-description">
                                            Enter a frame to get LU suggestions.
                                        </p>
                                    </div>
                                @endif
                            @endfragment
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
