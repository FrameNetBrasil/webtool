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

#[Middleware(name: 'web')]
class ParserController extends Controller
{
    public function __construct(
        private ParserService $parserService,
        private VisualizationService $visualizationService
    ) {}

    /**
     * Show main parser interface
     */
    #[Get(path: '/parser')]
    public function index()
    {
        $grammars = GrammarGraph::list();
        $recentParses = ParseGraph::list(10);

        return view('Parser.parser', [
            'grammars' => $grammars,
            'recentParses' => $recentParses,
        ]);
    }

    /**
     * Process sentence parsing (HTMX endpoint)
     */
    #[Post(path: '/parser/parse')]
    public function parse(ParseInputData $data)
    {
        try {
            $result = $this->parserService->parse($data);

            return view('Parser.parserResults', [
                'result' => $result,
                'sentence' => $data->sentence,
            ]);
        } catch (\Exception $e) {
            logger()->error('Parser error: '.$e->getMessage());

            return view('Parser.parserError', [
                'error' => $e->getMessage(),
                'sentence' => $data->sentence,
            ]);
        }
    }

    /**
     * View parse graph details
     */
    #[Get(path: '/parser/result/{id}')]
    public function viewResult(int $id)
    {
        $result = $this->parserService->getParseResult($id);
        $stats = $this->visualizationService->getStatistics($id);

        return view('Parser.parserResultPage', [
            'result' => $result,
            'stats' => $stats,
            'sentence' => $result->sentence,
        ]);
    }

    /**
     * Get visualization data (HTMX endpoint)
     */
    #[Get(path: '/parser/visualization/{id}')]
    public function visualization(int $id)
    {
        $d3Data = $this->visualizationService->prepareD3Data($id);
        $stats = $this->visualizationService->getStatistics($id);

        return view('Parser.parserGraph', [
            'idParserGraph' => $id,
            'd3Data' => json_encode($d3Data),
            'stats' => $stats,
        ]);
    }

    /**
     * List recent parses
     */
    #[Get(path: '/parser/history')]
    public function history()
    {
        $parses = ParseGraph::list(50);

        return view('Parser.parserHistory', [
            'parses' => $parses,
        ]);
    }

    /**
     * Export parse graph
     */
    #[Get(path: '/parser/export/{id}/{format}')]
    public function export(int $id, string $format)
    {
        $parseGraph = ParseGraph::byId($id);

        switch ($format) {
            case 'graphml':
                $content = $this->visualizationService->exportGraphML($id);
                $filename = "parse_{$id}.graphml";
                $mimeType = 'application/xml';
                break;

            case 'dot':
                $content = $this->visualizationService->exportDOT($id);
                $filename = "parse_{$id}.dot";
                $mimeType = 'text/plain';
                break;

            case 'json':
                $d3Data = $this->visualizationService->prepareD3Data($id);
                $content = json_encode($d3Data, JSON_PRETTY_PRINT);
                $filename = "parse_{$id}.json";
                $mimeType = 'application/json';
                break;

            default:
                abort(400, 'Invalid export format');
        }

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
