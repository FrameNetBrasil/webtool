<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/parser','Parser'],['','Construction']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-table
                title="Parser Constructions"
                url="/parser/construction/search"
                emptyMsg="Enter your search term above to find constructions."
                :data="$constructions"
            >
                <x-slot:actions>
                    <x-ui::modal-form
                        id="newConstruction"
                        class="ui secondary button"
                        url="/parser/construction/new"
                        label="New Construction"
                        size="medium"
                    ></x-ui::modal-form>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="four fields">
                        <div class="field">
                            <x-combobox.options
                                id="idGrammarGraph"
                                placeholder="Grammar"
                                :options="$grammars"
                                valueField="idGrammarGraph"
                                displayField="name"
                                value="1"
                            ></x-combobox.options>
                        </div>
                        <div class="field">
                            <div class="ui left icon input">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="name"
                                    placeholder="Search by name"
                                    autocomplete="off"
                                    value=""
                                    autofocus
                                >
                            </div>
                        </div>
                        <div class="field">
                            <x-combobox.options
                                id="constructionType"
                                placeholder="Type"
                                :options="['all'=>'All types','mwe'=>'MWE','phrasal'=>'Phrasal','clausal'=>'Clausal','sentential'=>'Sentential']"
                                value="all"
                            ></x-combobox.options>
                        </div>
                        <div class="field">
                            <x-combobox.options
                                id="enabled"
                                placeholder="Status"
                                :options="['2'=>'All status','1'=>'Enabled','0'=>'Disabled']"
                                value="2"
                            ></x-combobox.options>
                        </div>
                    </div>
                </x-slot:fields>

                <x-slot:table>
                    <table
                        x-data
                        hx-target="body"
                        hx-swap="innerHTML"
                        hx-push-url="true"
                        class="ui selectable striped small compact table"
                    >
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
                                hx-get="/parser/construction/{{ $construction->idConstruction }}"
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
                                        hx-post="/parser/construction/{{ $construction->idConstruction }}/toggle"
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

                </x-slot:table>
            </x-ui::browse-table>

            <div id="editArea" class="ui container" style="margin-top: 2rem;"></div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
