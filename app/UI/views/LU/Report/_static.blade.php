@php
    $id = uniqid("luStaticTree");
@endphp
<div class="grid w-full h-full">
    <div class="col-4">
        <div
            class="h-full"
        >
            <div class="relative h-full overflow-auto">
                <div id="luStaticTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
                    @fragment('search')
                        <ul id="{{$id}}">
                        </ul>
                        <script>
                            $(function() {
                                $("#{{$id}}").datagrid({
                                    data: {!! Js::from($objects) !!},
                                    fit: true,
                                    showHeader: false,
                                    rownumbers: false,
                                    showFooter: false,
                                    border: false,
                                    singleSelect:true,
                                    emptyMsg:"No records",
                                    columns: [[
                                        {
                                            field: "idDocument",
                                            hidden: true
                                        },
                                        {
                                            field: "documentName",
                                            width: "100%",
                                        }
                                        // {
                                        //     field: "idStaticObject",
                                        //     width: "15%",
                                        // }
                                    ]],
                                    onClickRow: (index,row) => {
                                        htmx.ajax("GET", `/report/lu/static/object/${row.idDocument}/{{$lu->idLU}}`, "#objectImageArea");
                                    }
                                });
                            });
                        </script>
                    @endfragment
                </div>
            </div>
        </div>
    </div>
    <div class="col-8">
        <div
            id="objectImageArea"
            class="h-full"
        >
        </div>
    </div>
</div>

<script>
    let dom = null;
</script>

