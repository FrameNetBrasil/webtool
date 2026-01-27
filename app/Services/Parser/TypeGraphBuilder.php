<?php

namespace App\Services\Parser;

use App\Data\Parser\V5\TypeGraph;
use App\Data\Parser\V5\TypeGraphEdge;
use App\Data\Parser\V5\TypeGraphNode;
use App\Repositories\Parser\ConstructionV4;
use Illuminate\Support\Facades\Log;

/**
 * Type Graph Builder
 *
 * Constructs the unified Type Graph from construction definitions.
 *
 * The Type Graph represents:
 * - All constructions as nodes
 * - All CE labels as nodes
 * - Relationships between them as edges
 * - Mandatory element requirements
 * - CE dependency hierarchies
 */
class TypeGraphBuilder
{
    private array $constructions = [];

    private array $nodes = [];

    private array $edges = [];

    private array $mandatoryElements = [];

    private array $ceHierarchy = [];

    private array $constructionIndex = [];

    private array $ceLabelIndex = [];

    /**
     * Build Type Graph for a grammar
     *
     * @param  int  $idGrammarGraph  Grammar ID
     */
    public function buildForGrammar(int $idGrammarGraph): TypeGraph
    {
        Log::info("Building Type Graph for grammar {$idGrammarGraph}");

        // Reset state
        $this->resetState();

        // Step 1: Load all constructions
        $this->constructions = ConstructionV4::listByGrammar($idGrammarGraph);
        Log::info('Loaded '.count($this->constructions).' constructions');

        // Step 2: Create construction nodes
        $this->createConstructionNodes();
        Log::info('Created '.count(array_filter($this->nodes, fn ($n) => $n->isConstruction())).' construction nodes');

        // Step 3: Create CE label nodes
        $this->createCELabelNodes();
        Log::info('Created '.count(array_filter($this->nodes, fn ($n) => $n->isCELabel())).' CE label nodes');

        // Step 4: Create "produces" edges (Construction → CE)
        $this->createProducesEdges();

        // Step 5: Create "requires" edges (Construction → Construction/CE)
        $this->createRequiresEdges();

        // Step 6: Create "inherits" edges (Construction → Construction)
        $this->createInheritsEdges();

        Log::info('Created '.count($this->edges).' edges');

        // Step 7: Analyze mandatory elements
        $this->analyzeMandatoryElements();
        Log::info('Analyzed mandatory elements for '.count($this->mandatoryElements).' constructions');

        // Step 8: Build CE hierarchy
        $this->buildCEHierarchy();
        Log::info('Built CE hierarchy with '.count($this->ceHierarchy).' entries');

        // Step 9: Create indexes
        $this->createIndexes();

        return new TypeGraph(
            idGrammarGraph: $idGrammarGraph,
            nodes: $this->nodes,
            edges: $this->edges,
            mandatoryElements: $this->mandatoryElements,
            ceHierarchy: $this->ceHierarchy,
            constructionIndex: $this->constructionIndex,
            ceLabelIndex: $this->ceLabelIndex
        );
    }

    /**
     * Reset internal state
     */
    private function resetState(): void
    {
        $this->constructions = [];
        $this->nodes = [];
        $this->edges = [];
        $this->mandatoryElements = [];
        $this->ceHierarchy = [];
        $this->constructionIndex = [];
        $this->ceLabelIndex = [];
    }

    /**
     * Create construction nodes
     */
    private function createConstructionNodes(): void
    {
        foreach ($this->constructions as $construction) {
            $node = TypeGraphNode::construction(
                constructionId: $construction->idConstruction,
                name: $construction->name,
                constructionType: $construction->constructionType,
                priority: $construction->priority,
                metadata: [
                    'pattern' => $construction->pattern,
                    'enabled' => $construction->enabled ?? true,
                ]
            );

            $this->nodes[] = $node;
        }
    }

    /**
     * Create CE label nodes from all constructions
     */
    private function createCELabelNodes(): void
    {
        $ceLabels = [
            'phrasal' => [],
            'clausal' => [],
            'sentential' => [],
        ];

        // Collect all CE labels from constructions
        foreach ($this->constructions as $construction) {
            if (! empty($construction->phrasalCE)) {
                $ceLabels['phrasal'][$construction->phrasalCE] = true;
            }
            if (! empty($construction->clausalCE)) {
                $ceLabels['clausal'][$construction->clausalCE] = true;
            }
            if (! empty($construction->sententialCE)) {
                $ceLabels['sentential'][$construction->sententialCE] = true;
            }
        }

        // Create nodes for each unique CE label
        foreach ($ceLabels as $level => $labels) {
            foreach (array_keys($labels) as $label) {
                $this->nodes[] = TypeGraphNode::ceLabel($label, $level);
            }
        }
    }

    /**
     * Create "produces" edges (Construction → CE)
     */
    private function createProducesEdges(): void
    {
        foreach ($this->constructions as $construction) {
            $fromNodeId = "construction_{$construction->idConstruction}";

            // Phrasal CE
            if (! empty($construction->phrasalCE)) {
                $this->edges[] = TypeGraphEdge::produces(
                    $fromNodeId,
                    "ce_phrasal_{$construction->phrasalCE}",
                    mandatory: true
                );
            }

            // Clausal CE
            if (! empty($construction->clausalCE)) {
                $this->edges[] = TypeGraphEdge::produces(
                    $fromNodeId,
                    "ce_clausal_{$construction->clausalCE}",
                    mandatory: true
                );
            }

            // Sentential CE
            if (! empty($construction->sententialCE)) {
                $this->edges[] = TypeGraphEdge::produces(
                    $fromNodeId,
                    "ce_sentential_{$construction->sententialCE}",
                    mandatory: true
                );
            }
        }
    }

    /**
     * Create "requires" edges (Construction → Construction/CE)
     *
     * Analyzes patterns to determine construction references
     */
    private function createRequiresEdges(): void
    {
        foreach ($this->constructions as $construction) {
            if (empty($construction->compiledPattern)) {
                continue;
            }

            $pattern = is_string($construction->compiledPattern)
                ? json_decode($construction->compiledPattern, true)
                : $construction->compiledPattern;

            if (! is_array($pattern) || empty($pattern['nodes'])) {
                continue;
            }

            $fromNodeId = "construction_{$construction->idConstruction}";

            // Extract construction references from pattern
            foreach ($pattern['nodes'] as $patternNode) {
                // Look for construction references in pattern
                if (isset($patternNode['type']) && $patternNode['type'] === 'CONSTRUCTION_REF') {
                    $refName = $patternNode['ref'] ?? $patternNode['name'] ?? null;
                    if ($refName) {
                        // Find referenced construction
                        $referencedConstruction = $this->findConstructionByName($refName);
                        if ($referencedConstruction) {
                            $toNodeId = "construction_{$referencedConstruction->idConstruction}";
                            $mandatory = $patternNode['mandatory'] ?? false;

                            $this->edges[] = TypeGraphEdge::requires(
                                $fromNodeId,
                                $toNodeId,
                                mandatory: $mandatory
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Create "inherits" edges (Construction → Construction)
     *
     * Could be extended to support explicit inheritance in construction definitions
     */
    private function createInheritsEdges(): void
    {
        // For now, we can infer inheritance from naming patterns
        // e.g., PRED_SIMPLE inherits from PRED
        foreach ($this->constructions as $construction) {
            $name = $construction->name;

            // Check if name contains underscore (potential inheritance)
            if (str_contains($name, '_')) {
                $parts = explode('_', $name);
                if (count($parts) >= 2) {
                    $parentName = $parts[0]; // e.g., PRED from PRED_SIMPLE

                    // Find parent construction
                    $parentConstruction = $this->findConstructionByName($parentName);
                    if ($parentConstruction && $parentConstruction->idConstruction !== $construction->idConstruction) {
                        $this->edges[] = TypeGraphEdge::inherits(
                            "construction_{$construction->idConstruction}",
                            "construction_{$parentConstruction->idConstruction}"
                        );
                    }
                }
            }
        }
    }

    /**
     * Analyze mandatory elements from construction patterns
     */
    private function analyzeMandatoryElements(): void
    {
        foreach ($this->constructions as $construction) {
            $mandatoryCEs = [];

            // Check if construction has explicit mandatoryElements field
            if (! empty($construction->mandatoryElements)) {
                $mandatoryCEs = is_string($construction->mandatoryElements)
                    ? json_decode($construction->mandatoryElements, true)
                    : $construction->mandatoryElements;
            } else {
                // Analyze pattern for <element> syntax (will be implemented in Phase 2)
                // For now, infer from pattern structure
                $mandatoryCEs = $this->inferMandatoryElements($construction);
            }

            if (! empty($mandatoryCEs)) {
                $this->mandatoryElements[$construction->idConstruction] = $mandatoryCEs;
            }
        }
    }

    /**
     * Infer mandatory elements from pattern structure
     */
    private function inferMandatoryElements(object $construction): array
    {
        $mandatory = [];

        if (empty($construction->compiledPattern)) {
            return $mandatory;
        }

        $pattern = is_string($construction->compiledPattern)
            ? json_decode($construction->compiledPattern, true)
            : $construction->compiledPattern;

        if (! is_array($pattern) || empty($pattern['nodes'])) {
            return $mandatory;
        }

        // Look for nodes marked as mandatory in compiled pattern
        foreach ($pattern['nodes'] as $node) {
            if (isset($node['mandatory']) && $node['mandatory'] === true) {
                // Extract CE label if available
                if (isset($node['ceLabel'])) {
                    $mandatory[] = $node['ceLabel'];
                }
            }
        }

        return $mandatory;
    }

    /**
     * Build CE hierarchy (dependency chains)
     *
     * Example: Head can have Mod, Mod can have Adp
     */
    private function buildCEHierarchy(): void
    {
        // Define common CE dependency patterns
        // These represent typical linguistic attachment patterns
        $this->ceHierarchy = [
            // Phrasal level
            'Head' => ['Mod', 'Adp', 'Clf', 'Idx', 'Conj'],
            'Mod' => ['Adp'],

            // Clausal level
            'Pred' => ['Arg', 'CPP', 'FPM', 'Gen', 'Conj'],
            'Arg' => ['Gen', 'Mod'],

            // Sentential level
            'Main' => ['Adv', 'Rel', 'Comp', 'Dtch', 'Int'],
        ];

        // Could be extended to infer from actual construction patterns
    }

    /**
     * Create fast lookup indexes
     */
    private function createIndexes(): void
    {
        foreach ($this->nodes as $node) {
            if ($node->isConstruction()) {
                $this->constructionIndex[$node->name] = $node;
            } elseif ($node->isCELabel()) {
                $level = $node->getCELevel();
                $key = "{$level}_{$node->name}";
                $this->ceLabelIndex[$key] = $node;
            }
        }
    }

    /**
     * Find construction by name
     */
    private function findConstructionByName(string $name): ?object
    {
        foreach ($this->constructions as $construction) {
            if ($construction->name === $name) {
                return $construction;
            }
        }

        return null;
    }
}
