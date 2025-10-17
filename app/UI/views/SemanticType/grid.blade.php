@php
$id = uniqid("videoTree");
@endphp
<div
    class="h-full"
    hx-trigger="reload-gridSemanticType from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/semanticType/grid"
>
    <div class="relative h-full overflow-auto">
        <div id="semanticTypeTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul id="{{$id}}">
                </ul>
                <script>
                    $(function() {
                        $("#{{$id}}").treegrid({
                            url:"/report/semanticType/data",
                            queryParams: {
                                semanticType: '{{$search->semanticType}}'
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
                                if (row.type === "domain") {
                                    htmx.ajax("GET", `/domain/${row.idDomain}/edit`, "#editArea");
                                }
                                if (row.type === "semanticType") {
                                    htmx.ajax("GET", `/semanticType/${row.idSemanticType}/edit`, "#editArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
