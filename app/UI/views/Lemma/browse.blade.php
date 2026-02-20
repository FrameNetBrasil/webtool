@use("\App\Enums\SearchOptions")
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['','Lemmas']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-table
                title="Lemmas"
                url="/lemma/search"
                emptyMsg="Enter your search term above to find Lemmas."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/lemma/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Lemma
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="fields">
                        <div class="three wide field">
                            <x-combobox.search-options
                                id="searchOption"
                                :value="SearchOptions::STARTSWITH"
                            ></x-combobox.search-options>
                        </div>
                        <div class="thirteen wide field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="lemma"
                                    placeholder="Search Lemma"
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                    </div>
                </x-slot:fields>

                <x-slot:table>
                    <table
                        x-data
                        class="ui selectable striped compact table"
                    >
                        <tbody>
                        @foreach($data as $lemma)
                            <tr>
                                <td>
                                    <a
                                        href="/lemma/{{$lemma['id']}}"
                                        hx-boost="true"
                                    >
                                        {!! $lemma['text'] !!}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </x-slot:table>
            </x-ui::browse-table>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
