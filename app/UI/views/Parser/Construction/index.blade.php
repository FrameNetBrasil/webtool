<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/parser','Parser'],['','Construction']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Parser Constructions
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    <x-ui::modal-form
                        id="newConstruction"
                        class="ui secondary button"
                        url="/parser/construction/new"
                        label="New Construction"
                        size="medium"
                    />
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
                                      hx-post="parser/construction/search"
                                      hx-target=".search-result-section"
                                      hx-swap="innerHTML"
                                      hx-trigger="submit, input delay:500ms"
                                >
                                    <div class="four fields">
                                        <div class="field">
                                            @php
                                                $options = [];
                                                foreach($grammars as $grammar) {
                                                    $options[$grammar->idGrammarGraph] = $grammar->language;
                                                }
                                            @endphp
                                            <x-combobox.options
                                                id="idGrammarGraph"
                                                placehold="Grammar"
                                                label=""
                                                :options="$options"
                                                value=""
                                            ></x-combobox.options>

                                            {{--                                        <select--}}
                                            {{--                                            id="idGrammarGraph"--}}
                                            {{--                                            name="idGrammarGraph"--}}
                                            {{--                                            class="ui dropdown"--}}
                                            {{--                                            hx-post="/parser/construction/search"--}}
                                            {{--                                            hx-trigger="change"--}}
                                            {{--                                            hx-target="#gridConstruction"--}}
                                            {{--                                            hx-swap="innerHTML"--}}
                                            {{--                                            hx-include="[name='name'],[name='constructionType'],[name='enabled']"--}}
                                            {{--                                        >--}}
                                            {{--                                            <option value="">All Grammars</option>--}}
                                            {{--                                            @foreach($grammars as $grammar)--}}
                                            {{--                                                <option value="{{ $grammar->idGrammarGraph }}">{{ $grammar->name }}--}}
                                            {{--                                                    ({{ $grammar->language }})--}}
                                            {{--                                                </option>--}}
                                            {{--                                            @endforeach--}}
                                            {{--                                        </select>--}}
                                        </div>
                                        <div class="field">
                                            <x-search-field
                                                id="name"
                                                placeholder="Search by name"
                                                hx-post="/parser/construction/search"
                                                hx-trigger="input changed delay:500ms, search"
                                                hx-target="#gridConstruction"
                                                hx-swap="innerHTML"
                                                hx-include="[name='idGrammarGraph'],[name='constructionType'],[name='enabled']"
                                            ></x-search-field>
                                        </div>
                                        <div class="field">
                                            <select
                                                id="constructionType"
                                                name="constructionType"
                                                class="ui dropdown"
                                                hx-post="/parser/construction/search"
                                                hx-trigger="change"
                                                hx-target="#gridConstruction"
                                                hx-swap="innerHTML"
                                                hx-include="[name='idGrammarGraph'],[name='name'],[name='enabled']"
                                            >
                                                <option value="">All Types</option>
                                                <option value="mwe">MWE</option>
                                                <option value="phrasal">Phrasal</option>
                                                <option value="clausal">Clausal</option>
                                                <option value="sentential">Sentential</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <select
                                                id="enabled"
                                                name="enabled"
                                                class="ui dropdown"
                                                hx-post="/parser/construction/search"
                                                hx-trigger="change"
                                                hx-target="#gridConstruction"
                                                hx-swap="innerHTML"
                                                hx-include="[name='idGrammarGraph'],[name='name'],[name='constructionType']"
                                            >
                                                <option value="">All Status</option>
                                                <option value="1">Enabled</option>
                                                <option value="0">Disabled</option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="search-result-section">
                            @fragment("search")
                                <div class="search-result-data">
                                    <table
                                        class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>CE Labels</th>
                                            <th>Pattern</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @fragment('search')
                                            @forelse($constructions as $construction)
                                                <tr
                                                    hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/edit"
                                                    hx-target="#editArea"
                                                    hx-swap="innerHTML"
                                                    class="cursor-pointer"
                                                >
                                                    <td>
                                                        <span
                                                            class="text-blue-900 font-bold">{{ $construction->name }}</span>
                                                    </td>
                                                    <td>
                                                        @if($construction->constructionType === 'mwe')
                                                            <span class="ui label tiny blue">MWE</span>
                                                        @elseif($construction->constructionType === 'phrasal')
                                                            <span class="ui label tiny green">Phrasal</span>
                                                        @elseif($construction->constructionType === 'clausal')
                                                            <span class="ui label tiny orange">Clausal</span>
                                                        @elseif($construction->constructionType === 'sentential')
                                                            <span class="ui label tiny red">Sentential</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="ui label tiny">{{ $construction->priority }}</span>
                                                    </td>
                                                    <td>
                                                        <button
                                                            class="ui mini button {{ $construction->enabled ? 'green' : 'grey' }}"
                                                            hx-post="/parser/v4/construction/{{ $construction->idConstruction }}/toggle"
                                                            hx-swap="none"
                                                            onclick="event.stopPropagation()"
                                                        >
                                                            {{ $construction->enabled ? 'Enabled' : 'Disabled' }}
                                                        </button>
                                                    </td>
                                                    <td>
                                                        @if($construction->phrasalCE)
                                                            <span
                                                                class="ui label tiny teal">P: {{ $construction->phrasalCE }}</span>
                                                        @endif
                                                        @if($construction->clausalCE)
                                                            <span
                                                                class="ui label tiny violet">C: {{ $construction->clausalCE }}</span>
                                                        @endif
                                                        @if($construction->sententialCE)
                                                            <span
                                                                class="ui label tiny pink">S: {{ $construction->sententialCE }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <code
                                                            class="text-xs">{{ Str::limit($construction->pattern, 50) }}</code>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-gray-500">
                                                        No constructions found. Click "New Construction" to create one.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        @endfragment
                                        </tbody>
                                    </table>

                                </div>
                            @endfragment
                        </div>
                    </div>
                </div>
            </div>

            {{--            <x-ui::browse-table--}}
            {{--                title="Parser Gonstruction"--}}
            {{--                url="/parser/construction/search"--}}
            {{--                emptyMsg="Enter your search term above to find constructions."--}}
            {{--                :data="$grammars"--}}
            {{--            >--}}
            {{--                <x-slot:actions>--}}
            {{--                    <x-modal-form--}}
            {{--                        id="newConstruction"--}}
            {{--                        class="ui secondary button"--}}
            {{--                        url="/parser/construction/new"--}}
            {{--                        label="New Gonstruction"--}}
            {{--                        size="medium"--}}
            {{--                    />--}}
            {{--                </x-slot:actions>--}}

            {{--                <x-slot:fields>--}}
            {{--                    <div class="field">--}}
            {{--                        <div class="ui left icon input w-full">--}}
            {{--                            <i class="search icon"></i>--}}
            {{--                            <input--}}
            {{--                                type="search"--}}
            {{--                                name="name"--}}
            {{--                                placeholder="Search Construction"--}}
            {{--                                autocomplete="off"--}}
            {{--                            >--}}
            {{--                        </div>--}}
            {{--                    </div>--}}
            {{--                </x-slot:fields>--}}

            {{--                <x-slot:table>--}}
            {{--                    <table--}}
            {{--                        x-data--}}
            {{--                        hx-target="body"--}}
            {{--                        hx-swap="innerHTML"--}}
            {{--                        hx-push-url="true"--}}
            {{--                        class="ui selectable striped compact table"--}}
            {{--                    >--}}
            {{--                        <tbody>--}}
            {{--                        @forelse($grammars as $grammar)--}}
            {{--                            <tr--}}
            {{--                                hx-get="/parser/grammar/{{ $grammar->idGrammarGraph }}"--}}
            {{--                                class="cursor-pointer"--}}
            {{--                            >--}}
            {{--                                <td>--}}
            {{--                                    <span class="text-blue-900 font-bold">{{ $grammar->name }}</span>--}}
            {{--                                </td>--}}
            {{--                                <td>--}}
            {{--                                    <span class="ui label tiny">{{ strtoupper($grammar->language) }}</span>--}}
            {{--                                </td>--}}
            {{--                                <td>--}}
            {{--                                    <span class="ui label tiny blue">{{ $grammar->constructionCount }} constructions</span>--}}
            {{--                                </td>--}}
            {{--                                <td>--}}
            {{--                                    <span class="text-gray-600">{{ Str::limit($grammar->description ?? '', 60) }}</span>--}}
            {{--                                </td>--}}
            {{--                            </tr>--}}
            {{--                        @empty--}}
            {{--                            <tr>--}}
            {{--                                <td colspan="4" class="text-center text-gray-500">--}}
            {{--                                    No grammar graphs found. Click "New Grammar Graph" to create one.--}}
            {{--                                </td>--}}
            {{--                            </tr>--}}
            {{--                        @endforelse--}}
            {{--                        </tbody>--}}
            {{--                    </table>--}}
            {{--                </x-slot:table>--}}
            {{--            </x-ui::browse-table>--}}
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
