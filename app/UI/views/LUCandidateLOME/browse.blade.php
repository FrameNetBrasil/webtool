<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['','LU Candidate - LOME']]"
        ></x-layout::breadcrumb>
        <main
            class="app-main"
            x-data="{
    sort: '',
    order: '',
    handleSort(col) {
        if (this.sort === col) {
            this.order = this.order === 'asc' ? 'desc' : this.order === 'desc' ? '' : 'asc';
            if (this.order === '') this.sort = '';
        } else {
            this.sort = col;
            this.order = 'asc';
        }
        document.getElementById('sort').value = this.sort;
        document.getElementById('order').value = this.order;
    }
}"
        >
            <x-ui::browse-table
                title="LU Candidate - LOME"
                url="/luCandidateLome/search"
                emptyMsg="Enter your search term above to find LUs."
                :data="$data"
            >
                <x-slot:fields>
                    <input type="hidden" id="sort" name="sort" value="">
                    <input type="hidden" id="order" name="order" value="">
                    <div class="fields">
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
                    </div>
                </x-slot:fields>

                <x-slot:table>
                    <table
                        class="ui selectable striped compact table"
                    >
                        <thead>
                        <tr
                            @click="htmx.trigger('.ui.form', 'submit');"
                        >
                            <th>LU Candidate
                                <i @click="handleSort('name')"
                                   :class="sort === 'name' ? (order === 'asc' ? 'sort up icon' : 'sort down icon') : 'sort icon'"
                                   class="cursor-pointer"></i>
                            </th>
                            <th>Suggested frame
                                <i @click="handleSort('frameName')"
                                   :class="sort === 'frameName' ? (order === 'asc' ? 'sort up icon' : 'sort down icon') : 'sort icon'"
                                   class="cursor-pointer"></i>
                            </th>
                            <th>Created at
                                <i @click="handleSort('createdAt')"
                                   :class="sort === 'createdAt' ? (order === 'asc' ? 'sort up icon' : 'sort down icon') : 'sort icon'"
                                   class="cursor-pointer"></i>
                            </th>
                            <th>Created by
                                <i @click="handleSort('email')"
                                   :class="sort === 'email' ? (order === 'asc' ? 'sort up icon' : 'sort down icon') : 'sort icon'"
                                   class="cursor-pointer"></i>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($data as $lu)
                            <tr
                                @click.prevent="window.location.assign('/luCandidateLome/{{$lu['id']}}')"
                            >
                                <td>
                                    {!! $lu['name'] !!}
                                </td>
                                <td>
                                    {!! $lu['frameName'] !!}
                                </td>
                                <td>
                                    {!! $lu['createdAt'] !!}
                                </td>
                                <td>
                                    {!! $lu['createdBy'] !!}
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
