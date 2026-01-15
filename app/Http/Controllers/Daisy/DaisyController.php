<?php

namespace App\Http\Controllers\Daisy;

use App\Data\Daisy\DaisyInputData;
use App\Http\Controllers\Controller;
use App\Services\Daisy\DaisyService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class DaisyController extends Controller
{
    public function __construct(
        private DaisyService $daisyService
    ) {}

    /**
     * Show Daisy semantic parser interface
     */
    #[Get(path: '/daisy')]
    public function index()
    {
        $searchTypes = config('daisy.searchTypes');
        $defaultLanguage = config('daisy.defaultLanguage');
        $defaultSearchType = config('daisy.defaultSearchType');
        $defaultLevel = config('daisy.defaultLevel');

        return view('Daisy.daisy', [
            'searchTypes' => $searchTypes,
            'defaultLanguage' => $defaultLanguage,
            'defaultSearchType' => $defaultSearchType,
            'defaultLevel' => $defaultLevel,
        ]);
    }

    /**
     * Parse sentence and return disambiguation results
     */
    #[Post(path: '/daisy/parse')]
    public function parse(DaisyInputData $data)
    {
        try {
            // Run Daisy disambiguation pipeline
            $result = $this->daisyService->disambiguate($data);

            // Return results view
            return view('Daisy.daisyResults', [
                'result' => $result->result,
                'graph' => $result->graph,
                'sentenceUD' => $result->sentenceUD,
                'weights' => $result->weights,
                'sentence' => $data->sentence,
            ]);
        } catch (\Exception $e) {
            logger()->error('Daisy parse error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return view('Daisy.daisyResults', [
                'error' => 'Error processing sentence: '.$e->getMessage(),
                'result' => [],
                'graph' => ['nodes' => [], 'links' => []],
                'sentenceUD' => [],
                'weights' => [],
                'sentence' => $data->sentence,
            ]);
        }
    }

    /**
     * Render graph visualization
     */
    #[Post(path: '/daisy/graph')]
    public function graph(DaisyInputData $data)
    {
        try {
            // Run Daisy disambiguation pipeline
            $result = $this->daisyService->disambiguate($data);

            // Return only the graph portion
            return view('Daisy.daisyGraph', [
                'graph' => $result->graph,
            ]);
        } catch (\Exception $e) {
            logger()->error('Daisy graph error: '.$e->getMessage());

            return view('Daisy.daisyGraph', [
                'graph' => ['nodes' => [], 'links' => []],
            ]);
        }
    }
}
