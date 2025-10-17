@php
    use App\Database\Criteria;use App\Services\AnnotationService;
    // get projects for documents that has videos
    $listProjects = Criteria::table("view_document_video as v")
        ->join("view_project_docs as p","v.idDocument","=","p.idDocument")
        ->where("p.idLanguage",\App\Services\AppService::getCurrentIdLanguage())
        ->where("p.projectName","<>","Default Project")
        ->select("p.projectName")
        ->chunkResult("projectName","projectName");
    $data = AnnotationService::browseCorpusDocumentBySearch($search, $listProjects);
    $id = uniqid("corpusTree");
@endphp
<div
    class="h-full"
>
    <div class="relative h-full overflow-auto">
        <div id="corpusTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul id="{{$id}}">
                </ul>
                <script>
                    $(function() {
                        $("#{{$id}}").treegrid({
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
                            onClickRow: (row) => {
                                if (row.type === "corpus") {
                                    $("#corpusTree").treegrid("toggle", row.id);
                                }
                                if (row.type === "document") {
                                    window.location = `/annotation/dynamicMode/${row.id}`;
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
