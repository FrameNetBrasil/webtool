@php
    use App\Database\Criteria;
    $videoIcon = view('components.icon.video')->render();
    $videos = Criteria::byFilter("video",["title","startswith", $search->video])
            ->orderBy("title")->get()->keyBy("idVideo")->all();
    $data = [];
    foreach($videos as $video) {
        $data[] = [
            'id' => $video->idVideo,
            'text' => $videoIcon . $video->title,
            'state' => 'closed',
            'type' => 'video',
            'children' => null
        ];
    }
    $id = uniqid("videoTree");
@endphp
<div
    id="videoGrid"
    class="h-full"
    hx-trigger="reload-gridVideo from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/video/grid"
>
    <div class="relative h-full overflow-auto">
        <div id="videoTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul id="{{$id}}">
                </ul>
                <script>
                    $(function() {
                        $("#{{$id}}").datagrid({
                            data: {{Js::from($data)}},
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
                            onClickRow: (index, row) => {
                                if (row.type === "video") {
                                    htmx.ajax("GET", `/video/${row.id}/edit`, "#editArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
