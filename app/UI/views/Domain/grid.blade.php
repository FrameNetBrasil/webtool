<div
    class="wt-datagrid flex flex-column"
    style="height:100%"
    hx-trigger="reload-gridDomain from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/domain/grid"
>
    <div class="datagrid-header-search flex">
        <div style="padding:4px 0px 4px 4px">
            <x-search-field
                id="domain"
                placeholder="Search Domain"
                hx-post="/domain/grid/search"
                hx-trigger="input changed delay:500ms, search"
                hx-target="#gridDomain"
                hx-swap="innerHTML"
            ></x-search-field>
        </div>
    </div>
    <div class="table" style="position:relative;height:100%">
        <table id="gridDomain">
            <tbody
            >
            @fragment('search')
                @foreach($domains as $domain)
                    <tr
                        hx-target="#editArea"
                        hx-swap="innerHTML"
                    >
                        <td
                            hx-get="/domain/{{$domain->idDomain}}/edit"
                            class="cursor-pointer"
                            style="min-width:120px"
                        >
                            <span>{{$domain->name}}</span>
                        </td>
                        <td
                            hx-get="/domain/{{$domain->idDomain}}/edit"
                            class="cursor-pointer"
                            style="min-width:120px"
                        >
                            <span>{{$domain->description}}</span>
                        </td>
                    </tr>
                @endforeach
            @endfragment
            </tbody>
        </table>
    </div>
</div>
