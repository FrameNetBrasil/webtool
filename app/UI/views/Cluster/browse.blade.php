<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['','Cluster']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-table
                title="Cluster Structure"
                url="/cluster/search"
                emptyMsg="Enter your search term above to find Clusters."
                :data="$data"
            >   <x-slot:actions>
                    <a href="/cluster/new"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        New Cluster
                    </a>
                </x-slot:actions>

                <x-slot:fields>
                    <div class="field">
                        <div class="ui left icon input w-full">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="Cluster"
                                placeholder="Search Cluster"
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
                                        href="/cluster/{{$frame['id']}}"
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
