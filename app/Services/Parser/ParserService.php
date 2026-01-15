<?php

namespace App\Services\Parser;

use App\Data\Parser\ParseInputData;
use App\Data\Parser\ParseOutputData;
use App\Repositories\Parser\ParseEdge;
use App\Repositories\Parser\ParseGraph;
use App\Repositories\Parser\ParseNode;
use App\Services\Parser\V4\IncrementalParserEngine;
use App\Services\Trankit\TrankitService;
use Illuminate\Support\Facades\DB;

class ParserService
{
    private GrammarGraphService $grammarService;

    private MWEService $mweService;

    private FocusQueueService $queueService;

    private ?IncrementalParserEngine $v4Engine;

    public function __construct(
        GrammarGraphService $grammarService,
        MWEService $mweService,
        ?IncrementalParserEngine $v4Engine = null
    ) {
        $this->grammarService = $grammarService;
        $this->mweService = $mweService;
        $this->v4Engine = $v4Engine;
    }

    /**
     * Parse a sentence using the graph-based predictive parser
     */
    public function parse(ParseInputData $input): ParseOutputData
    {
        $startTime = microtime(true);

        try {
            return DB::transaction(function () use ($input, $startTime) {
                // Initialize focus queue with strategy
                $this->queueService = new FocusQueueService($input->queueStrategy);

                // Create parse graph
                $idParserGraph = ParseGraph::create([
                    'sentence' => $input->sentence,
                    'idGrammarGraph' => $input->idGrammarGraph,
                    'status' => 'parsing',
                ]);

                // Get language ID from grammar graph
                $grammar = \App\Repositories\Parser\GrammarGraph::byId($input->idGrammarGraph);
                $idLanguage = config('parser.languageMap')[$grammar->language] ?? 1;

                // === PARSER VERSION ROUTING ===
                // Check if V4 parser should be used
                $parserVersion = config('parser.version', 'v3');
                $v4Enabled = config('parser.v4.enabled', false);

                if (($parserVersion === 'v4' || $v4Enabled) && $this->v4Engine) {
                    if (config('parser.logging.logStages', false)) {
                        logger()->info('Parser: Using V4 Incremental Constructional Parser');
                    }

                    // Use V4 parser
                    return $this->parseWithV4($input->sentence, $idParserGraph, $input->idGrammarGraph, $idLanguage, $startTime);
                }

                // Otherwise, use V3 parser (existing 3-stage pipeline)
                if (config('parser.logging.logStages', false)) {
                    logger()->info('Parser: Using V3 Three-Stage Parser');
                }

                // Parse with UD to get tokens with features
                $tokens = $this->parseWithUD($input->sentence, $idLanguage);

                // === STAGE 1: TRANSCRIPTION ===
                if (config('parser.stages.enableTranscription', true)) {
                    if (config('parser.logging.logStages', false)) {
                        logger()->info('Parser: Starting Transcription Stage');
                    }

                    // Use TranscriptionService for feature-aware lexical assembly
                    $transcription = app(TranscriptionService::class);
                    $nodes = $transcription->transcribe(
                        $tokens,
                        $idParserGraph,
                        $input->idGrammarGraph,
                        $idLanguage
                    );

                    // Transcribed nodes are in focus queue implicitly via existing logic
                    // Get them and add to queue for prediction matching
                    $transcribedNodes = ParseNode::listByStage($idParserGraph, 'transcription');
                    foreach ($transcribedNodes as $node) {
                        if ($node->isFocus && $node->type !== 'MWE') {
                            // Check against existing focus predictions
                            $matched = $this->checkFociPredictions($node, $idParserGraph, $input->idGrammarGraph);

                            // If no match, add to queue
                            if (! $matched) {
                                $this->queueService->enqueue($node);
                            }
                        }
                    }

                    if (config('parser.logging.logStages', false)) {
                        logger()->info('Parser: Transcription Stage Complete', [
                            'nodes' => count($nodes),
                        ]);
                    }
                } else {
                    // Fallback: Use existing sequential processing (for comparison/debugging)
                    foreach ($tokens as $token) {
                        $this->processWord(
                            word: $token['word'],
                            lemma: $token['lemma'] ?? $token['word'],
                            pos: $token['pos'] ?? 'X',
                            idParserGraph: $idParserGraph,
                            idGrammarGraph: $input->idGrammarGraph,
                            idLanguage: $idLanguage,
                            position: $token['id']
                        );

                        // Check timeout
                        if ((microtime(true) - $startTime) > config('parser.performance.maxParseTime', 30)) {
                            throw new \Exception('Parse timeout exceeded');
                        }
                    }
                }

                // === STAGE 2: TRANSLATION ===
                if (config('parser.stages.enableTranslation', true)) {
                    if (config('parser.logging.logStages', false)) {
                        logger()->info('Parser: Starting Translation Stage');
                    }

                    // Use TranslationService for feature-driven phrasal construction
                    $translation = app(TranslationService::class);
                    $phraseLinks = $translation->translate(
                        $idParserGraph,
                        $input->idGrammarGraph,
                        $grammar->language
                    );

                    if (config('parser.logging.logStages', false)) {
                        logger()->info('Parser: Translation Stage Complete', [
                            'links' => count($phraseLinks),
                        ]);
                    }
                }

                // === STAGE 3: FOLDING ===
                if (config('parser.stages.enableFolding', true)) {
                    if (config('parser.logging.logStages', false)) {
                        logger()->info('Parser: Starting Folding Stage');
                    }

                    // Use FoldingService for sentential integration
                    $folding = app(FoldingService::class);
                    $sentenceLinks = $folding->fold(
                        $idParserGraph,
                        $input->idGrammarGraph,
                        $grammar->language
                    );

                    if (config('parser.logging.logStages', false)) {
                        logger()->info('Parser: Folding Stage Complete', [
                            'links' => count($sentenceLinks),
                        ]);
                    }
                }

                // Garbage collection
                if (config('parser.garbageCollection.enabled', true)) {
                    $this->garbageCollect($idParserGraph);
                }

                // Validate parse
                $isValid = $this->validateParse($idParserGraph);

                // Update status
                if ($isValid) {
                    ParseGraph::markComplete($idParserGraph);
                } else {
                    ParseGraph::markFailed($idParserGraph, 'Parse validation failed');
                }

                // Return result
                return $this->buildOutput($idParserGraph);
            });
        } catch (\Exception $e) {
            logger()->error('Parser error: '.$e->getMessage());

            throw new \Exception('Parse failed: '.$e->getMessage());
        }
    }

    /**
     * Process a single word with UD information
     */
    private function processWord(
        string $word,
        string $lemma,
        string $pos,
        int $idParserGraph,
        int $idGrammarGraph,
        int $idLanguage,
        int $position
    ): void {
        if (config('parser.logging.logSteps', false)) {
            logger()->info('Parser: Processing word', [
                'word' => $word,
                'lemma' => $lemma,
                'pos' => $pos,
                'position' => $position,
            ]);
        }

        // Step 1: Get word type from POS
        $wordType = $this->grammarService->getWordType($word, $pos, $idGrammarGraph);

        // Step 2: Resolve lemma ID
        $lemmaResolver = app(LemmaResolverService::class);
        $idLemma = $lemmaResolver->getOrCreateLemma($lemma, $idLanguage, $pos);

        // Step 3: Determine label (lemma for E/R/A, word for F)
        $label = ($wordType === 'F') ? $word : $lemma;

        // Step 4: Create word node
        $idWordNode = ParseNode::create([
            'idParserGraph' => $idParserGraph,
            'label' => $label,
            'idLemma' => $idLemma,
            'pos' => $pos,
            'type' => $wordType,
            'threshold' => 1,
            'activation' => 1,
            'isFocus' => true,
            'positionInSentence' => $position,
        ]);

        $wordNode = ParseNode::byId($idWordNode);

        // Step 5: If word/lemma is first in any MWE, instantiate prefix nodes
        // TODO: Update to use lemma for E/R/A types
        $this->mweService->instantiateMWENodes($word, $idParserGraph, $idGrammarGraph, $position);

        // Step 6: Check existing MWE prefix nodes for matches
        // TODO: Update to use lemma for E/R/A types
        $this->checkMWEPrefixes($word, $idParserGraph, $position);

        // Step 7: Check against current focus nodes
        $matched = $this->checkFociPredictions($wordNode, $idParserGraph, $idGrammarGraph);

        // Step 8: If no match, add word as new waiting focus
        if (! $matched) {
            $this->queueService->enqueue($wordNode);
        }
    }

    /**
     * Check MWE prefixes for activation
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

                    // If threshold reached, aggregate
                    if (ParseNode::hasReachedThreshold($updatedPrefix)) {
                        $this->mweService->aggregateMWE($updatedPrefix, $idParserGraph);

                        // Add to focus queue
                        $this->queueService->enqueue($updatedPrefix);
                    }
                }
            }
        }
    }

    /**
     * Check if word matches predictions from focus nodes
     */
    private function checkFociPredictions(object $wordNode, int $idParserGraph, int $idGrammarGraph): bool
    {
        $matched = false;
        $foci = $this->queueService->getActiveFoci();

        foreach ($foci as $focus) {
            if ($this->grammarService->canLink($focus, $wordNode, $idGrammarGraph)) {
                // Create edge
                ParseEdge::create([
                    'idParserGraph' => $idParserGraph,
                    'idSourceNode' => $focus->idParserNode,
                    'idTargetNode' => $wordNode->idParserNode,
                    'linkType' => 'dependency',
                ]);

                // Remove focus from queue
                $this->queueService->removeFromQueue($focus);

                $matched = true;

                // Recursive linking
                if (config('parser.prediction.recursiveLinking', true)) {
                    $this->recursiveLinking($wordNode, $idParserGraph, $idGrammarGraph);
                }

                break;
            }
        }

        return $matched;
    }

    /**
     * Attempt recursive linking of waiting foci
     */
    private function recursiveLinking(object $newNode, int $idParserGraph, int $idGrammarGraph): void
    {
        $waitingFoci = $this->queueService->getActiveFoci();

        foreach ($waitingFoci as $focus) {
            if ($this->grammarService->canLink($newNode, $focus, $idGrammarGraph)) {
                // Create edge
                ParseEdge::create([
                    'idParserGraph' => $idParserGraph,
                    'idSourceNode' => $newNode->idParserNode,
                    'idTargetNode' => $focus->idParserNode,
                    'linkType' => 'dependency',
                ]);

                // Remove focus from queue
                $this->queueService->removeFromQueue($focus);

                // Continue recursion
                $this->recursiveLinking($focus, $idParserGraph, $idGrammarGraph);
            }
        }
    }

    /**
     * Remove nodes that didn't reach threshold
     */
    private function garbageCollect(int $idParserGraph): void
    {
        $garbageNodes = ParseGraph::getGarbageNodes($idParserGraph);

        foreach ($garbageNodes as $node) {
            // Check if should keep incomplete MWEs for debugging
            if ($node->type === 'MWE' && config('parser.garbageCollection.keepIncompleteMWE', false)) {
                continue;
            }

            // Delete edges involving this node
            ParseEdge::deleteByNode($node->idParserNode);

            // Delete node
            ParseNode::delete($node->idParserNode);
        }

        if (config('parser.logging.logSteps', false)) {
            logger()->info('Parser: Garbage collection', [
                'removed' => count($garbageNodes),
            ]);
        }
    }

    /**
     * Validate that parse is successful
     */
    private function validateParse(int $idParserGraph): bool
    {
        if (! config('parser.validation.requireConnected', true)) {
            return true;
        }

        $nodeCount = ParseGraph::countNodes($idParserGraph);
        $edgeCount = ParseGraph::countEdges($idParserGraph);

        // Check minimum edge ratio
        $minRatio = config('parser.validation.minEdgeRatio', 0.9);
        $requiredEdges = ($nodeCount - 1) * $minRatio;

        return $edgeCount >= $requiredEdges;
    }

    /**
     * Parse sentence using V4 Incremental Constructional Parser
     */
    private function parseWithV4(
        string $sentence,
        int $idParserGraph,
        int $idGrammarGraph,
        int $idLanguage,
        float $startTime
    ): ParseOutputData {
        // Parse with UD to get tokens with features
        $tokens = $this->parseWithUD($sentence, $idLanguage);

        // Convert tokens to objects for V4 engine
        $tokenObjects = array_map(function ($token) {
            return (object) $token;
        }, $tokens);

        // Run V4 incremental parser
        $v4State = $this->v4Engine->parse($tokenObjects, $idGrammarGraph);

        // Convert V4 output to database format
        $this->saveV4ResultsToDatabase($v4State, $idParserGraph, $tokens);

        // Validate parse
        $isValid = $this->validateParse($idParserGraph);

        // Update status
        if ($isValid) {
            ParseGraph::markComplete($idParserGraph);
        } else {
            ParseGraph::markFailed($idParserGraph, 'V4 parse validation failed');
        }

        // Return result
        return $this->buildOutput($idParserGraph);
    }

    /**
     * Save V4 parser results to database
     */
    private function saveV4ResultsToDatabase($v4State, int $idParserGraph, array $tokens): void
    {
        // Create nodes from V4 confirmed nodes
        foreach ($v4State->confirmedNodes as $index => $node) {
            $position = $node['position'] ?? $node['startPosition'] ?? $index;
            $constructionName = $node['constructionName'] ?? 'unknown';
            $type = $node['type'] ?? 'unknown';

            // Get original token data for this position
            $token = $tokens[$position] ?? null;

            // Determine node label and type
            if ($type === 'mwe') {
                // MWE node
                $label = $node['aggregateAs'] ?? $constructionName;
                $nodeType = 'MWE';
            } else {
                // Regular node
                $label = $token['lemma'] ?? $token['word'] ?? $constructionName;
                $nodeType = $this->mapConstructionTypeToNodeType($node, $token);
            }

            // Create parse node
            $idNode = ParseNode::create([
                'idParserGraph' => $idParserGraph,
                'label' => $label,
                'idLemma' => $token['idLemma'] ?? null,
                'pos' => $token['pos'] ?? $token['upos'] ?? 'X',
                'type' => $nodeType,
                'threshold' => 1,
                'activation' => 1,
                'isFocus' => true,
                'positionInSentence' => $position,
                'constructionName' => $constructionName,
                'phrasalCE' => $node['phrasalCE'] ?? null,
                'clausalCE' => $node['clausalCE'] ?? null,
                'sententialCE' => $node['sententialCE'] ?? null,
                'stage' => 'v4',
            ]);

            // Store node ID for edge creation
            $node['dbId'] = $idNode;
        }

        // Create edges from V4 confirmed edges
        foreach ($v4State->confirmedEdges as $edge) {
            // Find source and target node IDs
            $sourceNode = $this->findNodeByV4Id($v4State->confirmedNodes, $edge['sourceId']);
            $targetNode = $this->findNodeByV4Id($v4State->confirmedNodes, $edge['targetId']);

            if ($sourceNode && $targetNode && isset($sourceNode['dbId']) && isset($targetNode['dbId'])) {
                ParseEdge::create([
                    'idParserGraph' => $idParserGraph,
                    'idSourceNode' => $sourceNode['dbId'],
                    'idTargetNode' => $targetNode['dbId'],
                    'relation' => $edge['relation'],
                    'stage' => 'v4',
                    'weight' => 1.0,
                ]);
            }
        }
    }

    /**
     * Find a node in V4 confirmed nodes by its V4 ID
     */
    private function findNodeByV4Id(array &$nodes, string $v4Id): ?array
    {
        foreach ($nodes as &$node) {
            $nodeId = $node['id'] ?? ($node['constructionName'].'_'.($node['position'] ?? $node['startPosition'] ?? '0'));
            if ($nodeId === $v4Id) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Map V4 construction type and CE labels to parser node type
     */
    private function mapConstructionTypeToNodeType(array $node, ?array $token): string
    {
        // Check POS from token
        if ($token) {
            $pos = $token['pos'] ?? $token['upos'] ?? null;
            if ($pos) {
                // Use word type mappings from config
                $mappings = config('parser.wordTypeMappings', []);
                foreach ($mappings as $type => $posList) {
                    if (in_array($pos, $posList)) {
                        return $type;
                    }
                }
            }
        }

        // Fallback to semantic type if available
        if (isset($node['semanticType'])) {
            return $node['semanticType'];
        }

        // Default fallback
        return 'E';
    }

    /**
     * Parse sentence with UD parser (Trankit)
     */
    private function parseWithUD(string $sentence, int $idLanguage): array
    {
        // Initialize Trankit service
        $trankitService = app(TrankitService::class);
        $trankitUrl = config('parser.trankit.url');
        $trankitService->init($trankitUrl);

        // Get UD parse
        $udResult = $trankitService->getUDTrankit($sentence, $idLanguage);
        $tokens = $udResult->udpipe ?? [];

        return $tokens;
    }

    /**
     * Build output data
     */
    private function buildOutput(int $idParserGraph): ParseOutputData
    {
        $parseGraph = ParseGraph::getComplete($idParserGraph);
        $stats = ParseGraph::byIdWithStats($idParserGraph);

        return new ParseOutputData(
            idParserGraph: $parseGraph->idParserGraph,
            sentence: $parseGraph->sentence,
            status: $parseGraph->status,
            nodes: $parseGraph->nodes,
            edges: $parseGraph->edges,
            nodeCount: $stats->nodeCount ?? 0,
            edgeCount: $stats->linkCount ?? 0,
            focusNodeCount: $stats->focusNodeCount ?? 0,
            mweNodeCount: $stats->mweNodeCount ?? 0,
            isValid: $parseGraph->status === 'complete',
            errorMessage: $parseGraph->errorMessage ?? null,
        );
    }

    /**
     * Get parse result by ID
     */
    public function getParseResult(int $idParserGraph): ParseOutputData
    {
        return $this->buildOutput($idParserGraph);
    }
}
