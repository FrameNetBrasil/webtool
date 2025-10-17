<div
    class="h-full"
    hx-trigger="reload-gridC5 from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/report/c5/grid"
>
    <div class="relative h-full overflow-auto">
        <div class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul class="c5Tree">
                </ul>
                <script>
                    $(function() {
                        $(".c5Tree").treegrid({
                            {{--data: {{Js::from($data)}},--}}
                            url:"/report/c5/data",
                            queryParams: {
                                concept: '{{$search->concept}}'
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
                                if (row.type === "concept") {
                                    htmx.ajax("GET", `/report/c5/content/${row.idConcept}`, "#reportArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
