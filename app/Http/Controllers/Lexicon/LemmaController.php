<?php

namespace App\Http\Controllers\Lexicon;

use App\Data\ComboBox\QData;
use App\Data\Lemma\CreateExpressionData;
use App\Data\Lemma\CreateLemmaData;
use App\Data\Lemma\SearchLemmaData;
use App\Data\Lemma\UpdateLemmaData;
use App\Data\Lexicon\CreateFeatureData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Lemma;
use App\Services\AppService;
use App\Services\Lemma\BrowseService;
use App\Services\Lemma\LexiconPatternService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware('master')]
class LemmaController extends Controller
{
    #[Get(path: '/lemma')]
    public function browse(SearchLemmaData $search)
    {
        $data = BrowseService::browseLemmaBySearch($search);

        return view('Lemma.browse', [
            'title' => 'Lemmas',
            'data' => $data,
        ]);
    }

    #[Post(path: '/lemma/search')]
    public function search(SearchLemmaData $search)
    {
        $data = BrowseService::browseLemmaBySearch($search);

        return view('Lemma.tree', [
            'data' => $data,
            'title' => 'Lemmas',
        ]);
    }

    #[Get(path: '/lemma/listForSearch')]
    public function listForSearch(QData $data)
    {
        debug($data);
        $name = (strlen($data->lemmaName) > 0) ? $data->lemmaName : 'none';

        return ['results' => Criteria::byFilterLanguage('view_lemma', ['name', 'startswith', trim($name)])
            ->select('idLemma', 'name', 'fullName')
            ->limit(50)
            ->orderby('name')->all()];
    }

    #[Get(path: '/lemma/new')]
    public function formNew()
    {
        return view('Lemma.new');
    }

    #[Post(path: '/lemma')]
    public function create(CreateLemmaData $data)
    {
        try {
            $exists = Criteria::table('view_lemma')
                ->whereRaw("name = '{$data->name}' collate 'utf8mb4_bin'")
                ->where('idUDPOS', $data->idUDPOS)
                ->where('idLanguage', $data->idLanguage)
                ->first();
            if (! is_null($exists)) {
                throw new \Exception('Lemma already exists.');
            }
            $newLemma = json_encode([
                'name' => $data->name,
                'idLanguage' => $data->idLanguage,
                'idUDPOS' => $data->idUDPOS,
                'idUser' => AppService::getCurrentIdUser(),
            ]);
            $idLemma = Criteria::function('lemma_create(?)', [$newLemma]);
            $isMWE = str_contains($data->name, ' ');
            if ($isMWE) {
                $expressions = explode(' ', $data->name);
                foreach ($expressions as $i => $expression) {
                    $exp = json_encode([
                        'form' => trim($expression),
                        'idLexiconGroup' => 1,
                    ]);
                    $idLexicon = Criteria::function('lexicon_create(?)', [$exp]);
                    Criteria::create('lexicon_expression', [
                        'idLemma' => $idLemma,
                        'idExpression' => $idLexicon,
                        'position' => $i + 1,
                        'head' => ($i == 0),
                        'breakBefore' => 0,
                    ]);
                }
            }

            return $this->clientRedirect("/lemma/{$idLemma}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/lemma/{idLemma}/expressions')]
    public function expressions(int $idLemma)
    {
        $lemma = Lemma::byId($idLemma);
        $expressions = Criteria::table('view_lexicon_expression as e')
            ->where('e.idLemma', $idLemma)
            ->orderBy('e.position')
            ->all();

        return view('Lemma.expressions', [
            'lemma' => $lemma,
            'expressions' => $expressions,
        ]);
    }

    #[Get(path: '/lemma/{idLemma}')]
    public function edit(int $idLemma)
    {
        $lemma = Lemma::byId($idLemma);
        $expressions = Criteria::table('view_lexicon_expression as e')
            ->where('e.idLemma', $idLemma)
            ->orderBy('e.position')
            ->all();

        return view('Lemma.edit', [
            'lemma' => $lemma,
            'expressions' => $expressions,
            'pattern' => null, // Temporarily disabled
        ]);
    }

    #[Put(path: '/lemma/{idLemma}')]
    public function update(int $idLemma, UpdateLemmaData $data)
    {
        $data->idLemma = $idLemma;
        try {
            Criteria::function('lemma_update(?)', [json_encode($data->toArray())]);

            return $this->renderNotify('success', 'Lemma updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/lemma/{idLemma}')]
    public function delete(int $idLemma)
    {
        try {
            Criteria::function('lemma_delete(?,?)', [$idLemma, AppService::getCurrentIdUser()]);

            return $this->clientRedirect('/lemma');
        } catch (\Exception $e) {
            return $this->renderNotify('error', 'Deletion failed. Check if there is some LU using this lemma.');
        }
    }

    /*------
      Expression
      ------ */

    #[Get(path: '/lemma/expression/listForSelect')]
    public function listExpressionForSelect(QData $data)
    {
        $name = (strlen($data->q) > 0) ? $data->q : 'none';

        return ['results' => Criteria::byFilterLanguage('lexicon', ['form', 'startswith', trim($name)])
            ->select('idLexicon', 'form as name')
            ->limit(50)
            ->orderby('name')->all()];
    }

    #[Post(path: '/lemma/{idLemma}/expression')]
    public function createExpression(int $idLemma, CreateExpressionData $data)
    {
        $data->idLemma = $idLemma;
        try {
            if (trim($data->form) != '') {
                $lexicon = Criteria::table('lexicon')
                    ->whereRaw("form collate 'utf8mb4_bin' = '{$data->form}'")
                    ->first();
                if (is_null($lexicon)) {
                    $newLexicon = json_encode([
                        'form' => $data->form,
                        'idLexiconGroup' => 1,
                    ]);
                    $idLexicon = Criteria::function('lexicon_create(?)', [$newLexicon]);
                } else {
                    $idLexicon = $lexicon->idLexicon;
                }
                Criteria::create('lexicon_expression', [
                    'idLemma' => $idLemma,
                    'idExpression' => $idLexicon,
                    'position' => $data->position,
                    'head' => $data->head,
                    'breakBefore' => $data->breakBefore,
                ]);
                $this->trigger('reload-gridExpressions');

                return $this->renderNotify('success', 'Expression added.');
            } else {
                throw new \Exception('Expression not informed.');
            }
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/lemma/expression/{idLexiconExpression}')]
    public function deleteExpression(int $idLexiconExpression)
    {
        try {
            Criteria::deleteById('lexicon_expression', 'idLexiconExpression', $idLexiconExpression);
            $this->trigger('reload-gridExpressions');

            return $this->renderNotify('success', 'Expression removed.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    /*--------
      Features
     -------- */

    #[Get(path: '/lemma/feature/listForSelect')]
    public function listFeatureForSelect(QData $data)
    {
        $name = (strlen($data->q) > 0) ? $data->q : 'none';

        return ['results' => Criteria::table('udfeature')
            ->select('idUDFeature', 'name')
            ->whereRaw("lower(name) LIKE lower('{$name}%')")
            ->orderby('name')->all()];
    }

    #[Post(path: '/lemma/{idLemma}/feature')]
    public function createFeature(int $idLemma, CreateFeatureData $data)
    {
        try {
            Criteria::table('lexicon_feature')
                ->where('idLexicon', $idLemma)
                ->where('idUDFeature', $data->idUDFeature)
                ->delete();
            Criteria::create('lexicon_feature', [
                'idLexicon' => $idLemma,
                'idUDFeature' => $data->idUDFeature,
            ]);
            $this->trigger('reload-gridFeatures');

            return $this->renderNotify('success', 'Feature added.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/lemma/{idLemma}/features')]
    public function features(int $idLemma)
    {
        $lemma = Lemma::byId($idLemma);
        $features = Criteria::table('udfeature as f')
            ->join('lexicon_feature as lf', 'f.idUDFeature', 'lf.idUDFeature')
            ->select('f.idUDFeature', 'f.name', 'lf.idLexicon')
            ->where('lf.idLexicon', $idLemma)
            ->all();

        return view('Lemma.features', [
            'lemma' => $lemma,
            'features' => $features,
        ]);
    }

    #[Delete(path: '/lemma/{idLemma}/feature/{idUDFeature}')]
    public function deleteFeature(int $idLemma, int $idUDFeature)
    {
        try {
            Criteria::table('lexicon_feature')
                ->where('idLexicon', $idLemma)
                ->where('idUDFeature', $idUDFeature)
                ->delete();
            $this->trigger('reload-gridFeatures');

            return $this->renderNotify('success', 'Feature removed.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    /*--------
      Pattern
     -------- */

    #[Get(path: '/lemma/{idLemma}/pattern')]
    public function getPattern(int $idLemma)
    {
        $patternService = app(LexiconPatternService::class);
        $pattern = $patternService->getLemmaPattern($idLemma);

        if (! $pattern) {
            return response()->json(['error' => 'No pattern found for this lemma'], 404);
        }

        $enrichedPattern = $this->enrichPatternData($pattern);
        $treexFormat = $this->convertPatternToTreexFormat($enrichedPattern);

        return response()->json($treexFormat);
    }

    /**
     * Enrich pattern data with human-readable labels
     */
    protected function enrichPatternData(array $pattern): array
    {
        // Enrich nodes with word forms and POS labels
        $enrichedNodes = [];
        foreach ($pattern['nodes'] as $node) {
            $enrichedNode = $node;

            // Get word form from lexicon
            if ($node['idLexicon']) {
                $lexicon = Criteria::table('lexicon')
                    ->where('idLexicon', $node['idLexicon'])
                    ->first();
                $enrichedNode['form'] = $lexicon->form ?? '';
            } else {
                $enrichedNode['form'] = '';
            }

            // Get POS label
            if ($node['idUDPOS']) {
                $udpos = Criteria::table('udpos')
                    ->where('idUDPOS', $node['idUDPOS'])
                    ->first();
                $enrichedNode['posLabel'] = $udpos->POS ?? '';
            } else {
                $enrichedNode['posLabel'] = '';
            }

            $enrichedNodes[] = $enrichedNode;
        }

        // Enrich edges with relation labels
        $enrichedEdges = [];
        foreach ($pattern['edges'] as $edge) {
            $enrichedEdge = $edge;

            // Get relation label
            if ($edge['idUDRelation']) {
                $relation = Criteria::table('udrelation')
                    ->where('idUDRelation', $edge['idUDRelation'])
                    ->first();
                $enrichedEdge['relationLabel'] = $relation->info ?? '';
            } else {
                $enrichedEdge['relationLabel'] = '';
            }

            $enrichedEdges[] = $enrichedEdge;
        }

        return [
            'pattern' => $pattern['pattern'],
            'nodes' => $enrichedNodes,
            'edges' => $enrichedEdges,
            'constraints' => $pattern['constraints'] ?? [],
        ];
    }

    /**
     * Convert pattern data to js-treex-view format
     */
    protected function convertPatternToTreexFormat(array $pattern): array
    {
        // Safety check
        if (empty($pattern['nodes'])) {
            return [[
                'desc' => [['No data', 'empty']],
                'zones' => [
                    'conllu' => [
                        'trees' => [
                            'a' => [
                                'layer' => 'a',
                                'nodes' => [],
                            ],
                        ],
                    ],
                ],
            ]];
        }

        // Build node map indexed by node ID
        $nodeMap = [];
        foreach ($pattern['nodes'] as $node) {
            $nodeMap[$node['idLexiconPatternNode']] = $node;
        }

        // Build parent-child map from edges
        $childrenMap = [];
        $parentMap = [];
        foreach ($pattern['edges'] as $edge) {
            $parentId = $edge['idNodeHead'];
            $childId = $edge['idNodeDependent'];

            if (! isset($childrenMap[$parentId])) {
                $childrenMap[$parentId] = [];
            }
            $childrenMap[$parentId][] = $childId;
            $parentMap[$childId] = $parentId;
        }

        // Find root node (node with isRoot=1 or no parent)
        $rootNode = null;
        foreach ($pattern['nodes'] as $node) {
            if ($node['isRoot'] || ! isset($parentMap[$node['idLexiconPatternNode']])) {
                $rootNode = $node;
                break;
            }
        }

        // Build treex nodes
        $treexNodes = [];

        // Create root node (w0)
        $treexNodes[] = [
            'id' => 'w0',
            'ord' => 0,
            'parent' => null,
            'data' => [
                'id' => '0',
                'form' => '<root>',
                'deprel' => 'root',
            ],
            'labels' => ['<root>', '#{#00008b}root'],
            'firstson' => $rootNode ? 'w'.$rootNode['position'] : null,
            'rbrother' => null,
        ];

        // Create nodes for each pattern node
        foreach ($pattern['nodes'] as $node) {
            $nodeId = 'w'.$node['position'];
            $parentId = isset($parentMap[$node['idLexiconPatternNode']]) ? 'w'.$nodeMap[$parentMap[$node['idLexiconPatternNode']]]['position'] : 'w0';

            // Find relation label for this node
            $relationLabel = '';
            foreach ($pattern['edges'] as $edge) {
                if ($edge['idNodeDependent'] == $node['idLexiconPatternNode']) {
                    $relationLabel = $edge['relationLabel'] ?? '';
                    break;
                }
            }

            // Build labels array
            $labels = [
                $node['form'],
                '#{#00008b}'.$relationLabel,
                '#{#004048}'.$node['posLabel'],
            ];

            $treexNodes[] = [
                'id' => $nodeId,
                'ord' => $node['position'],
                'parent' => $parentId,
                'data' => [
                    'id' => (string) $node['position'],
                    'form' => $node['form'],
                    'deprel' => $relationLabel,
                    'upos' => $node['posLabel'],
                ],
                'labels' => $labels,
                'firstson' => null,
                'rbrother' => null,
            ];
        }

        // Calculate firstson and rbrother relationships
        // Group children by parent
        $childrenByParent = [];
        foreach ($treexNodes as $node) {
            if ($node['parent']) {
                if (! isset($childrenByParent[$node['parent']])) {
                    $childrenByParent[$node['parent']] = [];
                }
                $childrenByParent[$node['parent']][] = $node['id'];
            }
        }

        // Update firstson and rbrother
        foreach ($treexNodes as &$node) {
            if (isset($childrenByParent[$node['id']])) {
                $children = $childrenByParent[$node['id']];
                sort($children);
                $node['firstson'] = $children[0];

                // Set rbrother for siblings
                for ($i = 0; $i < count($children) - 1; $i++) {
                    $currentChildId = $children[$i];
                    $nextChildId = $children[$i + 1];

                    foreach ($treexNodes as &$childNode) {
                        if ($childNode['id'] === $currentChildId) {
                            $childNode['rbrother'] = $nextChildId;
                            break;
                        }
                    }
                }
            }
        }

        // Build description (word sequence)
        $desc = [];
        foreach ($pattern['nodes'] as $index => $node) {
            if ($index > 0) {
                $desc[] = [' ', 'space'];
            }
            $desc[] = [$node['form'], 'w'.$node['position']];
        }

        return [[
            'desc' => $desc,
            'zones' => [
                'conllu' => [
                    'trees' => [
                        'a' => [
                            'layer' => 'a',
                            'nodes' => $treexNodes,
                        ],
                    ],
                ],
            ],
        ]];
    }
}
