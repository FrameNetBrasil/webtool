<div
    style="height:300px"
    hx-trigger="reload-gridMultimodal from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/report/multimodal/grid"
>
    <div class="relative h-full overflow-auto">
        <div id="multimodalTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul id="multimodalTree">
                </ul>
                <script>
                    $(function() {
                        $("#multimodalTree").treegrid({
                            url:"/report/multimodal/data",
                            queryParams: {
                                corpus: '{{$search->corpus}}',
                                document: '{{$search->document}}'
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
                                if (row.type === "document") {
                                    htmx.ajax("GET", `/report/multimodal/${row.idDocument}`, "#reportArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
