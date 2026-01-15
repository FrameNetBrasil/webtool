<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['','Microframe']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-table
                title="Microframe Structure"
                url="/microframe/search"
                emptyMsg="Enter your search term above to find microframes."
                :data="$data"
            >   <x-slot:actions>
                    <a href="/microframe/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Microframe
                    </a>
                </x-slot:actions>

                <x-slot:fields>
                    <div class="field">
                        <div class="ui left icon input w-full">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="microframe"
                                placeholder="Search Microframe"
                                autocomplete="off"
                            >
                        </div>
                    </div>
                </x-slot:fields>

                <x-slot:table>
                    <table
                        x-data
                        class="ui selectable striped compact table"
                    >
                        <tbody>
                        @foreach($data as $frame)
                            <tr>
                                <td>
                                    <a
                                        href="/microframe/{{$frame['id']}}"
                                        hx-boost="true"
                                    >
                                        {!! $frame['text'] !!}
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
