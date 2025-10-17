@php
    $id = uniqid("cxnGrid");
@endphp
<div
    class="h-full"
>
    <div class="relative h-full overflow-auto">
        <div id="cxnTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul id="{{$id}}">
                </ul>
                <script>
                    $(function() {
                        $("#{{$id}}").treegrid({
                            url:"/report/cxn/data",
                            queryParams: {
                                cxn: '{{$search->cxn}}'
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
                                if (row.type === "construction") {
                                    htmx.ajax("GET", `/report/cxn/${row.id}/view`, "#reportArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
