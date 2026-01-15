<?php

namespace App\Http\Controllers\Parser;

use App\Data\Parser\ParseInputData;
use App\Http\Controllers\Controller;
use App\Repositories\Parser\GrammarGraph;
use App\Repositories\Parser\ParseGraph;
use App\Services\Parser\ParserService;
use App\Services\Parser\VisualizationService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'api')]
class ApiController extends Controller
{
    public function __construct(
        private ParserService $parserService,
        private VisualizationService $visualizationService
    ) {}

    /**
     * Parse sentence (JSON API)
     */
    #[Post(path: '/api/parser/parse')]
    public function parse(ParseInputData $data)
    {
        try {
            $result = $this->parserService->parse($data);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            logger()->error('API Parser error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get parse result by ID
     */
    #[Get(path: '/api/parser/result/{id}')]
    public function getResult(int $id)
    {
        try {
            $result = $this->parserService->getParseResult($id);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get visualization data
     */
    #[Get(path: '/api/parser/visualization/{id}')]
    public function visualization(int $id)
    {
        try {
            $d3Data = $this->visualizationService->prepareD3Data($id);
            $stats = $this->visualizationService->getStatistics($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'graph' => $d3Data,
                    'statistics' => $stats,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * List grammar graphs
     */
    #[Get(path: '/api/parser/grammars')]
    public function listGrammars()
    {
        $grammars = GrammarGraph::list();

        return response()->json([
            'success' => true,
            'data' => $grammars,
        ]);
    }

    /**
     * Get grammar graph details
     */
    #[Get(path: '/api/parser/grammar/{id}')]
    public function getGrammar(int $id)
    {
        try {
            $grammar = GrammarGraph::getWithStructure($id);

            return response()->json([
                'success' => true,
                'data' => $grammar,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * List parse results
     */
    #[Get(path: '/api/parser/results')]
    public function listResults()
    {
        $limit = request()->query('limit', 50);
        $status = request()->query('status');

        if ($status) {
            $results = ParseGraph::listByStatus($status, $limit);
        } else {
            $results = ParseGraph::list($limit);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
