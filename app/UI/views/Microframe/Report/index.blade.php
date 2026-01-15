<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/report','Report'],['','Microframe']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="page-content slide">
                <div
                    class="ui container page"
                    x-init="$('.item').tab()"
                >
                    <div class="ui tab h-full" data-tab="browse">
                        <x-ui::browse-table
                            title="Microframe Report"
                            url="/report/microframe/search"
                            emptyMsg="Enter your search term above to find microframes."
                            :data="$data"
                        >
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
                                                <div
                                                    hx-get="/report/microframe/{{$frame['id']}}"
                                                    hx-target=".report"
                                                    hx-on::before-request="$.tab('change tab','report')"
                                                >
                                                    {!! $frame['text'] !!}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </x-slot:table>
                        </x-ui::browse-table>

                    </div>
                    <div class="ui tab report h-full" data-tab="report">
                    </div>
                </div>
            </div>

        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
