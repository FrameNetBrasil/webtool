<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['','Sentence']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-table
                title="Sentence Structure"
                url="/sentence/search"
                emptyMsg="Enter your search above to find sentences."
                :data="$data"
            >
                <x-slot:actions>
                    <a href="/sentence/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Sentence
                    </a>
                </x-slot:actions>
                <x-slot:fields>
                    <div class="two fields">
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="sentence"
                                    placeholder="Search Sentence"
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="idSentence"
                                    placeholder="Search by Id"
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
                        @foreach($data as $sentence)
                            <tr>
                                <td>
                                    <a
                                        href="/sentence/{{$sentence['id']}}"
                                        hx-boost="true"
                                    >
                                        {!! $sentence['id'] !!}
                                    </a>
                                </td>
                                <td>
                                    <a
                                        href="/sentence/{{$sentence['id']}}"
                                        hx-boost="true"
                                    >
                                        {!! $sentence['text'] !!}
                                    </a>
                                </td>
                                <td>
                                    {!! $sentence['documentName'] !!}
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
