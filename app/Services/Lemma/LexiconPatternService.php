<?php

namespace App\Services\Lemma;

use App\Services\Trankit\TrankitService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lexicon Pattern Service
 *
 * Manages lemma patterns for both CLI (batch processing) and Web (CRUD operations).
 * Handles pattern storage, retrieval, matching for all lemmas (SWE and MWE).
 */
class LexiconPatternService
{
    protected TrankitService $trankit;

    // Cache for database lookups to prevent memory exhaustion during batch operations
    protected array $udRelationCache = [];

    protected array $lexiconCache = [];

    protected array $udposCache = [];

    public function __construct(TrankitService $trankit)
    {
        $this->trankit = $trankit;
        $this->trankit->init(config('udparser.trankit_url'));
    }

    // =====================================================
    // PUBLIC API - PATTERN STORAGE OPERATIONS
    // =====================================================

    /**
     * Store lemma pattern for a single lemma entry
     *
     * @param  int  $idLemma  Lemma ID (from lemma table)
     * @param  int  $idLanguage  Language ID
     * @return array Result with pattern details
     *
     * @throws \InvalidArgumentException If lemma not found
     * @throws \RuntimeException If pattern extraction fails
     */
    public function storeLemmaPattern(int $idLemma, int $idLanguage): array
    {
        // 1. Get lemma details
        $lemma = DB::table('view_lemma')->where('idLemma', $idLemma)->first();
        if (! $lemma) {
            throw new \InvalidArgumentException("Lemma not found: {$idLemma}");
        }

        // 2. Use lemma name directly as the text to parse
        // For MWE: lemma name is the canonical form (e.g., "added time", "kick ball")
        $lemmaText = $lemma->name;
        if (empty($lemmaText)) {
            throw new \RuntimeException("Empty lemma name for lemma: {$idLemma}");
        }

        // 3. Extract pattern using Trankit
        $pattern = $this->extractPatternFromLemma($lemmaText, $idLanguage);

        // 4. Store pattern in database
        return DB::transaction(function () use ($idLemma, $pattern, $lemma) {
            // Delete existing pattern if any
            $this->deletePatternData($idLemma);

            // Store new pattern
            $idLexiconPattern = $this->storePatternData($idLemma, $pattern);

            return [
                'idLemma' => $idLemma,
                'idLexiconPattern' => $idLexiconPattern,
                'lemmaName' => $lemma->name,
                'nodeCount' => count($pattern['nodes']),
                'edgeCount' => count($pattern['edges']),
                'constraintCount' => count($pattern['constraints'] ?? []),
            ];
        });
    }

    /**
     * Store patterns for multiple lemmas (batch processing)
     *
     * @param  array  $idLemmas  Array of lemma IDs
     * @param  int  $idLanguage  Language ID
     * @param  callable|null  $progressCallback  Callback(processed, total, currentResult)
     * @return array Results summary with success/failed counts
     */
    public function storeLemmaPatternsBatch(array $idLemmas, int $idLanguage, ?callable $progressCallback = null): array
    {
        // Disable query logging to prevent memory exhaustion during batch operations
        DB::connection()->disableQueryLog();

        try {
            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            $total = count($idLemmas);

            foreach ($idLemmas as $index => $idLemma) {
                try {
                    $result = $this->storeLemmaPattern($idLemma, $idLanguage);
                    $results['success']++;

                    if ($progressCallback) {
                        $progressCallback($index + 1, $total, $result);
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][$idLemma] = $e->getMessage();

                    Log::error("Failed to store pattern for lemma {$idLemma}: ".$e->getMessage());
                }

                // Clear caches every 10 items to prevent memory exhaustion
                if (($index + 1) % 10 === 0) {
                    $this->clearCaches();
                }
            }

            return $results;
        } finally {
            // Re-enable query logging and clear caches
            DB::connection()->enableQueryLog();
            $this->clearCaches();
        }
    }

    /**
     * Clear internal caches to free memory
     */
    public function clearCaches(): void
    {
        $this->udRelationCache = [];
        $this->lexiconCache = [];
        $this->udposCache = [];
    }

    /**
     * Delete lemma pattern
     *
     * @param  int  $idLemma  Lemma ID
     * @return bool Success status
     */
    public function deleteLemmaPattern(int $idLemma): bool
    {
        return DB::transaction(function () use ($idLemma) {
            $this->deletePatternData($idLemma);

            return true;
        });
    }

    /**
     * Update lemma pattern (regenerate from scratch)
     *
     * @param  int  $idLemma  Lemma ID
     * @param  int  $idLanguage  Language ID
     * @return array Result with pattern details
     */
    public function updateLemmaPattern(int $idLemma, int $idLanguage): array
    {
        return $this->storeLemmaPattern($idLemma, $idLanguage);
    }

    // =====================================================
    // PUBLIC API - PATTERN RETRIEVAL OPERATIONS
    // =====================================================

    /**
     * Get pattern for a specific lemma with full details
     *
     * @param  int  $idLemma  Lemma ID
     * @return array|null Pattern data with nodes, edges, constraints or null if not found
     */
    public function getLemmaPattern(int $idLemma): ?array
    {
        $pattern = DB::table('lexicon_pattern')
            ->where('idLemma', $idLemma)
            ->first();

        if (! $pattern) {
            return null;
        }

        // Load nodes
        $nodes = DB::table('lexicon_pattern_node')
            ->where('idLexiconPattern', $pattern->idLexiconPattern)
            ->orderBy('position')
            ->get()
            ->map(fn ($node) => (array) $node)
            ->toArray();

        // Load edges with position mapping
        $edges = DB::table('lexicon_pattern_edge as e')
            ->join('lexicon_pattern_node as head', 'e.idNodeHead', '=', 'head.idLexiconPatternNode')
            ->join('lexicon_pattern_node as dep', 'e.idNodeDependent', '=', 'dep.idLexiconPatternNode')
            ->where('e.idLexiconPattern', $pattern->idLexiconPattern)
            ->select(
                'e.*',
                'head.position as head_position',
                'dep.position as dependent_position'
            )
            ->get()
            ->map(fn ($edge) => (array) $edge)
            ->toArray();

        // Load constraints
        $constraints = DB::table('lexicon_pattern_constraint')
            ->where('idLexiconPattern', $pattern->idLexiconPattern)
            ->get()
            ->map(fn ($con) => (array) $con)
            ->toArray();

        return [
            'pattern' => (array) $pattern,
            'nodes' => $nodes,
            'edges' => $edges,
            'constraints' => $constraints,
        ];
    }

    /**
     * Check if lemma has pattern stored
     *
     * @param  int  $idLemma  Lemma ID
     * @return bool True if pattern exists
     */
    public function hasPattern(int $idLemma): bool
    {
        return DB::table('lexicon_pattern')
            ->where('idLemma', $idLemma)
            ->exists();
    }

    /**
     * Get all lemmas with patterns
     *
     * @param  int|null  $idLanguage  Optional language filter
     * @return Collection Collection of lemmas with pattern info
     */
    public function getAllLemmasWithPatterns(?int $idLanguage = null): Collection
    {
        $query = DB::table('view_lemma as l')
            ->join('lexicon_pattern as p', 'l.idLexicon', '=', 'p.idLexicon')
            ->select('l.*', 'p.idLexiconPattern', 'p.patternType', 'p.created_at as pattern_created_at');

        if ($idLanguage !== null) {
            $query->where('l.idLanguage', $idLanguage);
        }

        return $query->get();
    }

    // =====================================================
    // PUBLIC API - PATTERN MATCHING OPERATIONS
    // =====================================================

    /**
     * Find lemma occurrences in parsed sentence tokens
     *
     * @param  array  $sentenceTokens  Parsed sentence tokens
     * @param  string|null  $sentenceId  Optional sentence identifier
     * @return array Array of detected lemma occurrences
     */
    public function findLemmaOccurrences(array $sentenceTokens, ?string $sentenceId = null): array
    {
        $occurrences = [];

        // Get all patterns from database
        $patterns = $this->loadAllPatterns();

        foreach ($patterns as $pattern) {
            // Match pattern against sentence tokens
            $matches = $this->findPatternMatches($sentenceTokens, $pattern, $sentenceId);
            $occurrences = array_merge($occurrences, $matches);
        }

        return $occurrences;
    }

    /**
     * Parse sentence and find lemma occurrences (convenience method)
     *
     * @param  string  $sentence  Sentence text
     * @param  int  $idLanguage  Language ID
     * @return array Array of detected lemma occurrences
     */
    public function parseSentenceAndFindLemmas(string $sentence, int $idLanguage): array
    {
        // Parse sentence using Trankit
        $trankitOutput = $this->trankit->parseSentenceRawTokens($sentence, $idLanguage);
        $tokens = $this->parseTrankitOutput($trankitOutput);

        // Find lemma occurrences
        return $this->findLemmaOccurrences($tokens);
    }

    // =====================================================
    // PUBLIC API - UTILITY OPERATIONS
    // =====================================================

    /**
     * Get pattern statistics
     *
     * @param  int|null  $idLanguage  Optional language filter
     * @return array Statistics about patterns
     */
    public function getPatternStats(?int $idLanguage = null): array
    {
        $query = DB::table('lexicon_pattern as p')
            ->join('view_lemma as l', 'p.idLexicon', '=', 'l.idLexicon');

        if ($idLanguage !== null) {
            $query->where('l.idLanguage', $idLanguage);
        }

        $totalPatterns = $query->count();

        // Count SWE vs MWE patterns
        $sweCount = DB::table('lexicon_pattern as p')
            ->leftJoin('lexicon_pattern_node as n', 'p.idLexiconPattern', '=', 'n.idLexiconPattern')
            ->select('p.idLexiconPattern')
            ->groupBy('p.idLexiconPattern')
            ->havingRaw('COUNT(n.idLexiconPatternNode) = 1')
            ->count();

        return [
            'total_patterns' => $totalPatterns,
            'swe_patterns' => $sweCount,
            'mwe_patterns' => $totalPatterns - $sweCount,
        ];
    }

    /**
     * Validate lemma can have pattern stored
     *
     * @param  int  $idLemma  Lemma ID
     * @return bool True if valid
     */
    public function validateLemmaForPattern(int $idLemma): bool
    {
        // Check lemma exists
        $lemmaExists = DB::table('view_lemma')->where('idLemma', $idLemma)->exists();
        if (! $lemmaExists) {
            return false;
        }

        // Get idLexicon from lemma
        $lemma = DB::table('view_lemma')->where('idLemma', $idLemma)->first();

        // Check has expressions
        $hasExpressions = DB::table('view_lexicon_expression')->where('idLemma', $lemma->idLexicon)->exists();

        return $hasExpressions;
    }

    // =====================================================
    // PROTECTED HELPER METHODS
    // =====================================================


    /**
     * Extract pattern from lemma text using Trankit
     *
     * @param  string  $lemmaText  Lemma text to parse
     * @param  int  $idLanguage  Language ID
     * @return array Pattern structure with nodes, edges, constraints
     */
    protected function extractPatternFromLemma(string $lemmaText, int $idLanguage): array
    {
        $trankitOutput = $this->trankit->parseSentenceRawTokens($lemmaText, $idLanguage);
        $tokens = $this->parseTrankitOutput($trankitOutput);

        return $this->buildFullTreePattern($tokens);
    }

    /**
     * Store pattern data in database
     *
     * @param  int  $idLemma  Lemma ID
     * @param  array  $pattern  Pattern structure
     * @return int Created pattern ID
     */
    protected function storePatternData(int $idLemma, array $pattern): int
    {
        // Create pattern entry
        $idLexiconPattern = DB::table('lexicon_pattern')->insertGetId([
            'idLemma' => $idLemma,
            'patternType' => 'canonical',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Store nodes
        $nodeMapping = [];
        foreach ($pattern['nodes'] as $node) {
            $idNode = DB::table('lexicon_pattern_node')->insertGetId([
                'idLexiconPattern' => $idLexiconPattern,
                'position' => $node['position'],
                'idLexicon' => $node['idLexicon'] ?? null,
                'idUDPOS' => $node['idUDPOS'] ?? null,
                'isRoot' => $node['is_root'],
                'isRequired' => $node['is_required'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $nodeMapping[$node['position']] = $idNode;
        }

        // Store edges
        foreach ($pattern['edges'] as $edge) {
            DB::table('lexicon_pattern_edge')->insert([
                'idLexiconPattern' => $idLexiconPattern,
                'idNodeHead' => $nodeMapping[$edge['head_position']],
                'idNodeDependent' => $nodeMapping[$edge['dependent_position']],
                'idUDRelation' => $edge['idUDRelation'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Store constraints
        if (isset($pattern['constraints'])) {
            foreach ($pattern['constraints'] as $constraint) {
                DB::table('lexicon_pattern_constraint')->insert([
                    'idLexiconPattern' => $idLexiconPattern,
                    'constraintType' => $constraint['type'],
                    'constraintValue' => $constraint['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $idLexiconPattern;
    }

    /**
     * Delete pattern data from database
     *
     * @param  int  $idLemma  Lemma ID
     */
    protected function deletePatternData(int $idLemma): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $patterns = DB::table('lexicon_pattern')
            ->where('idLemma', $idLemma)
            ->pluck('idLexiconPattern');

        foreach ($patterns as $idPattern) {
            DB::table('lexicon_pattern_constraint')->where('idLexiconPattern', $idPattern)->delete();
            DB::table('lexicon_pattern_edge')->where('idLexiconPattern', $idPattern)->delete();
            DB::table('lexicon_pattern_node')->where('idLexiconPattern', $idPattern)->delete();
            DB::table('lexicon_pattern')->where('idLexiconPattern', $idPattern)->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Build FULL tree pattern from parsed tokens
     * Stores ALL nodes and ALL edges (complete dependency structure)
     *
     * @param  array  $tokens  Parsed tokens
     * @return array Pattern structure with complete tree
     */
    protected function buildFullTreePattern(array $tokens): array
    {
        $pattern = [
            'nodes' => [],
            'edges' => [],
            'constraints' => [],
        ];

        // Find root token
        $rootToken = $this->findRootToken($tokens);
        if (! $rootToken) {
            throw new \RuntimeException('No root token found in parsed lemma');
        }

        // Create nodes for all tokens
        foreach ($tokens as $index => $token) {
            $isRoot = ($token['id'] == $rootToken['id']);

            $pattern['nodes'][] = [
                'position' => $index,
                'idLexicon' => $this->getLexiconIdByForm(strtolower($token['form'])),
                'idUDPOS' => $this->getUDPOSIdByName($token['upos']),
                'is_root' => $isRoot,
                'is_required' => true, // All nodes required for lemmas
            ];
        }

        // Create edges for ALL dependencies (complete tree)
        foreach ($tokens as $token) {
            if ($token['head'] == 0) {
                continue; // Skip root's head (which is 0)
            }

            $headPosition = $this->findTokenPosition($tokens, $token['head']);
            $depPosition = $this->findTokenPosition($tokens, $token['id']);

            if ($headPosition !== null && $depPosition !== null) {
                $pattern['edges'][] = [
                    'head_position' => $headPosition,
                    'dependent_position' => $depPosition,
                    'idUDRelation' => $this->getUDRelationIdByName($token['deprel']),
                ];
            }
        }

        // Add constraints for fixed expressions
        if ($this->isFixedExpression($tokens)) {
            $pattern['constraints'][] = [
                'type' => 'word_order',
                'value' => 'strict',
            ];
        }

        return $pattern;
    }

    /**
     * Parse Trankit output into structured tokens
     *
     * @param  array  $trankitOutput  Trankit output array
     * @return array Array of token arrays with idUDPOS and idUDRelation
     */
    public function parseTrankitOutput(array $trankitOutput): array
    {
        $tokens = [];

        foreach ($trankitOutput as $node) {
            $upos = $node['pos'];
            $deprel = $node['rel'];

            $tokens[] = [
                'id' => (int) $node['id'],
                'form' => $node['word'],
                'lemma' => $node['lemma'] ?? $node['word'],
                'upos' => $upos,
                'idUDPOS' => $this->getUDPOSIdByName($upos),
                'xpos' => '_',
                'feats' => $node['morph'] ?? [],
                'head' => (int) $node['parent'],
                'deprel' => $deprel,
                'idUDRelation' => $this->getUDRelationIdByName($deprel),
                'deps' => '_',
                'misc' => '_',
            ];
        }

        return $tokens;
    }

    /**
     * Load all patterns from database with full tree structure
     *
     * @return array Array of patterns with nodes and edges
     */
    protected function loadAllPatterns(): array
    {
        $lexicons = DB::table('view_lemma as l')
            ->join('lexicon_pattern as p', 'l.idLexicon', '=', 'p.idLexicon')
            ->select('l.*', 'p.idLexiconPattern', 'p.patternType')
            ->get();

        $result = [];
        foreach ($lexicons as $lexicon) {
            $patternData = $this->getLemmaPattern($lexicon->idLemma);
            if ($patternData) {
                $result[] = array_merge((array) $lexicon, $patternData);
            }
        }

        return $result;
    }

    /**
     * Find pattern matches in sentence tokens
     *
     * @param  array  $sentenceTokens  Sentence tokens
     * @param  array  $pattern  Pattern with nodes and edges
     * @param  string|null  $sentenceId  Sentence ID
     * @return array Matches
     */
    protected function findPatternMatches(array $sentenceTokens, array $pattern, ?string $sentenceId = null): array
    {
        $matches = [];

        // Find root node candidates
        $rootNode = $this->getRootNode($pattern['nodes']);
        if (! $rootNode) {
            return $matches;
        }

        $rootCandidates = $this->findNodeCandidates($sentenceTokens, $rootNode);

        foreach ($rootCandidates as $rootToken) {
            $match = $this->tryMatchFullTree($rootToken, $pattern, $sentenceTokens);

            if ($match !== null) {
                $matches[] = [
                    'sentence_id' => $sentenceId ?? uniqid('sent_'),
                    'idLexicon' => $pattern['idLexicon'],
                    'idLexiconPattern' => $pattern['idLexiconPattern'],
                    'lemma_text' => $pattern['name'],
                    'token_indices' => $match['token_indices'],
                    'matched_nodes' => $match['node_mapping'],
                    'confidence' => $match['confidence'],
                ];
            }
        }

        return $matches;
    }

    /**
     * Try to match the complete dependency tree structure
     *
     * @param  array  $rootToken  Root candidate token
     * @param  array  $pattern  Full pattern with nodes and edges
     * @param  array  $sentenceTokens  All sentence tokens
     * @return array|null Match data or null
     */
    protected function tryMatchFullTree(array $rootToken, array $pattern, array $sentenceTokens): ?array
    {
        // Build sentence dependency graph for quick lookup
        $sentenceGraph = $this->buildDependencyGraph($sentenceTokens);

        // Try to map pattern nodes to sentence tokens
        $nodeMapping = []; // pattern position -> sentence token id
        $rootNode = $this->getRootNode($pattern['nodes']);
        $nodeMapping[$rootNode['position']] = $rootToken['id'];

        // Recursively match all nodes using BFS
        $toVisit = [$rootNode['position']];
        $visited = [];
        $requiredMatches = 0;
        $totalRequired = 0;

        while (! empty($toVisit)) {
            $currentPos = array_shift($toVisit);
            if (in_array($currentPos, $visited)) {
                continue;
            }
            $visited[] = $currentPos;

            $currentNode = $this->getNodeByPosition($pattern['nodes'], $currentPos);
            if ($currentNode['isRequired']) {
                $totalRequired++;
                if (isset($nodeMapping[$currentPos])) {
                    $requiredMatches++;
                }
            }

            // Find all edges where current node is the head
            $outgoingEdges = $this->getOutgoingEdges($pattern['edges'], $currentPos);

            foreach ($outgoingEdges as $edge) {
                $dependentNode = $this->getNodeByPosition($pattern['nodes'], $edge['dependent_position']);

                // Try to find matching token in sentence
                if (isset($nodeMapping[$currentPos])) {
                    $headTokenId = $nodeMapping[$currentPos];
                    $matchedDependent = $this->findMatchingDependent(
                        $headTokenId,
                        $dependentNode,
                        $edge['idUDRelation'],
                        $sentenceTokens,
                        $sentenceGraph
                    );

                    if ($matchedDependent !== null) {
                        $nodeMapping[$edge['dependent_position']] = $matchedDependent['id'];
                        $toVisit[] = $edge['dependent_position'];
                    } elseif ($dependentNode['isRequired']) {
                        // Required node not found
                        return null;
                    }
                }
            }
        }

        // Verify ALL edges match (complete tree structure)
        $allEdgesMatch = $this->verifyAllEdges($pattern['edges'], $nodeMapping, $sentenceGraph);
        if (! $allEdgesMatch) {
            return null;
        }

        $confidence = $totalRequired > 0 ? $requiredMatches / $totalRequired : 0;

        if ($confidence >= 0.8) {
            return [
                'token_indices' => array_values($nodeMapping),
                'node_mapping' => $nodeMapping,
                'confidence' => $confidence,
            ];
        }

        return null;
    }

    // =====================================================
    // PRIVATE HELPER METHODS
    // =====================================================

    protected function findRootToken(array $tokens): ?array
    {
        foreach ($tokens as $token) {
            if ($token['deprel'] === 'root' || $token['head'] === 0) {
                return $token;
            }
        }

        return $tokens[0] ?? null;
    }

    protected function findTokenPosition(array $tokens, int $tokenId): ?int
    {
        foreach ($tokens as $index => $token) {
            if ($token['id'] == $tokenId) {
                return $index;
            }
        }

        return null;
    }

    protected function getLexiconIdByForm(string $form): ?int
    {
        if (! isset($this->lexiconCache[$form])) {
            $this->lexiconCache[$form] = DB::table('lexicon')
                ->where('form', $form)
                ->value('idLexicon');
        }

        return $this->lexiconCache[$form];
    }

    protected function getUDPOSIdByName(string $pos): ?int
    {
        if (! isset($this->udposCache[$pos])) {
            $this->udposCache[$pos] = DB::table('udpos')
                ->where('POS', $pos)
                ->value('idUDPOS');
        }

        return $this->udposCache[$pos];
    }

    protected function getUDRelationIdByName(string $relation): ?int
    {
        // Strip UD relation subtypes (e.g., "flat:foreign" -> "flat", "obl:tmod" -> "obl")
        $baseRelation = strpos($relation, ':') !== false
            ? substr($relation, 0, strpos($relation, ':'))
            : $relation;

        if (! isset($this->udRelationCache[$baseRelation])) {
            $this->udRelationCache[$baseRelation] = DB::table('udrelation')
                ->where('info', $baseRelation)
                ->value('idUDRelation');

            // Log if relation not found
            if ($this->udRelationCache[$baseRelation] === null) {
                Log::warning("UD Relation not found in database: '{$baseRelation}' (original: '{$relation}')");
            }
        }

        return $this->udRelationCache[$baseRelation];
    }

    protected function isCoreElement(array $token): bool
    {
        $coreRels = ['nsubj', 'obj', 'iobj', 'csubj', 'ccomp', 'xcomp', 'obl', 'aux', 'cop', 'case', 'mark', 'fixed', 'flat', 'compound'];

        return in_array($token['deprel'], $coreRels);
    }

    protected function isFixedExpression(array $tokens): bool
    {
        foreach ($tokens as $token) {
            if ($token['deprel'] === 'fixed') {
                return true;
            }
        }

        return false;
    }

    protected function getRootNode(array $nodes): ?array
    {
        foreach ($nodes as $node) {
            if ($node['isRoot']) {
                return $node;
            }
        }

        return null;
    }

    protected function getNodeByPosition(array $nodes, int $position): ?array
    {
        foreach ($nodes as $node) {
            if ($node['position'] == $position) {
                return $node;
            }
        }

        return null;
    }

    protected function getOutgoingEdges(array $edges, int $headPosition): array
    {
        return array_filter($edges, fn ($edge) => $edge['head_position'] == $headPosition);
    }

    protected function findNodeCandidates(array $sentenceTokens, array $patternNode): array
    {
        $candidates = [];

        foreach ($sentenceTokens as $token) {
            $lemmaMatch = true;
            $uposMatch = true;

            if ($patternNode['idLexicon'] !== null) {
                $tokenLexiconId = $this->getLexiconIdByForm($token['lemma']);
                $lemmaMatch = $tokenLexiconId == $patternNode['idLexicon'];
            }

            if ($patternNode['idUDPOS'] !== null) {
                $uposMatch = $token['idUDPOS'] == $patternNode['idUDPOS'];
            }

            if ($lemmaMatch && $uposMatch) {
                $candidates[] = $token;
            }
        }

        return $candidates;
    }

    protected function buildDependencyGraph(array $tokens): array
    {
        $graph = [];

        foreach ($tokens as $token) {
            $headId = $token['head'];
            if ($headId > 0) {
                if (! isset($graph[$headId])) {
                    $graph[$headId] = [];
                }
                $graph[$headId][] = $token;
            }
        }

        return $graph;
    }

    protected function findMatchingDependent(int $headTokenId, array $patternNode, int $idUDRelation, array $sentenceTokens, array $sentenceGraph): ?array
    {
        if (! isset($sentenceGraph[$headTokenId])) {
            return null;
        }

        foreach ($sentenceGraph[$headTokenId] as $dependent) {
            // Check relation
            if ($dependent['idUDRelation'] != $idUDRelation) {
                continue;
            }

            // Check lemma if specified
            if ($patternNode['idLexicon'] !== null) {
                $depLexiconId = $this->getLexiconIdByForm($dependent['lemma']);
                if ($depLexiconId != $patternNode['idLexicon']) {
                    continue;
                }
            }

            // Check UPOS if specified
            if ($patternNode['idUDPOS'] !== null && $dependent['idUDPOS'] != $patternNode['idUDPOS']) {
                continue;
            }

            return $dependent;
        }

        return null;
    }

    protected function verifyAllEdges(array $patternEdges, array $nodeMapping, array $sentenceGraph): bool
    {
        foreach ($patternEdges as $edge) {
            $headPos = $edge['head_position'];
            $depPos = $edge['dependent_position'];

            // Check if both nodes are mapped
            if (! isset($nodeMapping[$headPos]) || ! isset($nodeMapping[$depPos])) {
                continue; // Skip if optional node
            }

            $headTokenId = $nodeMapping[$headPos];
            $depTokenId = $nodeMapping[$depPos];

            // Verify this edge exists in sentence with same relation
            $edgeExists = false;
            if (isset($sentenceGraph[$headTokenId])) {
                foreach ($sentenceGraph[$headTokenId] as $dep) {
                    if ($dep['id'] == $depTokenId && $dep['idUDRelation'] == $edge['idUDRelation']) {
                        $edgeExists = true;
                        break;
                    }
                }
            }

            if (! $edgeExists) {
                return false;
            }
        }

        return true;
    }
}
