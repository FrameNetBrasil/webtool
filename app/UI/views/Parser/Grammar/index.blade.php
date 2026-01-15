<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/parser','Parser'],['','Grammar']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-table
                title="Parser Grammar"
                url="/parser/grammar/search"
                emptyMsg="Enter your search term above to find grammars."
                :data="$grammars"
            >
                <x-slot:actions>
                    <x-modal-form
                        id="newGrammar"
                        class="ui secondary button"
                        url="/parser/grammar/new"
                        label="New Grammar"
                        size="medium"
                    />
                </x-slot:actions>

                <x-slot:fields>
                    <div class="field">
                        <div class="ui left icon input w-full">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="name"
                                placeholder="Search Grammar"
                                autocomplete="off"
                            >
                        </div>
                    </div>
                </x-slot:fields>

                <x-slot:table>
                    <table
                        x-data
                        hx-target="body"
                        hx-swap="innerHTML"
                        hx-push-url="true"
                        class="ui selectable striped compact table"
                    >
                        <tbody>
                        @forelse($grammars as $grammar)
                            <tr
                                hx-get="/parser/grammar/{{ $grammar->idGrammarGraph }}"
                                class="cursor-pointer"
                            >
                                <td>
                                    <span class="text-blue-900 font-bold">{{ $grammar->name }}</span>
                                </td>
                                <td>
                                    <span class="ui label tiny">{{ strtoupper($grammar->language) }}</span>
                                </td>
                                <td>
                                    <span class="ui label tiny blue">{{ $grammar->constructionCount }} constructions</span>
                                </td>
                                <td>
                                    <span class="text-gray-600">{{ Str::limit($grammar->description ?? '', 60) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray-500">
                                    No grammar graphs found. Click "New Grammar Graph" to create one.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </x-slot:table>
            </x-ui::browse-table>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
