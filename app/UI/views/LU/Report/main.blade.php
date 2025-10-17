<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/report','Report'],['','LU']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="page-content">
                <div
                    class="ui container h-full lus-tabs-context"
                    x-init="$.tab({
                        evaluateScripts:true,
                        context: '.lus-tabs-context',
                        childrenOnly: true,
                    })"
                >
                    <div class="ui tab h-full" data-tab="browse">
                        <x-ui::browse-table
                            title="LU Report"
                            url="/report/lu/search"
                            emptyMsg="Enter your search term above to find LUs."
                            :data="$data"
                        >
                            <x-slot:fields>
                                <div class="field">
                                    <div class="ui left icon input w-full">
                                        <i class="search icon"></i>
                                        <input
                                            type="search"
                                            name="lu"
                                            placeholder="Search LU"
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
                                    @foreach($data as $lu)
                                        <tr>
                                            <td>
                                                <div
                                                    hx-get="/report/lu/{{$lu['id']}}"
                                                    hx-target=".report"
                                                    hx-on::before-request="$.tab('change tab','report')"
                                                >
                                                    {!! $lu['text'] !!}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </x-slot:table>
                        </x-ui::browse-table>

                    </div>
                    <div class="ui tab report" data-tab="report">
                    </div>
                </div>
            </div>

        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>
