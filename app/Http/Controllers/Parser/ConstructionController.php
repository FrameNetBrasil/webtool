<?php

namespace App\Http\Controllers\Parser;

use App\Data\Parser\Construction\CreateData;
use App\Data\Parser\Construction\ImportData;
use App\Data\Parser\Construction\SearchData;
use App\Data\Parser\Construction\TestPatternData;
use App\Data\Parser\Construction\UpdateData;
use App\Http\Controllers\Controller;
use App\Repositories\Parser\ConstructionV4;
use App\Repositories\Parser\GrammarGraph;
use App\Services\Parser\V4\ConstructionGraphService;
use App\Services\Parser\V4\ConstructionServiceV4;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'master')]
class ConstructionController extends Controller
{
    #[Get(path: '/parser/construction')]
    public function index(SearchData $search)
    {
        $grammars = GrammarGraph::list();
        $constructions = ConstructionV4::listToGrid($search);
        return view('Parser.Construction.index', [
            'constructions' => [],
            'grammars' => $grammars,
        ]);
    }

    #[Post(path: '/parser/construction/search')]
    public function search(SearchData $search)
    {
        $grammars = GrammarGraph::list();
        $constructions = ConstructionV4::listToGrid($search);
        return view('Parser.Construction.index', [
            'constructions' => $constructions,
            'grammars' => $grammars,
        ])->fragment('search');

    }

    /**
     * Show create construction form
     */
    #[Get(path: '/parser/construction/new')]
    public function new()
    {
        $grammars = GrammarGraph::list();

        return view('Parser.Construction.formNew', [
            'grammars' => $grammars,
        ]);
    }

    /**
     * Show edit construction page with tabs
     */
    #[Get(path: '/parser/construction/{id}/edit')]
    public function edit(int $id)
    {
        $construction = ConstructionV4::byId($id);
        $grammar = GrammarGraph::byId($construction->idGrammarGraph);

        return view('Parser.Construction.edit', [
            'construction' => $construction,
            'grammar' => $grammar,
        ]);
    }

    /**
     * Create new construction
     */
    #[Post(path: '/parser/construction')]
    public function create(CreateData $data)
    {
        try {
            // Check for name uniqueness
            if (ConstructionV4::existsByName($data->idGrammarGraph, $data->name)) {
                return viewNotify('error', "Construction '{$data->name}' already exists in this grammar.");
            }

            $service = new ConstructionServiceV4;
            $idConstruction = $service->compileAndStoreV4($data);

            $this->trigger('reload-gridConstruction');

            return viewNotify('success', "Construction '{$data->name}' created successfully.");
        } catch (\Exception $e) {
            return viewNotify('error', $e->getMessage());
        }
    }

    /**
     * Update construction
     */
    #[Post(path: '/parser/construction/{id}')]
    public function update(int $id, UpdateData $data)
    {
        try {
            // Check for name uniqueness (excluding current construction)
            if (ConstructionV4::existsByName($data->idGrammarGraph, $data->name, $id)) {
                return viewNotify('error', "Construction '{$data->name}' already exists in this grammar.");
            }

            $service = new ConstructionServiceV4;
            $service->updateV4($id, $data);

            $this->trigger('reload-gridConstruction');

            return viewNotify('success', 'Construction updated successfully.');
        } catch (\Exception $e) {
            return viewNotify('error', $e->getMessage());
        }
    }

    /**
     * Delete construction
     */
    #[Delete(path: '/parser/construction/{id}')]
    public function delete(int $id)
    {
        try {
            $construction = ConstructionV4::byId($id);
            $constructionName = $construction->name;

            ConstructionV4::delete($id);

            $this->trigger('reload-gridConstruction');
            $this->clientRedirect('/parser/construction');

            return viewNotify('success', "Construction '{$constructionName}' deleted successfully.");
        } catch (\Exception $e) {
            return viewNotify('error', $e->getMessage());
        }
    }

    // ========================================
    // Tab Content Routes
    // ========================================

    /**
     * Basic tab: name, type, description
     */
    #[Get(path: '/parser/construction/{id}/tab/basic')]
    public function tabBasic(int $id)
    {
        $construction = ConstructionV4::byId($id);

        return view('Parser.Construction.tabs/basic', [
            'construction' => $construction,
        ]);
    }

    /**
     * Pattern tab: pattern editor, priority, enabled
     */
    #[Get(path: '/parser/construction/{id}/tab/pattern')]
    public function tabPattern(int $id)
    {
        $construction = ConstructionV4::byId($id);
        $compiledPattern = ConstructionV4::getCompiledPattern($construction);

        return view('Parser.Construction.tabs/pattern', [
            'construction' => $construction,
            'compiledPattern' => $compiledPattern,
        ]);
    }

    /**
     * CE Labels tab: phrasalCE, clausalCE, sententialCE
     */
    #[Get(path: '/parser/construction/{id}/tab/ce-labels')]
    public function tabCeLabels(int $id)
    {
        $construction = ConstructionV4::byId($id);
        $uniqueLabels = ConstructionV4::getUniqueLabels($construction->idGrammarGraph);

        return view('Parser.Construction.tabs/ce-labels', [
            'construction' => $construction,
            'uniqueLabels' => $uniqueLabels,
        ]);
    }

    /**
     * Constraints tab: JSON editor
     */
    #[Get(path: '/parser/construction/{id}/tab/constraints')]
    public function tabConstraints(int $id)
    {
        $construction = ConstructionV4::byId($id);
        $constraints = ConstructionV4::getConstraints($construction);

        return view('Parser.Construction.tabs/constraints', [
            'construction' => $construction,
            'constraints' => $constraints,
        ]);
    }

    /**
     * MWE tab: MWE-specific fields + lookahead
     */
    #[Get(path: '/parser/construction/{id}/tab/mwe')]
    public function tabMwe(int $id)
    {
        $construction = ConstructionV4::byId($id);
        $invalidationPatterns = ConstructionV4::getInvalidationPatterns($construction);
        $confirmationPatterns = ConstructionV4::getConfirmationPatterns($construction);

        return view('Parser.Construction.tabs/mwe', [
            'construction' => $construction,
            'invalidationPatterns' => $invalidationPatterns,
            'confirmationPatterns' => $confirmationPatterns,
        ]);
    }

    /**
     * Examples tab: example sentences
     */
    #[Get(path: '/parser/construction/{id}/tab/examples')]
    public function tabExamples(int $id)
    {
        $construction = ConstructionV4::byId($id);
        $examples = ConstructionV4::getExamples($construction);

        return view('Parser.Construction.tabs/examples', [
            'construction' => $construction,
            'examples' => $examples,
        ]);
    }

    // ========================================
    // Graph Routes
    // ========================================

    /**
     * Pattern graph: BNF structure visualization
     */
    #[Get(path: '/parser/construction/{id}/graph/pattern')]
    public function graphPattern(int $id)
    {
        $construction = ConstructionV4::byId($id);
        $graphService = new ConstructionGraphService;
        $graphData = $graphService->generatePatternGraph($construction);

        return view('Parser.Construction.graphs/pattern', [
            'construction' => $construction,
            'graphData' => $graphData,
        ]);
    }

    /**
     * Hierarchy graph: construction relationships
     */
    #[Get(path: '/parser/construction/{id}/graph/hierarchy')]
    public function graphHierarchy(int $id)
    {
        $construction = ConstructionV4::byId($id);
        $graphService = new ConstructionGraphService;
        $graphData = $graphService->generateHierarchyGraph($construction->idGrammarGraph);

        return view('Parser.Construction.graphs/hierarchy', [
            'construction' => $construction,
            'graphData' => $graphData,
        ]);
    }

    /**
     * Priority graph: priority lanes visualization
     */
    #[Get(path: '/parser/construction/{id}/graph/priority')]
    public function graphPriority(int $id)
    {
        $construction = ConstructionV4::byId($id);
        $graphService = new ConstructionGraphService;
        $graphData = $graphService->generatePriorityGraph($construction->idGrammarGraph);

        return view('Parser.Construction.graphs/priority', [
            'construction' => $construction,
            'graphData' => $graphData,
        ]);
    }

    // ========================================
    // Feature Routes
    // ========================================

    /**
     * Toggle enabled/disabled
     */
    #[Post(path: '/parser/construction/{id}/toggle')]
    public function toggle(int $id)
    {
        try {
            ConstructionV4::toggle($id);
            $construction = ConstructionV4::byId($id);
            $status = $construction->enabled ? 'enabled' : 'disabled';

            $this->trigger('reload-gridConstruction');

            return viewNotify('success', "Construction {$status} successfully.");
        } catch (\Exception $e) {
            return viewNotify('error', $e->getMessage());
        }
    }

    /**
     * Test pattern against sentence
     */
    #[Post(path: '/parser/construction/test')]
    public function test(TestPatternData $data)
    {
        try {
            $service = new ConstructionServiceV4;
            $result = $service->testPatternAgainstSentence($data->pattern, $data->sentence, $data->idGrammarGraph);

            return view('Parser.Construction.test-result', [
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return viewNotify('error', $e->getMessage());
        }
    }

    /**
     * Export constructions to JSON
     */
    #[Get(path: '/parser/construction/export')]
    public function export(int $idGrammarGraph)
    {
        try {
            $service = new ConstructionServiceV4;
            $json = $service->exportToJson($idGrammarGraph);

            $grammar = GrammarGraph::byId($idGrammarGraph);
            $filename = 'constructions_' . $grammar->name . '_' . date('Y-m-d') . '.json';

            return response()->json($json)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return viewNotify('error', $e->getMessage());
        }
    }

    /**
     * Import constructions from JSON
     */
    #[Post(path: '/parser/construction/import')]
    public function import(ImportData $data)
    {
        try {
            $service = new ConstructionServiceV4;
            $result = $service->importFromJson($data->idGrammarGraph, $data->file, $data->overwrite);

            $this->trigger('reload-gridConstruction');

            return view('Parser.Construction.import-result', [
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return viewNotify('error', $e->getMessage());
        }
    }

    /**
     * Recompile construction pattern
     */
    #[Post(path: '/parser/construction/{id}/compile')]
    public function compile(int $id)
    {
        try {
            $service = new ConstructionServiceV4;
            $construction = ConstructionV4::byId($id);

            // Validate pattern
            $validation = $service->validatePattern($construction->pattern);

            if (!$validation['valid']) {
                return viewNotify('error', 'Pattern validation failed: ' . $validation['error']);
            }

            // Recompile and update
            ConstructionV4::update($id, [
                'compiledPattern' => json_encode($validation['compiled']),
            ]);

            return viewNotify('success', 'Pattern recompiled successfully.');
        } catch (\Exception $e) {
            return viewNotify('error', $e->getMessage());
        }
    }
}
