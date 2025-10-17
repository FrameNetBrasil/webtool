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
@endphp
<div
        class="wt-datagrid flex flex-column h-full"
        hx-trigger="reload-gridVideo from:body"
        hx-target="this"
        hx-swap="outerHTML"
        hx-get="/video/grid"
>
    <div class="datagrid-header-search flex">
        <div style="padding:4px 0px 4px 4px">
            <x-search-field
                    id="video"
                    placeholder="Search Video"
                    hx-post="/video/grid/search"
                    hx-trigger="input changed delay:500ms, search"
                    hx-target="#videoTreeWrapper"
                    hx-swap="innerHTML"
            ></x-search-field>
        </div>
    </div>
    <div id="videoTreeWrapper">
        @fragment('search')
            <ul id="videoTree" class="wt-treegrid">
            </ul>
            <script>
                $(function() {
                    $("#videoTree").tree({
                        data: {{Js::from($data)}},
                        onClick: function(node) {
                            if (node.type === "video") {
                                htmx.ajax("GET", `/video/${node.id}/edit`, "#editArea");
                            }
                        }
                    });
                });
            </script>
        @endfragment
    </div>
</div>
