@php
    $id = uniqid("projectTree");
@endphp
<div
        class="h-full"
        hx-trigger="reload-gridProject from:body"
        hx-target="this"
        hx-swap="outerHTML"
        hx-get="/project/grid"
>
    <div class="relative h-full overflow-auto">
        <div id="projectTreeWrapper" class="ui table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul id="{{$id}}">
                </ul>
                <script>
                    $(function() {
                        let projectIcon = `{!! view('components.icon.project')->render() !!} `;
                        let datasetIcon = `{!! view('components.icon.dataset')->render() !!} `;
                        $("#{{$id}}").treegrid({
                            {{--//data: {{Js::from($data)}},--}}
                            url:"/project/data",
                            queryParams: {
                                project: "{{$search->project}}",
                                dataset: "{{$search->dataset}}"
                            },
                            method:'get',
                            fit: true,
                            showHeader: false,
                            showFooter: false,
                            rownumbers: false,
                            idField: "id",
                            treeField: "name",
                            border: false,
                            columns: [[
                                {
                                    field: "name",
                                    width: "100%",
                                    formatter: function(value,row,index){
                                        return (row.type === 'project' ? projectIcon : datasetIcon) + value + (row.type === 'dataset' ? row.project : '');
                                    }
                                }
                            ]],
                            onClickRow: (row) => {
                                console.log(row);
                                if (row.type === "project") {
                                    htmx.ajax("GET", `/project/${row.idProject}/edit`, "#editArea");
                                }
                                if (row.type === "dataset") {
                                    htmx.ajax("GET", `/dataset/${row.idDataset}/edit`, "#editArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
