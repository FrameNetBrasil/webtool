@php
    $id = uniqid("imageTree");
@endphp
<div
    class="h-full"
    hx-trigger="reload-gridImage from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/image/grid"
>
    <div class="relative h-full overflow-auto">
        <div id="imageTreeWrapper" class="ui table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul id="{{$id}}">
                </ul>
                <script>
                    $(function() {
                        let imageIcon = `{!! view('components.icon.image')->render() !!} `;
                        let datasetIcon = `{!! view('components.icon.dataset')->render() !!} `;
                        $("#{{$id}}").treegrid({
                            {{--//data: {{Js::from($data)}},--}}
                            url:"/image/data",
                            queryParams: {
                                image: "{{$search->image}}",
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
                                        return (row.type === 'dataset' ? datasetIcon : imageIcon) + value + (row.type === 'image' ? row.dataset : '');
                                    }
                                }
                            ]],
                            onClickRow: (row) => {
                                if (row.type === "image") {
                                    htmx.ajax("GET", `/image/${row.idImage}/edit`, "#editArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
