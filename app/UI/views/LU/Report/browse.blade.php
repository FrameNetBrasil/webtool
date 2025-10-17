<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/report','Report'],['','LU']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
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
                                    <a
                                        href="/report/lu/{{$lu['id']}}"
                                        hx-boost="true"
                                    >
                                        {!! $lu['text'] !!}
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
