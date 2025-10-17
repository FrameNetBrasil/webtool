@php
    use App\Database\Criteria;
    $limit = 300;
    $idLanguage = \App\Services\AppService::getCurrentIdLanguage();
    $data = [];
    if ($search->document != '') {
        if (strlen(trim($search->document)) > 2) {
            if ($search->sentence == '') {
                $search->sentence = '%';
            }
            $sentences = Criteria::table("view_document_sentence as ds")
                ->join("view_sentence as s","ds.idSentence","=","s.idSentence")
                ->join("view_document as d","ds.idDocument","=","d.idDocument")
                ->where("d.idLanguage",$idLanguage)
                ->where("s.text","contains",$search->sentence)
                ->where("d.name","startswith",$search->document)
                ->select("d.idDocument","d.name","s.idSentence","s.text")
                ->limit($limit)
                ->orderBy("d.name")->get()->groupBy(["idDocument","name"])->toArray();
            foreach($sentences as $idDocument => $name) {
               $nameDoc = array_key_first($name);
               $children = array_map(fn($item) => [
                 'id'=> $item->idSentence,
                 'text' => $item->text,
                 'state' => 'closed',
                 'type' => 'sentence',
                 'children' => []
                ], $name[$nameDoc] ?? []);
                $data[] = [
                    'id' => $idDocument,
                    'text' => $nameDoc,
                    'state' => 'closed',
                    'type' => 'document',
                    'children' => $children
                ];
            }
        }
    } else {
        if ($search->sentence) {
        $sentences = Criteria::byFilter("sentence", [
                ["text", "contains", $search->sentence]
            ])->select("idSentence", "text")
            ->limit($limit)
            ->orderBy("idSentence")->all();
        foreach($sentences as $sentence) {
            $data[] = [
                'id' => $sentence->idSentence,
                'text' => $sentence->text,
                'state' => 'closed',
                'type' => 'sentence',
                'children' => []
            ];
        }
        }
    }
    if (empty($data)) {
         $data[] = [
            'id' => 0,
            'text' => "No results",
            'state' => 'closed',
            'type' => 'result',
        ];
    }
@endphp
<div
        class="h-full"
        hx-trigger="reload-gridSentence from:body"
        hx-target="this"
        hx-swap="innerHTML"
        hx-post="/sentence/grid"
>
    <div class="relative h-full overflow-auto">
        <div id="sentenceTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            @fragment('search')
                <ul id="sentenceTree">
                </ul>
                <script>
                    $(function() {
                        $("#sentenceTree").treegrid({
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
                                if (row.type === "document") {
                                    $("#sentenceTree").treegrid("toggle", row.id);
                                }
                                if (row.type === "sentence") {
                                    htmx.ajax("GET", `/sentence/${row.id}`, "#editArea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
