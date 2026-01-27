<?php

namespace App\Services\Parser;

use App\Data\Parser\ParseInputData;
use App\Data\Parser\ParseOutputData;
use App\Repositories\Parser\ParseEdge;
use App\Repositories\Parser\ParseGraph;
use App\Repositories\Parser\ParseNode;
use App\Services\Trankit\TrankitService;
use Illuminate\Support\Facades\DB;

class ParserService
{
    private IncrementalParserEngine $v4Engine;

    public function __construct(
        IncrementalParserEngine $v4Engine
    ) {
        $this->v4Engine = $v4Engine;
    }

    /**
     * Parse a sentence using V4 Incremental Constructional Parser
     */
    public function parse(ParseInputData $input): ParseOutputData
    {
        $startTime = microtime(true);

        try {
            return DB::transaction(function () use ($input, $startTime) {
                // Create parse graph
                $idParserGraph = ParseGraph::create([
                    'sentence' => $input->sentence,
                    'idGrammarGraph' => $input->idGrammarGraph,
                    'status' => 'parsing',
                ]);

                // Get language ID from grammar graph
                $grammar = \App\Repositories\Parser\GrammarGraph::byId($input->idGrammarGraph);
                $idLanguage = config('parser.languageMap')[$grammar->language] ?? 1;

                if (config('parser.logging.logStages', false)) {
                    logger()->info('Parser: Using V4 Incremental Constructional Parser');
                }

                // Use V4 parser
                return $this->parseWithV4($input->sentence, $idParserGraph, $input->idGrammarGraph, $idLanguage, $startTime);
            });
        } catch (\Exception $e) {
            logger()->error('Parser error: '.$e->getMessage());

            throw new \Exception('Parse failed: '.$e->getMessage());
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
        foreach ($v4State->confirmedNodes as $index => &$node) {
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
                'pos' => $token['pos'] ?? $token['upos'] ?? 'X',
                'type' => $nodeType,
                'threshold' => 1,
                'activation' => 1,
                'isFocus' => true,
                'positionInSentence' => $position,
                'constructionName' => $constructionName,
                'idConstruction' => $node['idConstruction'] ?? null,
                'phrasalCE' => $node['phrasalCE'] ?? null,
                'clausalCE' => $node['clausalCE'] ?? null,
                'sententialCE' => $node['sententialCE'] ?? null,
                'stage' => 'v4',
            ]);

            // Store node ID for edge creation
            $node['dbId'] = $idNode;
        }
        unset($node); // Break the reference

        // Create edges from V4 confirmed edges
        $edgesSaved = 0;
        $edgesSkipped = 0;
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
                $edgesSaved++;
            } else {
                $edgesSkipped++;
                logger()->warning('V4 Edge skipped - nodes not found', [
                    'sourceId' => $edge['sourceId'] ?? 'missing',
                    'targetId' => $edge['targetId'] ?? 'missing',
                    'sourceFound' => $sourceNode !== null,
                    'targetFound' => $targetNode !== null,
                    'sourceHasDbId' => $sourceNode && isset($sourceNode['dbId']),
                    'targetHasDbId' => $targetNode && isset($targetNode['dbId']),
                ]);
            }
        }

        logger()->info('V4 edges saved to database', [
            'total' => count($v4State->confirmedEdges),
            'saved' => $edgesSaved,
            'skipped' => $edgesSkipped,
        ]);
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
