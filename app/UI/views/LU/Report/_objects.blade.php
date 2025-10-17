@php
    $id = uniqid("luDynamicObjectTree");
@endphp
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
            singleSelect: true,
            emptyMsg: "No records",
            columns: [[
                {
                    field: "idDynamicObject",
                    width: "100%"
                }
            ]],
            onClickRow: (index, row) => {
                htmx.ajax("GET", `/report/lu/dynamic/object/${row.idDynamicObject}`, "#objectImageAreaScript");
            }
        });

    });
</script>

