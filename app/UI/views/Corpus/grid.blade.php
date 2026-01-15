@php
    use App\Database\Criteria;
    $corpusIcon = view('components.icon.corpus')->render();
    $documentIcon = view('components.icon.document')->render();
    if ($search->document == '') {
        $corpus = Criteria::byFilterLanguage("view_corpus",["name","startswith", $search->corpus])
            ->orderBy("name")->get()->keyBy("idCorpus")->all();
        $ids = array_keys($corpus);
        $documents = Criteria::byFilterLanguage("view_document",["idCorpus","IN", $ids])
            ->orderBy("name")
            ->get()->groupBy("idCorpus")
            ->toArray();
        $data = [];
        foreach($corpus as $c) {
           $children = array_map(fn($item) => [
             'id'=> $item->idDocument,
             'text' => $documentIcon . $item->name,
             'state' => 'closed',
             'type' => 'document',
             'children' => []
            ], $documents[$c->idCorpus] ?? []);
            $data[] = [
                'id' => $c->idCorpus,
                'text' => $corpusIcon . $c->name,
                'state' => 'closed',
                'type' => 'corpus',
                'children' => $children
            ];
        }
    } else {
        $documents = Criteria::byFilterLanguage("view_document",["name","startswith", $search->document])
            ->select('idDocument','name','corpusName')
            ->orderBy("corpusName")->orderBy("name")->all();
        $data = array_map(fn($item) => [
           'id'=> $item->idDocument,
           'text' => $documentIcon . $item->corpusName . ' / ' . $item->name,
           'state' => 'closed',
           'type' => 'document'
        ], $documents);
    }
    $id = "corpusTree";
    debug($data);
@endphp
<div
    class="h-full"
    hx-trigger="reload-gridCorpus from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/corpus/grid"
>
    <div class="relative h-full overflow-auto">
        <div id="corpusTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul class=".corpusTree">
                </ul>
                <script>
                    $(function() {
                        $(".corpusTree").treegrid({
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
                                    htmx.ajax("GET", `/corpus/${row.id}/edit`, "#editArea");
                                }
                                if (row.type === "document") {
                                    htmx.ajax("GET", `/document/${row.id}/edit`, "#editArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
