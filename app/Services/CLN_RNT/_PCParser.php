<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\PCParseResult;
use App\Models\CLN_RNT\PCParserGraphNode;

/**
 * PC Parser
 *
 * Main Predictive Coding parser that processes token sequences and builds
 * a parser graph showing all parallel exploration paths.
 *
 * Key algorithm:
 * 1. For each token: check ALL waiting nodes, match against pattern graph
 * 2. Follow all pattern edges (parallel exploration)
 * 3. Create waiting nodes for predicted next elements
 * 4. Create active constructions when patterns complete (edge → END)
 * 5. Recursively process new constructions
 *
 * Critical: Waiting nodes are global (NOT position-specific)
 */
class PCParser
{
    /**
     * Graph builder
     */
    private PCGraphBuilder $graphBuilder;

    /**
     * Token matcher
     */
    private PCTokenMatcher $tokenMatcher;

    /**
     * Pattern matcher (for pattern graph queries)
     */
    private GraphPatternMatcher $patternMatcher;

    /**
     * Nodes processed in current iteration (to avoid infinite loops)
     */
    private array $processed = [];

    /**
     * Create new PC parser
     */
    public function __construct(
        PCGraphBuilder $graphBuilder,
        PCTokenMatcher $tokenMatcher,
        GraphPatternMatcher $patternMatcher
    ) {
        $this->graphBuilder = $graphBuilder;
        $this->tokenMatcher = $tokenMatcher;
        $this->patternMatcher = $patternMatcher;
    }

    /**
     * Parse a token sequence
     *
     * @param  string  $sequence  Token sequence (e.g., "the/DET cat/NOUN")
     */
    public function parse(string $sequence): PCParseResult
    {
        // Parse sequence into tokens
        $tokens = $this->parseSequence($sequence);

        // Process each token
        foreach ($tokens as $token) {
            $this->processToken($token['word'], $token['pos'], $token['position']);
        }

        // Collect completed constructions
        $constructions = array_filter(
            $this->graphBuilder->getAllNodes(),
            fn ($node) => $node->isConstruction() && $node->isCompleted()
        );

        // Build result
        return new PCParseResult([
            'sequence' => $sequence,
            'tokens' => $tokens,
            'nodes' => $this->graphBuilder->getAllNodes(),
            'edges' => $this->graphBuilder->getAllEdges(),
            'constructions' => array_values($constructions),
            'statistics' => $this->graphBuilder->getStatistics(),
        ]);
    }

    /**
     * Process a single token
     *
     * @param  string  $word  Word text
     * @param  string  $pos  Part-of-speech tag
     * @param  int  $position  Token position in sequence
     */
    private function processToken(string $word, string $pos, int $position): void
    {
        error_log("PC Parser: Processing token {$word}/{$pos} at position {$position}");

        // Step 1: Check ALL waiting nodes to see if this token matches any
        $this->checkAndActivateWaitingNodesForToken($word, $pos);

        // Step 2: Create active token node
        $tokenNode = $this->graphBuilder->findOrCreateTokenNode($position, $word, $pos);
        error_log("PC Parser: Created token node {$tokenNode->id}");

        // Step 3: Find matching pattern nodes from START
        $matches = $this->findMatchingPatternsForToken($word, $pos);
        error_log('PC Parser: About to follow edges for '.count($matches).' matches');

        // Step 4: Follow pattern edges for each match
        foreach ($matches as $match) {
            error_log('PC Parser: Calling followPatternEdges...');
            $this->followPatternEdges($tokenNode, $match, $position);
        }
        error_log('PC Parser: Done processing token');
    }

    /**
     * Check ALL waiting nodes globally to see if token matches any
     *
     * @param  string  $word  Word text
     * @param  string  $pos  Part-of-speech tag
     */
    private function checkAndActivateWaitingNodesForToken(string $word, string $pos): void
    {
        $waitingNodes = $this->graphBuilder->getAllWaitingNodes();
        $graphCache = $this->getGraphCache();

        foreach ($waitingNodes as $waitingNode) {
            // Only check token-type waiting nodes
            if (! $waitingNode->isToken()) {
                continue;
            }

            // Get pattern node from graph cache
            $patternNodeId = $waitingNode->metadata['pattern_node_id'] ?? null;
            if (! $patternNodeId) {
                continue;
            }

            $patternNode = $graphCache['nodes'][$patternNodeId] ?? null;
            if (! $patternNode) {
                continue;
            }

            // Check if this token matches the pattern node (handles both SLOT and LITERAL)
            if ($this->tokenMatcher->matchesToken($word, $pos, $patternNode)) {
                // Activate waiting node
                $waitingNode->activate();
                $this->graphBuilder->unregisterWaitingNode($waitingNode);

                // Confirm all prediction edges pointing to this node
                $this->graphBuilder->confirmEdgesToNode($waitingNode);

                // Continue pattern from this activated node
                if (isset($waitingNode->metadata['pattern_id'])) {
                    $this->continuePatternFromNode(
                        $waitingNode,
                        $waitingNode->metadata['pattern_id'],
                        $patternNodeId
                    );
                }
            }
        }
    }

    /**
     * Check ALL waiting nodes to see if construction matches any
     *
     * @param  string  $constructionName  Construction name
     * @param  PCParserGraphNode  $constructionNode  The construction node
     */
    private function checkAndActivateWaitingNodesForConstruction(
        string $constructionName,
        PCParserGraphNode $constructionNode
    ): void {
        $waitingNodes = $this->graphBuilder->getAllWaitingNodes();

        foreach ($waitingNodes as $waitingNode) {
            // Only check construction-type waiting nodes
            if (! $waitingNode->isConstruction()) {
                continue;
            }

            // Check if this construction matches the waiting node
            if ($waitingNode->value === $constructionName) {
                // Activate waiting node
                $waitingNode->activate();
                $this->graphBuilder->unregisterWaitingNode($waitingNode);

                // Confirm all prediction edges pointing to this node
                $this->graphBuilder->confirmEdgesToNode($waitingNode);

                // Create edge from construction to activated waiting node (avoid self-loops)
                if ($constructionNode->id !== $waitingNode->id) {
                    $this->graphBuilder->createEdge(
                        $constructionNode,
                        $waitingNode,
                        'activates',
                        'match'
                    );
                }

                // Continue pattern from this activated node
                if (isset($waitingNode->metadata['pattern_id']) &&
                    isset($waitingNode->metadata['pattern_node_id'])) {
                    $this->continuePatternFromNode(
                        $waitingNode,
                        $waitingNode->metadata['pattern_id'],
                        $waitingNode->metadata['pattern_node_id']
                    );
                }
            }
        }
    }

    /**
     * Find pattern nodes that match the given token from START
     *
     * @param  string  $word  Word text
     * @param  string  $pos  Part-of-speech tag
     * @return array Array of matches
     */
    private function findMatchingPatternsForToken(string $word, string $pos): array
    {
        // Get all edges from START node
        $graphCache = $this->getGraphCache();
        $startNodeId = $graphCache['start_node_id'];

        if ($startNodeId === null) {
            error_log('PC Parser: START node not found in graph cache');

            return [];
        }

        $matches = [];
        $edges = $this->getEdgesFromNode($startNodeId);

        error_log('PC Parser: Found '.count($edges)." edges from START node for {$word}/{$pos}");

        foreach ($edges as $edge) {
            $toNodeId = $edge['to_node_id'];
            $patternNode = $graphCache['nodes'][$toNodeId] ?? null;

            if (! $patternNode) {
                continue;
            }

            $nodeType = $patternNode['type'] ?? 'UNKNOWN';
            $nodePos = $patternNode['pos'] ?? 'N/A';
            error_log("PC Parser: Checking pattern node type={$nodeType}, pos={$nodePos}");

            // Check if token matches this pattern node
            if ($this->tokenMatcher->matchesToken($word, $pos, $patternNode)) {
                error_log('PC Parser: MATCH FOUND!');
                $matches[] = [
                    'pattern_id' => $edge['pattern_id'],
                    'node_id' => $toNodeId,
                    'node' => $patternNode,
                    'edge' => $edge,
                ];
            }
        }

        error_log('PC Parser: Total matches: '.count($matches));

        return $matches;
    }

    /**
     * Follow pattern edges from a matched node
     *
     * @param  PCParserGraphNode  $parserNode  Parser graph node (token or construction)
     * @param  array  $match  Pattern match info
     * @param  int  $position  Current position in sequence
     */
    private function followPatternEdges(
        PCParserGraphNode $parserNode,
        array $match,
        int $position
    ): void {
        $patternId = $match['pattern_id'];
        $patternNodeId = $match['node_id'];

        error_log("PC Parser: Following edges for pattern {$patternId} from node {$patternNodeId}");

        // Get next nodes in the pattern
        $nextNodes = $this->patternMatcher->findNextNodes($patternNodeId, $patternId);

        error_log('PC Parser: Found '.count($nextNodes).' next nodes');

        foreach ($nextNodes as $next) {
            if ($next['is_end']) {
                error_log('PC Parser: Reached END node!');
                // Pattern completed! Create construction from edge label or pattern name
                $edgeLabel = $next['edge']['label'] ?? null;

                // If no edge label, use the pattern name
                if (! $edgeLabel) {
                    $metadata = $this->patternMatcher->getConstructionMetadata($patternId);
                    $edgeLabel = $metadata['name'] ?? null;
                    error_log('PC Parser: No edge label, using pattern name: '.($edgeLabel ?? 'NULL'));
                } else {
                    error_log("PC Parser: Edge label: {$edgeLabel}");
                }

                if ($edgeLabel) {
                    $constructionNode = $this->createConstruction(
                        $edgeLabel,
                        $position,
                        $patternId
                    );

                    // Create edge from token to construction
                    $this->graphBuilder->createEdge(
                        $parserNode,
                        $constructionNode,
                        $edgeLabel,
                        'completion',
                        $patternId
                    );
                    error_log("PC Parser: Created construction {$edgeLabel}!");
                }
            } else {
                error_log('PC Parser: Creating waiting node for next pattern element');
                // Create waiting node for next pattern element
                $nextPatternNode = $next['node'];
                $nextEdge = $next['edge'];
                $edgeLabel = $nextEdge['label'] ?? null;

                // If no edge label, get pattern name
                if (! $edgeLabel) {
                    $metadata = $this->patternMatcher->getConstructionMetadata($patternId);
                    $edgeLabel = $metadata['name'] ?? 'expects';
                }

                $this->createWaitingNode($parserNode, $nextPatternNode, $patternId, $position, $edgeLabel);
            }
        }
    }

    /**
     * Continue pattern matching from an activated node
     *
     * @param  PCParserGraphNode  $node  Activated node
     * @param  int  $patternId  Pattern ID
     * @param  int  $patternNodeId  Pattern node ID
     */
    private function continuePatternFromNode(
        PCParserGraphNode $node,
        int $patternId,
        int $patternNodeId
    ): void {
        // Avoid infinite loops
        $key = "{$node->id}:{$patternId}:{$patternNodeId}";
        if (isset($this->processed[$key])) {
            return;
        }
        $this->processed[$key] = true;

        // Get next nodes in the pattern
        $nextNodes = $this->patternMatcher->findNextNodes($patternNodeId, $patternId);

        foreach ($nextNodes as $next) {
            if ($next['is_end']) {
                // Pattern completed! Create construction
                $edgeLabel = $next['edge']['label'] ?? null;
                if ($edgeLabel) {
                    $constructionNode = $this->createConstruction(
                        $edgeLabel,
                        $node->position,
                        $patternId
                    );

                    // Create edge
                    $this->graphBuilder->createEdge(
                        $node,
                        $constructionNode,
                        $edgeLabel,
                        'completion',
                        $patternId
                    );
                }
            } else {
                // Create waiting node
                $nextPatternNode = $next['node'];
                $nextEdge = $next['edge'];
                $edgeLabel = $nextEdge['label'] ?? null;

                // If no edge label, get pattern name
                if (! $edgeLabel) {
                    $metadata = $this->patternMatcher->getConstructionMetadata($patternId);
                    $edgeLabel = $metadata['name'] ?? 'expects';
                }

                $this->createWaitingNode($node, $nextPatternNode, $patternId, $node->position, $edgeLabel);
            }
        }
    }

    /**
     * Create a construction node from END node
     *
     * @param  string  $constructionLabel  Construction name (from edge label)
     * @param  int  $position  Position where construction was recognized
     * @param  int  $patternId  Pattern ID
     */
    private function createConstruction(
        string $constructionLabel,
        int $position,
        int $patternId
    ): PCParserGraphNode {
        $wasWaitingNode = false;

        // STEP 1: Check if there's a WAITING construction node we can activate
        $waitingConstruction = $this->graphBuilder->findWaitingNode('construction', $constructionLabel);

        if ($waitingConstruction) {
            // Activate the waiting node and use it
            $constructionNode = $waitingConstruction;
            $constructionNode->activate();
            $constructionNode->complete();
            $this->graphBuilder->unregisterWaitingNode($waitingConstruction);

            // Confirm edges pointing to this node (it was predicted correctly)
            $this->graphBuilder->confirmEdgesToNode($constructionNode);

            // Track usage at current position
            $constructionNode->addUsagePosition($position);

            $wasWaitingNode = true;

            error_log("PC Parser: Activated waiting construction node {$constructionNode->id} for {$constructionLabel} at position {$position}");
        }
        // STEP 2: Check if there's an active/completed non-confirmed construction we can reuse
        // Must satisfy temporal locality constraint (position - 1 or used at position - 1)
        elseif ($existingConstruction = $this->graphBuilder->findActiveNonConfirmedNode('construction', $constructionLabel, $position)) {
            // Reuse existing construction
            $constructionNode = $existingConstruction;

            // Track that this node is being used at current position
            $constructionNode->addUsagePosition($position);

            // Update status to completed if it wasn't already
            if (! $constructionNode->isCompleted()) {
                $constructionNode->complete();
            }

            error_log("PC Parser: Reusing existing construction node {$constructionNode->id} for {$constructionLabel} at position {$position}");
        }
        // STEP 3: No existing node found, create new
        else {
            // Create new completed construction node
            $constructionNode = $this->graphBuilder->findOrCreateConstructionNode(
                $position,
                $constructionLabel,
                'completed'
            );

            error_log("PC Parser: Created new construction node {$constructionNode->id} for {$constructionLabel} at position {$position}");
        }

        $constructionNode->metadata['pattern_id'] = $patternId;

        // RECURSIVE FEEDBACK CONFIRMATION: Confirm all edges pointing to this completed construction
        // This creates a chain of confirmations flowing backwards through the graph
        // (Skip if we already confirmed when activating a waiting node)
        if (! $wasWaitingNode) {
            $this->graphBuilder->confirmEdgesToNode($constructionNode);
        }

        // Check ALL waiting nodes to see if any match this construction
        // (Skip if we just activated this construction from waiting - no need to check again)
        if (! $wasWaitingNode) {
            $this->checkAndActivateWaitingNodesForConstruction($constructionLabel, $constructionNode);
        }

        // Find matching patterns for this construction (recursive)
        $this->findAndFollowPatternsForConstruction($constructionNode, $constructionLabel, $position);

        return $constructionNode;
    }

    /**
     * Find and follow patterns for a construction
     *
     * @param  PCParserGraphNode  $constructionNode  Construction node
     * @param  string  $constructionName  Construction name
     * @param  int  $position  Position
     */
    private function findAndFollowPatternsForConstruction(
        PCParserGraphNode $constructionNode,
        string $constructionName,
        int $position
    ): void {
        error_log("PC Parser: Finding patterns for construction {$constructionName}");

        // Get all edges from START node
        $graphCache = $this->getGraphCache();
        $startNodeId = $graphCache['start_node_id'];

        $edges = $this->getEdgesFromNode($startNodeId);
        error_log("PC Parser: Checking {$constructionName} against ".count($edges).' edges from START');

        $matchCount = 0;
        foreach ($edges as $edge) {
            $toNodeId = $edge['to_node_id'];
            $patternNode = $graphCache['nodes'][$toNodeId] ?? null;

            if (! $patternNode) {
                continue;
            }

            $nodeType = $patternNode['type'] ?? 'UNKNOWN';
            $nodeCxnName = $patternNode['construction_name'] ?? 'N/A';

            // Check if construction matches this pattern node
            if ($this->tokenMatcher->matchesConstruction($constructionName, $patternNode)) {
                error_log("PC Parser: CONSTRUCTION MATCH! {$constructionName} matches node type={$nodeType}, cxn={$nodeCxnName}");
                $matchCount++;

                $match = [
                    'pattern_id' => $edge['pattern_id'],
                    'node_id' => $toNodeId,
                    'node' => $patternNode,
                    'edge' => $edge,
                ];

                $this->followPatternEdges($constructionNode, $match, $position);
            }
        }

        error_log("PC Parser: Found {$matchCount} construction matches for {$constructionName}");
    }

    /**
     * Create a waiting node for predicted next element
     *
     * @param  PCParserGraphNode  $fromNode  Source node
     * @param  array  $patternNode  Pattern node to wait for
     * @param  int  $patternId  Pattern ID
     * @param  int  $position  Current position (for visualization)
     * @param  string  $edgeLabel  Label from pattern graph edge
     */
    private function createWaitingNode(
        PCParserGraphNode $fromNode,
        array $patternNode,
        int $patternId,
        int $position,
        string $edgeLabel
    ): void {
        $nodeType = ($patternNode['type'] === 'CONSTRUCTION_REF' || $patternNode['type'] === 'CONSTRUCTION')
            ? 'construction'
            : 'token';

        $value = $patternNode['construction_name'] ?? $patternNode['value'] ?? $patternNode['pos'] ?? 'UNKNOWN';

        // Step 1: Check if waiting node already exists
        $existingWaiting = $this->graphBuilder->findWaitingNode($nodeType, $value);
        if ($existingWaiting) {
            // Waiting node already exists, create edge to it with pattern graph label
            $this->graphBuilder->createEdge(
                $fromNode,
                $existingWaiting,
                $edgeLabel,
                'prediction',
                $patternId
            );

            return;
        }

        // Step 2: Check if there's an active/completed non-confirmed node we can reuse
        // Must satisfy temporal locality constraint (position - 1 or used at position - 1)
        $existingActive = $this->graphBuilder->findActiveNonConfirmedNode($nodeType, $value, $position);
        if ($existingActive) {
            // Reuse existing active node, track usage at current position
            $existingActive->addUsagePosition($position);

            // Create prediction edge to it
            $this->graphBuilder->createEdge(
                $fromNode,
                $existingActive,
                $edgeLabel,
                'prediction',
                $patternId
            );

            return;
        }

        // Step 3: No existing node found, create new waiting node
        // Use the CREATION position (where the prediction originated from) for visualization
        $waitingNode = ($nodeType === 'construction')
            ? $this->graphBuilder->findOrCreateConstructionNode($position, $value, 'waiting')
            : $this->graphBuilder->createWaitingNode(
                $position,
                $nodeType,
                $value,
                [
                    'pattern_id' => $patternId,
                    'pattern_node_id' => $patternNode['id'],
                    'pattern_type' => $patternNode['type'] ?? null,
                    'expected_pos' => $patternNode['pos'] ?? null,
                    'expected_word' => $patternNode['value'] ?? null,  // For LITERAL nodes
                ]
            );

        // Register in waiting nodes registry
        $this->graphBuilder->registerWaitingNode($waitingNode);

        // Create edge from current node to waiting node with pattern graph label
        $this->graphBuilder->createEdge(
            $fromNode,
            $waitingNode,
            $edgeLabel,
            'prediction',
            $patternId
        );
    }

    /**
     * Parse sequence string into tokens
     *
     * @param  string  $sequence  Token sequence (e.g., "the/DET cat/NOUN")
     * @return array Array of tokens with word, pos, position
     */
    private function parseSequence(string $sequence): array
    {
        $tokens = [];
        $parts = preg_split('/\s+/', trim($sequence));

        foreach ($parts as $position => $part) {
            if (strpos($part, '/') !== false) {
                [$word, $pos] = explode('/', $part, 2);
                $tokens[] = [
                    'word' => $word,
                    'pos' => $pos,
                    'position' => $position,
                ];
            }
        }

        return $tokens;
    }

    /**
     * Get pattern graph cache (helper method)
     */
    private function getGraphCache(): array
    {
        // Access the static cache via reflection or a public getter
        // For now, we'll load it if needed
        $reflection = new \ReflectionClass($this->patternMatcher);
        $property = $reflection->getProperty('graphCache');
        $property->setAccessible(true);
        $cache = $property->getValue();

        if ($cache === null) {
            // Trigger graph loading
            $this->patternMatcher->findMatchingPatternsFromStart(
                new \App\Models\CLN_RNT\Node(\App\Models\CLN_RNT\Layer::L23, 'dummy')
            );
            $cache = $property->getValue();
        }

        return $cache ?? ['nodes' => [], 'edges' => [], 'start_node_id' => null, 'end_node_id' => null];
    }

    /**
     * Get edges from a pattern graph node (helper method)
     */
    private function getEdgesFromNode(int $nodeId): array
    {
        $graphCache = $this->getGraphCache();
        $result = [];

        foreach ($graphCache['edges'] as $edge) {
            if ($edge['from_node_id'] === $nodeId) {
                $result[] = $edge;
            }
        }

        return $result;
    }
}
