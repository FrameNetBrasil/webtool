<?php

namespace App\Services\Parser;

use App\Models\Parser\PhrasalCENode;
use App\Repositories\Parser\MWE;
use App\Repositories\Parser\ParseNode;

/**
 * Transcription Stage: Lexical Assembly
 *
 * Transforms UD tokens into stable lexical units with morphological features.
 * This is Stage 1 of the three-stage parsing framework (Transcription → Translation → Folding).
 *
 * V3 Enhancement: Three-layer detection priority:
 * 1. Simple MWEs (highest priority) - Fixed sequences (lexicalized expressions)
 * 2. Variable MWEs - Patterns with slots (productive patterns)
 * 3. BNF Constructions (lowest priority) - Complex patterns with semantics
 *
 * Rationale: Simple and variable MWEs can be components of BNF constructions,
 * so they must be identified first before construction-level pattern matching.
 *
 * Biological Analogy: DNA → mRNA (Transcription)
 * - Resolves word types (E/R/A/F)
 * - Assembles multi-word expressions (MWEs) via prefix activation or BNF matching
 * - Extracts and stores morphological features from UD
 * - Quality control: garbage collects incomplete units
 */
class TranscriptionService
{
    private GrammarGraphService $grammarService;

    private MWEService $mweService;

    private LemmaResolverService $lemmaResolver;

    private ConstructionService $constructionService;

    public function __construct(
        GrammarGraphService $grammarService,
        MWEService $mweService,
        LemmaResolverService $lemmaResolver,
        ?ConstructionService $constructionService = null
    ) {
        $this->grammarService = $grammarService;
        $this->mweService = $mweService;
        $this->lemmaResolver = $lemmaResolver;
        $this->constructionService = $constructionService ?? new ConstructionService;
    }

    /**
     * Transcribe UD tokens into lexical units with features
     *
     * @param  array  $tokens  UD tokens from TrankitService
     * @param  int  $idParserGraph  Parse graph ID
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @param  int  $idLanguage  Language ID
     * @return array Array of created node IDs
     */
    public function transcribe(
        array $tokens,
        int $idParserGraph,
        int $idGrammarGraph,
        int $idLanguage
    ): array {
        $createdNodes = [];

        if (config('parser.logging.logStages', false)) {
            logger()->info('Transcription Stage: Starting', [
                'idParserGraph' => $idParserGraph,
                'tokenCount' => count($tokens),
            ]);
        }

        // Process each token
        foreach ($tokens as $token) {
            $nodeId = $this->processToken(
                token: $token,
                idParserGraph: $idParserGraph,
                idGrammarGraph: $idGrammarGraph,
                idLanguage: $idLanguage
            );

            if ($nodeId) {
                $createdNodes[] = $nodeId;
            }

            // Check MWE prefixes for activation
            $this->checkMWEPrefixes(
                word: $token['word'],
                idParserGraph: $idParserGraph,
                position: $token['id']
            );
        }

        // Quality control: garbage collect incomplete MWEs
        if (config('parser.garbageCollection.enabled', true)) {
            $this->garbageCollectIncompleteMWEs($idParserGraph);
        }

        if (config('parser.logging.logStages', false)) {
            logger()->info('Transcription Stage: Complete', [
                'createdNodes' => count($createdNodes),
            ]);
        }

        return $createdNodes;
    }

    /**
     * Transcribe with V3 Construction Detection (NEW)
     *
     * Three-layer detection approach (in priority order):
     * 1. Simple MWEs (highest priority) - Fixed word sequences (lexicalized expressions)
     * 2. Variable MWEs - Patterns with POS/CE slots (productive patterns)
     * 3. BNF Constructions (lowest priority) - Recursive patterns with semantics
     *
     * Linguistic Rationale: Bottom-up assembly strategy
     * - Simple MWEs are atomic lexicalized units and must be identified first
     * - Variable MWEs are productive patterns that may contain simple MWEs
     * - BNF Constructions are complex patterns that may contain both MWE types
     *
     * Example: "a não ser que" (simple MWE) should be recognized as a unit
     * before any variable pattern like "[PREP] [NEG] [VERB] que" can match it,
     * and before construction patterns like "CONDITIONAL → ... que ..." apply.
     *
     * @param  array  $tokens  UD tokens from TrankitService
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @param  int  $idLanguage  Language ID
     * @return array Array of PhrasalCENode objects
     */
    public function transcribeV3(
        array $tokens,
        int $idGrammarGraph,
        int $idLanguage
    ): array {
        if ($this->getConfig('parser.logging.logStages', false)) {
            logger()->info('Transcription V3: Starting', [
                'tokenCount' => count($tokens),
                'idGrammarGraph' => $idGrammarGraph,
            ]);
        }

        // Convert UD tokens to PhrasalCENodes first
        $nodes = $this->createPhrasalNodes($tokens, $idLanguage);

        if ($this->getConfig('parser.logging.logStages', false)) {
            logger()->info('Transcription V3: Created phrasal nodes', [
                'nodeCount' => count($nodes),
            ]);
        }

        // Layer 1: Simple MWE Detection (highest priority - lexicalized expressions)
        // Fixed sequences are identified first as they are atomic lexical units
        $simpleMWEs = $this->mweService->detectSimpleMWEs($nodes, $idGrammarGraph);
        $nodes = $this->applyMWEMatches($nodes, $simpleMWEs);

        if ($this->getConfig('parser.logging.logStages', false)) {
            logger()->info('Transcription V3: Simple MWE matches applied', [
                'simpleMWEMatches' => count($simpleMWEs),
                'remainingNodes' => count($nodes),
            ]);
        }

        // Layer 2: Variable MWE Detection (productive patterns with slots)
        // Run after simple MWEs to avoid breaking up lexicalized expressions
        $variableMWEs = $this->mweService->detectVariableMWEs($nodes, $idGrammarGraph);
        $nodes = $this->applyMWEMatches($nodes, $variableMWEs);

        if ($this->getConfig('parser.logging.logStages', false)) {
            logger()->info('Transcription V3: Variable MWE matches applied', [
                'variableMWEMatches' => count($variableMWEs),
                'remainingNodes' => count($nodes),
            ]);
        }

        // Layer 3: BNF Construction Detection (lowest priority - complex patterns)
        // Run last because constructions may contain simple/variable MWEs as components
        $constructionMatches = $this->constructionService->detectAll($nodes, $idGrammarGraph);
        $nodes = $this->applyConstructionMatches($nodes, $constructionMatches);

        if ($this->getConfig('parser.logging.logStages', false)) {
            logger()->info('Transcription V3: Complete', [
                'finalNodeCount' => count($nodes),
                'constructionMatches' => count($constructionMatches),
                'totalMWEMatches' => count($simpleMWEs) + count($variableMWEs),
            ]);
        }

        return $nodes;
    }

    /**
     * Create PhrasalCENode objects from UD tokens
     */
    private function createPhrasalNodes(array $tokens, int $idLanguage): array
    {
        $nodes = [];

        foreach ($tokens as $token) {
            // Resolve lemma ID for database reference
            $lemma = $token['lemma'] ?? $token['word'];
            $pos = $token['pos'] ?? 'X';
            $idLemma = $this->lemmaResolver->getOrCreateLemma($lemma, $idLanguage, $pos);

            // Create PhrasalCENode
            $node = PhrasalCENode::fromUDToken($token, $idLemma);
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * Apply construction matches to node array
     *
     * Replaces matched sequences with merged construction nodes
     */
    private function applyConstructionMatches(array $nodes, array $matches): array
    {
        if (empty($matches)) {
            return $nodes;
        }

        // Sort matches by position (descending) to apply from end to start
        // This prevents index shifting issues
        usort($matches, fn ($a, $b) => $b->startPosition - $a->startPosition);

        foreach ($matches as $match) {
            // Extract matched component nodes
            $components = array_slice(
                $nodes,
                $match->startPosition,
                $match->getLength()
            );

            // Create merged construction node
            $mergedNode = $this->createConstructionNode($components, $match);

            // Replace matched nodes with single merged node
            array_splice(
                $nodes,
                $match->startPosition,
                $match->getLength(),
                [$mergedNode]
            );
        }

        return $nodes;
    }

    /**
     * Create a PhrasalCENode from construction match
     */
    private function createConstructionNode(array $components, $match): PhrasalCENode
    {
        if (empty($components)) {
            throw new \InvalidArgumentException('Construction must have at least one component');
        }

        $firstNode = $components[0];

        // Combine words from all components
        $combinedWord = implode(' ', array_map(fn ($n) => $n->word, $components));
        $combinedLemma = implode(' ', array_map(fn ($n) => $n->lemma, $components));

        // Determine POS - use semantic type or first component's POS
        $pos = $this->derivePOSFromSemantics($match) ?? $firstNode->pos;

        // Merge features from match semantics and first component
        $features = [
            'lexical' => array_merge(
                $firstNode->features['lexical'] ?? [],
                $match->features
            ),
            'derived' => [
                'semanticValue' => $match->semanticValue,
                'construction' => $match->name,
                'slots' => $match->slots,
            ],
        ];

        return new PhrasalCENode(
            word: $combinedWord,
            lemma: $combinedLemma,
            pos: $pos,
            phrasalCE: \App\Enums\Parser\PhrasalCE::fromPOS($pos, $features['lexical']),
            features: $features,
            index: $firstNode->index,
            activation: count($components),
            threshold: count($components),
            isMWE: true,
            idLemma: $firstNode->idLemma,
            deprel: $firstNode->deprel,
            head: $firstNode->head,
        );
    }

    /**
     * Derive POS tag from semantic type
     */
    private function derivePOSFromSemantics($match): ?string
    {
        // Map semantic features to POS tags
        if (isset($match->features['NumType'])) {
            return 'NUM';
        }

        // Default: no derivation
        return null;
    }

    /**
     * Apply MWE matches to node array
     *
     * Replaces matched sequences with merged MWE nodes
     */
    private function applyMWEMatches(array $nodes, array $matches): array
    {
        if (empty($matches)) {
            return $nodes;
        }

        // Sort matches by position (descending) to apply from end to start
        // This prevents index shifting issues
        usort($matches, fn ($a, $b) => $b->startPosition - $a->startPosition);

        foreach ($matches as $match) {
            // Debug logging
            if ($this->getConfig('parser.logging.logMWE', false)) {
                logger()->info('Applying MWE match', [
                    'phrase' => $match->phrase,
                    'startPosition' => $match->startPosition,
                    'length' => $match->length,
                    'totalNodes' => count($nodes),
                ]);
            }

            // Extract matched component nodes
            $components = array_slice(
                $nodes,
                $match->startPosition,
                $match->length
            );

            if ($this->getConfig('parser.logging.logMWE', false)) {
                $componentWords = array_map(fn ($n) => $n->word, $components);
                logger()->info('Extracted components', [
                    'count' => count($components),
                    'words' => $componentWords,
                ]);
            }

            // Get POS from lexicon for this MWE (if available)
            $mwePhrase = $match->phrase;
            $pos = MWE::getPOS($mwePhrase) ?? $components[0]->pos;

            // Create merged MWE node using PhrasalCENode factory method
            $mergedNode = PhrasalCENode::fromMWEComponents($components, $match->length, $pos);

            if ($this->getConfig('parser.logging.logMWE', false)) {
                logger()->info('Created MWE node', [
                    'word' => $mergedNode->word,
                    'lemma' => $mergedNode->lemma,
                    'pos' => $mergedNode->pos,
                ]);
            }

            // Replace matched nodes with single merged node
            array_splice(
                $nodes,
                $match->startPosition,
                $match->length,
                [$mergedNode]
            );
        }

        return $nodes;
    }

    /**
     * Process a single UD token
     */
    private function processToken(
        array $token,
        int $idParserGraph,
        int $idGrammarGraph,
        int $idLanguage
    ): ?int {
        // Extract data from UD token
        $word = $token['word'];
        $lemma = $token['lemma'] ?? $word;
        $pos = $token['pos'] ?? 'X';
        $morphFeatures = $token['morph'] ?? [];
        $position = $token['id'];

        // Build feature bundle
        $features = $this->buildFeatureBundle($morphFeatures);

        if (config('parser.logging.logFeatures', false)) {
            logger()->info('Transcription: Extracted features', [
                'word' => $word,
                'features' => $features,
            ]);
        }

        // Classify word type (E/R/A/F)
        $wordType = $this->grammarService->getWordType($word, $pos, $idGrammarGraph);

        // Resolve lemma ID
        $idLemma = $this->lemmaResolver->getOrCreateLemma($lemma, $idLanguage, $pos);

        // Determine label (lemma for E/R/A, word for F)
        $label = ($wordType === 'F') ? $word : $lemma;

        // Create word node with features and stage
        $nodeData = [
            'idParserGraph' => $idParserGraph,
            'label' => $label,
            'idLemma' => $idLemma,
            'pos' => $pos,
            'type' => $wordType,
            'threshold' => 1,
            'activation' => 1,
            'isFocus' => true,
            'positionInSentence' => $position,
            'features' => json_encode($features),
            'stage' => 'transcription',
        ];

        $idWordNode = ParseNode::create($nodeData);

        // If word starts any MWE, instantiate prefix nodes
        $this->mweService->instantiateMWENodes(
            firstWord: $word,
            idParserGraph: $idParserGraph,
            idGrammarGraph: $idGrammarGraph,
            position: $position
        );

        return $idWordNode;
    }

    /**
     * Build feature bundle from UD morphological features
     *
     * Creates structure: {lexical: {...}, derived: {...}}
     */
    private function buildFeatureBundle(array $morphFeatures): array
    {
        $features = [
            'lexical' => [],
            'derived' => [],
        ];

        // Extract only the features we're tracking
        $extractedFeatures = config('parser.features.extractedFeatures', []);

        foreach ($morphFeatures as $featureName => $featureValue) {
            if (in_array($featureName, $extractedFeatures)) {
                $features['lexical'][$featureName] = $featureValue;
            }
        }

        return $features;
    }

    /**
     * Check MWE prefixes for activation
     *
     * Reuses existing MWEService logic
     */
    private function checkMWEPrefixes(string $word, int $idParserGraph, int $position): void
    {
        $mwePrefixes = $this->mweService->getActivePrefixes($idParserGraph);

        foreach ($mwePrefixes as $prefix) {
            // Check if word matches next expected component
            if ($this->mweService->matchesNextComponent($prefix, $word)) {
                // Check if not interrupted
                if (! $this->mweService->isInterrupted($prefix, $position)) {
                    // Increment activation
                    $this->mweService->incrementActivation($prefix, $word);

                    // Reload node to get updated activation
                    $updatedPrefix = ParseNode::byId($prefix->idParserNode);

                    // If threshold reached, aggregate MWE
                    if (ParseNode::hasReachedThreshold($updatedPrefix)) {
                        $this->mweService->aggregateMWE($updatedPrefix, $idParserGraph);

                        if (config('parser.logging.logStages', false)) {
                            logger()->info('Transcription: MWE completed', [
                                'label' => $updatedPrefix->label,
                                'threshold' => $updatedPrefix->threshold,
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Garbage collect incomplete MWE prefix nodes
     *
     * Only removes MWE nodes that didn't reach threshold
     * Regular word nodes are kept
     */
    private function garbageCollectIncompleteMWEs(int $idParserGraph): void
    {
        $incompleteMWEs = ParseNode::listBy([
            'idParserGraph' => $idParserGraph,
            'type' => 'MWE',
            'stage' => 'transcription',
        ]);

        $removedCount = 0;

        foreach ($incompleteMWEs as $node) {
            // Only remove if activation < threshold (incomplete)
            if ($node->activation < $node->threshold) {
                // Don't keep incomplete MWEs unless debugging
                if (! config('parser.garbageCollection.keepIncompleteMWE', false)) {
                    ParseNode::delete($node->idParserNode);
                    $removedCount++;
                }
            }
        }

        if ($removedCount > 0 && config('parser.logging.logStages', false)) {
            logger()->info('Transcription: Garbage collection', [
                'removedIncompleteMWEs' => $removedCount,
            ]);
        }
    }

    /**
     * Get feature bundle from node
     *
     * Helper method to decode features JSON
     */
    public function getNodeFeatures(object $node): array
    {
        if (empty($node->features)) {
            return ['lexical' => [], 'derived' => []];
        }

        return json_decode($node->features, true) ?? ['lexical' => [], 'derived' => []];
    }

    /**
     * Extract lexical features only
     */
    public function getLexicalFeatures(object $node): array
    {
        $features = $this->getNodeFeatures($node);

        return $features['lexical'] ?? [];
    }

    /**
     * Extract derived features only
     */
    public function getDerivedFeatures(object $node): array
    {
        $features = $this->getNodeFeatures($node);

        return $features['derived'] ?? [];
    }

    /**
     * Safe config access (for testing compatibility)
     */
    private function getConfig(string $key, mixed $default = null): mixed
    {
        try {
            return config($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
