{{--Layout for browse records with search input --}}
{{--Goal: Browse records using a table --}}
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <x-ui::browse-table
                title="Title"
                url="/url/for/search"
                emptyMsg="Enter your search term above to find records."
                :data="$data"
            >
                <x-slot:actions>
                    {{-- Buttons for actions over the entity --}}
                    <a href="/url/for/action"
                       rel="noopener noreferrer"
                       class="ui button secondary">
                        Action
                    </a>
                </x-slot:actions>

                <x-slot:fields>
                    {{-- Input search fields --}}
                    <div class="fields">
                        <div class="field">
                            <div class="ui left icon input w-full">
                                <i class="search icon"></i>
                                <input
                                    type="search"
                                    name="fieldName"
                                    placeholder="Search Entity"
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                    </div>
                </x-slot:fields>

                <x-slot:table>
                    {{--  Table showing records/result --}}
                    <table
                        x-data
                        class="ui selectable striped compact table"
                    >
                        <tbody>
                        @foreach($data as $x)
                            <tr>
                                <td>
                                    <a
                                        href="/url/for/{{$x['id']}}"
                                        hx-boost="true"
                                    >
                                        {!! $x['text'] !!}
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
