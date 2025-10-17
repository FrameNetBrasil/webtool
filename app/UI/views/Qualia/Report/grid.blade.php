<div
    class="h-full"
    hx-trigger="reload-gridQualia from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/report/qualia/grid"
>
    <div class="relative h-full overflow-auto">
        <div id="qualiaTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul id="qualiaTree">
                </ul>
                <script>
                    $(function() {
                        $("#qualiaTree").treegrid({
                            url:"/report/qualia/data",
                            queryParams: {
                                qualia: '{{$search->qualia}}'
                            },
                            method:'get',
                            fit: true,
                            showHeader: false,
                            rownumbers: false,
                            idField: "id",
                            treeField: "text",
                            showFooter: false,
                            border: false,
                            columns: [[
                                {
                                    field: "text",
                                    width: "100%",
                                }
                            ]],
                            onClickRow: (row) => {
                                if (row.type === "type") {
                                    $("#qualiaTree").treegrid("toggle", row.id);
                                }
                                if (row.type === "qualia") {
                                    htmx.ajax("GET", `/report/qualia/${row.idQualia}`, "#reportArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
