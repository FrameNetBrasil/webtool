@php
@endphp
<div
    class="h-full"
    hx-trigger="reload-gridLUCandidate from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/luCandidate/grid"
>
    <div class="relative h-full overflow-auto">
        <div id="luTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul id="luTree">
                </ul>
                <script>
                    $(function() {
                        $("#luTree").datagrid({
                            url:"/luCandidate/data",
                            method:"get",
                            fit: true,
                            singleSelect:true,
                            showHeader: true,
                            rownumbers: false,
                            idField: "id",
                            treeField: "text",
                            showFooter: false,
                            border: false,
                            columns: [[
                                {
                                    field: "name",
                                    width: "30%",
                                    title:"LU candidate",
                                    sortable: true,
                                    hstyler: () => {
                                        return "font-weight:bold;";
                                    },
                                },
                                {
                                    field: "frameName",
                                    width: "35%",
                                    title:"Suggested frame",
                                    sortable: true,
                                    hstyler: () => {
                                        return "font-weight:bold;";
                                    },
                                },
                                {
                                    field: "origin",
                                    width: "15%",
                                    title: "Origin",
                                    sortable: true,
                                    hstyler: () => {
                                        return "font-weight:bold;";
                                    },
                                },
                                {
                                    field: "createdAt",
                                    width: "20%",
                                    title: "Created At",
                                    sortable: true,
                                    hstyler: () => {
                                        return "font-weight:bold;";
                                    },
                                },
                            ]],
                            onClickRow: (index, row) => {
                                if (row.type === "lu") {
                                    htmx.ajax("GET", `/luCandidate/${row.id}/edit`, "#editArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
