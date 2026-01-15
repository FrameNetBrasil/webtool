<?php

namespace App\Services\Parser;

use App\Data\Parser\ConstructionMatch;
use App\Repositories\Parser\Construction;
use Illuminate\Support\Facades\Cache;

/**
 * Construction Detection Service
 *
 * Orchestrates BNF construction detection in token sequences.
 * Handles loading constructions, matching, caching, semantic calculation, and priority resolution.
 */
class ConstructionService
{
    private BNFMatcher $matcher;

    private PatternCompiler $compiler;

    private SemanticCalculator $semanticCalculator;

    private bool $cacheEnabled = true;

    private int $cacheTTL = 3600; // 1 hour

    public function __construct(
        ?BNFMatcher $matcher = null,
        ?PatternCompiler $compiler = null,
        ?SemanticCalculator $semanticCalculator = null
    ) {
        $this->matcher = $matcher ?? new BNFMatcher;
        $this->compiler = $compiler ?? new PatternCompiler;
        $this->semanticCalculator = $semanticCalculator ?? new SemanticCalculator;
        // Cache config loaded on-demand in getCompiledGraph()
    }

    /**
     * Detect all constructions in token sequence
     *
     * @param  array  $tokens  Array of PhrasalCENode objects
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @return array Array of ConstructionMatch objects
     */
    public function detectAll(array $tokens, int $idGrammarGraph): array
    {
        // Load enabled constructions ordered by priority
        $constructions = Construction::getEnabled($idGrammarGraph);

        if (empty($constructions)) {
            return [];
        }

        $allMatches = [];

        // Try each construction (in priority order)
        foreach ($constructions as $construction) {
            $matches = $this->detectConstruction($tokens, $construction);
            $allMatches = array_merge($allMatches, $matches);
        }

        // Resolve overlapping matches (higher priority wins)
        $resolvedMatches = $this->resolveOverlaps($allMatches);

        return $resolvedMatches;
    }

    /**
     * Detect specific construction in tokens
     *
     * @param  array  $tokens  Array of PhrasalCENode objects
     * @param  object  $construction  Construction from database
     * @return array Array of ConstructionMatch objects
     */
    public function detectConstruction(array $tokens, object $construction): array
    {
        // Get compiled graph (cached)
        $graph = $this->getCompiledGraph($construction);

        if (! $graph) {
            return [];
        }

        // Match all occurrences
        $matchResults = $this->matcher->matchAll($graph, $tokens);

        // Convert to ConstructionMatch objects and apply semantics
        $matches = [];
        foreach ($matchResults as $matchResult) {
            $match = ConstructionMatch::fromMatcherResult(
                idConstruction: $construction->idConstruction,
                name: $construction->name,
                matchResult: $matchResult,
                startPosition: $matchResult['startPosition'],
                semanticType: $construction->semanticType
            );

            // Apply semantic calculation if configured
            $semantics = Construction::getSemantics($construction);
            if ($semantics) {
                $match = $this->semanticCalculator->calculate($match, $semantics);
            }

            $matches[] = $match;
        }

        return $matches;
    }

    /**
     * Test pattern against sentence (for debugging/UI)
     *
     * @param  string  $pattern  BNF pattern string
     * @param  array  $tokens  Array of PhrasalCENode objects
     * @return array Match results with debugging info
     */
    public function testPattern(string $pattern, array $tokens): array
    {
        try {
            // Compile pattern
            $graph = $this->compiler->compile($pattern);

            // Validate
            $validation = $this->compiler->validate($pattern);

            // Attempt matching
            $matches = $this->matcher->matchAll($graph, $tokens);

            return [
                'success' => true,
                'valid' => $validation['valid'],
                'errors' => $validation['errors'],
                'graph' => $graph,
                'matches' => $matches,
                'matchCount' => count($matches),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'valid' => false,
                'errors' => [$e->getMessage()],
                'graph' => null,
                'matches' => [],
                'matchCount' => 0,
            ];
        }
    }

    /**
     * Compile pattern and store in database
     *
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @param  string  $name  Construction name
     * @param  string  $pattern  BNF pattern string
     * @param  array  $metadata  Additional metadata (description, semanticType, etc.)
     * @return int Construction ID
     *
     * @throws \Exception If pattern is invalid
     */
    public function compileAndStore(
        int $idGrammarGraph,
        string $name,
        string $pattern,
        array $metadata = []
    ): int {
        // Validate pattern
        $validation = $this->compiler->validate($pattern);
        if (! $validation['valid']) {
            throw new \Exception('Invalid pattern: '.implode(', ', $validation['errors']));
        }

        // Compile to graph
        $graph = $this->compiler->compile($pattern);

        // Prepare data
        $data = array_merge([
            'idGrammarGraph' => $idGrammarGraph,
            'name' => $name,
            'pattern' => $pattern,
            'compiledGraph' => $graph,
            'semanticType' => 'Head',
            'priority' => 0,
            'enabled' => true,
        ], $metadata);

        // Check if exists
        if (Construction::exists($idGrammarGraph, $name)) {
            throw new \Exception("Construction '$name' already exists for this grammar");
        }

        // Create
        $idConstruction = Construction::create($data);

        // Clear cache
        $this->clearConstructionCache($idConstruction);

        return $idConstruction;
    }

    /**
     * Recompile an existing construction
     *
     * @throws \Exception If pattern is invalid
     */
    public function recompile(int $idConstruction): void
    {
        $construction = Construction::byId($idConstruction);

        // Validate current pattern
        $validation = $this->compiler->validate($construction->pattern);
        if (! $validation['valid']) {
            throw new \Exception('Cannot recompile invalid pattern: '.implode(', ', $validation['errors']));
        }

        // Recompile
        $graph = $this->compiler->compile($construction->pattern);

        // Update
        Construction::update($idConstruction, [
            'compiledGraph' => $graph,
        ]);

        // Clear cache
        $this->clearConstructionCache($idConstruction);
    }

    /**
     * Get compiled graph (with caching)
     */
    private function getCompiledGraph(object $construction): ?array
    {
        if (! $this->cacheEnabled) {
            return Construction::getCompiledGraph($construction);
        }

        $cacheKey = "construction.graph.{$construction->idConstruction}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($construction) {
            return Construction::getCompiledGraph($construction);
        });
    }

    /**
     * Clear cached graph for construction
     */
    private function clearConstructionCache(int $idConstruction): void
    {
        if ($this->cacheEnabled) {
            Cache::forget("construction.graph.{$idConstruction}");
        }
    }

    /**
     * Resolve overlapping matches (priority-based)
     *
     * When matches overlap, keep the one with:
     * 1. Higher priority (from construction)
     * 2. Longer match (if same priority)
     * 3. Earlier position (if same priority and length)
     */
    private function resolveOverlaps(array $matches): array
    {
        if (count($matches) <= 1) {
            return $matches;
        }

        // Sort by priority (higher first), then length (longer first), then position (earlier first)
        usort($matches, function ($a, $b) {
            // Note: Priority comes from construction, not stored in match
            // For now, sort by length and position
            $lenDiff = $b->getLength() - $a->getLength();
            if ($lenDiff !== 0) {
                return $lenDiff;
            }

            return $a->startPosition - $b->startPosition;
        });

        // Keep non-overlapping matches
        $resolved = [];
        $occupiedPositions = [];

        foreach ($matches as $match) {
            $overlaps = false;

            // Check if this match overlaps with any accepted match
            for ($pos = $match->startPosition; $pos < $match->endPosition; $pos++) {
                if (isset($occupiedPositions[$pos])) {
                    $overlaps = true;
                    break;
                }
            }

            if (! $overlaps) {
                // Accept this match
                $resolved[] = $match;

                // Mark positions as occupied
                for ($pos = $match->startPosition; $pos < $match->endPosition; $pos++) {
                    $occupiedPositions[$pos] = true;
                }
            }
        }

        // Re-sort by position for output
        usort($resolved, fn ($a, $b) => $a->startPosition - $b->startPosition);

        return $resolved;
    }

    /**
     * Set cache configuration
     */
    public function setCacheConfig(bool $enabled, int $ttl = 3600): void
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTTL = $ttl;
    }

    /**
     * Get construction statistics
     */
    public function getStats(int $idGrammarGraph): array
    {
        return [
            'total' => Construction::count($idGrammarGraph),
            'enabled' => Construction::countEnabled($idGrammarGraph),
            'names' => Construction::listNames($idGrammarGraph),
        ];
    }
}
