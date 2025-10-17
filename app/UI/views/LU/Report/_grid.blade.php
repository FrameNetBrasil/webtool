@php
    $id = uniqid("luGrid");
@endphp
<div
    class="wt-box h-full"
>
    @fragment('search')
        <ul id="{{$id}}">
        </ul>
        <script>
            $(function() {
                let luIcon = `{!! view('components.icon.lu')->render() !!} `;
                $("#{{$id}}").datagrid({
                    url: "/report/lu/data",
                    queryParams: {
                        lu: '{{$search->lu}}'
                    },
                    method: "get",
                    fit: true,
                    showHeader: false,
                    rownumbers: false,
                    showFooter: false,
                    singleSelect: true,
                    border: false,
                    columns: [[
                        {
                            field: "name",
                            width: "100%",
                            formatter: function(value, row, index) {
                                return luIcon + value + " [" + row.frameName + "]";
                            }
                        }
                    ]],
                    onClickRow: (index, row) => {
                        htmx.ajax("GET", `/report/lu/content/${row.idLU}`, "#reportArea");
                    }
                });
            });
        </script>
    @endfragment
</div>
