<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/report','Report'],['','Frame']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <x-ui::browse-table
                title="Frame Report"
                url="/report/frame/search"
                emptyMsg="Enter your search term above to find frames."
                :data="$data"
            >
                <x-slot:fields>
                    <div class="field">
                        <div class="ui left icon input w-full">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="frame"
                                placeholder="Search Frame"
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
                                        href="/report/frame/{{$frame['id']}}"
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
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>
