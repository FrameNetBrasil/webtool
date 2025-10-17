@php
    use App\Database\Criteria;
    $groupIcon = view('components.icon.layergroup')->render();
    $layerIcon = view('components.icon.layertype')->render();
    $labelIcon = view('components.icon.genericlabel')->render();
    $limit = 300;
    $idLanguage = \App\Services\AppService::getCurrentIdLanguage();
    $data = [];
    $layergroups = Criteria::table("layergroup")
        ->select("layergroup.idLayerGroup","layergroup.name","layergroup.type")
        ->orderBy("name")
        ->all();
    if ($search->genericlabel == '') {
        if ($search->layer == '') {
            $search->layer = '--none';
        }
        $data = [];
        foreach($layergroups as $layergroup) {
            $lt = [];
            $layers = Criteria::table("view_layertype")
                ->select("idLayerType","name")
                ->where("idLayerGroup",$layergroup->idLayerGroup)
                ->where("idLanguage",$idLanguage)
                ->all();
            foreach($layers as $layer) {
                $gl = [];
                $gls = Criteria::table("genericlabel")
                    ->select("idGenericLabel","name")
                    ->where("idLayerType",$layer->idLayerType)
                    ->where("idLanguage",$idLanguage)
                    ->all();
                foreach($gls as $g) {
                     $gl[] = [
                        'id' => 'l'. $g->idGenericLabel,
                        'text' => $labelIcon . $g->name,
                        'state' => 'open',
                        'type' => 'genericlabel',
                    ];
                }
                $lt[] = [
                    'id' => 't'. $layer->idLayerType,
                    'text' => $layerIcon . $layer->name,
                    'state' => 'closed',
                    'type' => 'layertype',
                    'children' => $gl
                ];
            }
            $data[] = [
                    'id' => 'g'. $layergroup->idLayerGroup,
                    'text' => $groupIcon . $layergroup->name . ' [' . $layergroup->type . ']',
                    'state' => 'closed',
                    'type' => 'layergroup',
                    'children' => $lt
            ];
        }
    } else {
        $genericlabels = Criteria::table("genericlabel as gl")
            ->join("view_layerType lt","lt.idLayterType","=","gl.idLayerType")
            ->select('idGenericLabel', 'name', "lt.name as layerName")
            ->where("gl.name", "startswith", $search->genericlabel)
            ->where('gl.idLanguage', "=", $idLanguage)
            ->where('lt.idLanguage', "=", $idLanguage)
            ->distinct()
            ->limit($limit)
            ->orderBy("name")->orderBy("lexeme")->all();
        foreach($genericlabels as $genericlabel) {
            $data[] = [
                'id' => $genericlabel->idGenericLabel,
                'text' => $genericlabel->name . " [{$genericlabel->layerName}]",
                'state' => 'closed',
                'type' => 'lexeme',
            ];
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
    $id = uniqid("layersTree");
@endphp
<div
        id="gridLayers"
        class="h-full"
        hx-trigger="reload-gridLayers from:body"
        hx-target="this"
        hx-swap="outerHTML"
        hx-post="/layers/grid"
>
    <div class="relative h-full overflow-auto">
        <div id="layersTreeWrapper" class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
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
                                let id = row.id.substr(1);
                                if (row.type === "layergroup") {
                                    htmx.ajax("GET", `/layers/layergroup/${id}/edit`, "#editarea");
                                }
                                if (row.type === "layertype") {
                                    htmx.ajax("GET", `/layers/layertype/${id}/edit`, "#editarea");
                                }
                                if (row.type === "genericlabel") {
                                    htmx.ajax("GET", `/layers/genericlabel/${id}/edit`, "#editarea");
                                }
                            }
                        });
                    });
                </script>
            @endfragment
        </div>
    </div>
</div>
