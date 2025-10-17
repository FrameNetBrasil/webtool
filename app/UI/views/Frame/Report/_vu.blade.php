@php
    $id = uniqid("luStaticTree");
    $idFrame = $frame->idFrame;
@endphp
<div class="grid w-full h-full" style="min-height:500px">
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
                                    data: {!! Js::from($vus) !!},
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
                                            field: "corpusName",
                                            width: "50%",
                                        },
                                        {
                                            field: "documentName",
                                            width: "35%",
                                        },
                                        {
                                            field: "idImage",
                                            width: "15%",
                                        }
                                    ]],
                                    onClickRow: (index,row) => {
                                        htmx.ajax("GET", `/report/frame/static/object/${row.idDocument}/${row.idImage}/{{$idFrame}}`, "#objectImageArea");
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

