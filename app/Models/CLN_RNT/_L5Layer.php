<?php

namespace App\Models\CLN_RNT;

use App\Services\CLN_RNT\GraphPatternMatcher;
use App\Services\CLN_RNT\NodeEventRegistry;
use App\Services\CLN_RNT\NodeFactory;
use App\Services\CLN_RNT\PatternMatcher;

/**
 * L5 Layer (Output Layer)
 *
 * Represents the deep cortical layer 5 in CLN architecture.
 * Contains construction and lemma nodes that recognize patterns from L23 input.
 *
 * Key responsibilities:
 * - Receive L23 activations and match against construction patterns (feed-forward)
 * - Create partial constructions when pattern elements begin matching
 * - Confirm partial constructions when all pattern elements match
 * - Generate L23 feedback nodes for confirmed constructions (recursive composition)
 * - Generate predictions for next column position
 * - Maintain type index for efficient node lookup
 *
 * Design principle: Layer is a simple container. Partial constructions are Nodes
 * with metadata['is_partial'] = true. Node metadata determines behavior, not class hierarchy.
 */
class L5Layer
{
    /**
     * Position of this layer in the sequence
     */
    public readonly int $columnPosition;

    /**
     * Reference to parent column (for accessing constructions)
     */
    private ?CLNColumn $column = null;

    /**
     * All nodes in this layer (indexed by node ID)
     * Type-agnostic: contains both Node and Node instances
     */
    private array $nodes = [];

    /**
     * Type index for O(1) lookup by node type
     * Format: ['node_type' => ['node_id1', 'node_id2', ...]]
     */
    private array $typeIndex = [];

    /**
     * Node factory for creating nodes
     */
    private NodeFactory $factory;

    /**
     * Event registry for cross-position communication via node events
     */
    private ?NodeEventRegistry $eventRegistry = null;

    /**
     * Partial construction nodes (subset of nodes where metadata['is_partial'] = true)
     * Indexed by node ID for quick access
     */
    private array $partialConstructions = [];

    /**
     * Activation boosts applied to partial constructions (from lateral confirmations)
     * Format: ['partial_construction_id' => boost_amount]
     */
    private array $partialConstructionBoosts = [];

    /**
     * Maximum depth for recursive prediction generation
     * Prevents infinite loops when predictions trigger more predictions
     */
    private const MAX_PREDICTION_DEPTH = 3;

    /**
     * Current prediction generation depth (for loop prevention)
     */
    private int $predictionDepth = 0;

    /**
     * Create a new L5 Layer
     *
     * @param int $columnPosition Position in the sequence (0-based)
     * @param NodeFactory|null $factory Optional factory (creates default if null)
     * @param NodeEventRegistry|null $eventRegistry Optional event registry for node-centric event communication
     */
    public function __construct(
        int                $columnPosition,
        ?NodeFactory       $factory = null,
        ?NodeEventRegistry $eventRegistry = null
    )
    {
        $this->columnPosition = $columnPosition;
        $this->factory = $factory ?? new NodeFactory;
        $this->eventRegistry = $eventRegistry;
    }

    // ========================================================================
    // Node Management (type-agnostic)
    // ========================================================================

    /**
     * Confirm an evoked construction (single-element construction that matched input)
     *
     * Evoked constructions are complete matches that don't need further processing.
     * This method creates a confirmed construction node in L5.
     *
     * @param int $constructionId Construction ID
     * @param string $name Construction name
     * @param int $columnPosition Column position
     * @param int $spanLength Span length (usually 1 for evoked constructions)
     * @param array $metadata Additional metadata
     */
    public function confirmEvokedConstruction(
        int    $constructionId,
        string $name,
        int    $columnPosition,
        int    $spanLength,
        array  $metadata = []
    ): void
    {
        // Extract pattern from graph (same approach as L23Layer and partial constructions)
        $graph = $metadata['graph'] ?? [];
        $pattern = $this->extractPatternSequence($graph);

        // Create confirmed construction node in L5
        $constructionNode = $this->factory->createNode(
            nodeType: 'construction',
            layer: Layer::L5,
            metadata: array_merge([
                'construction_id' => $constructionId,
                'name' => $name,
                'column_position' => $columnPosition,
                'span_length' => $spanLength,
                'is_partial' => false,  // Not partial - this is confirmed
                'is_evoked' => true,     // Flag that this was evoked
                'is_confirmed' => true,  // Explicitly confirmed
                'pattern' => $pattern,   // Pattern extracted from graph
                'matched' => $pattern,   // All elements matched
            ], $metadata),
            id: "construction_l5_{$columnPosition}_{$constructionId}_" . time()
        );

        // Set activation to full
        $constructionNode->threshold = 0;

        // Add to L5
        $this->addNode($constructionNode);
    }

    /**
     * Add a node to this layer
     *
     * @param Node $node The node to add
     */
    public function addNode(Node $node): void
    {
        $this->nodes[$node->id] = $node;

        // Maintain type index for O(1) lookup
        $nodeType = $node->metadata['node_type'] ?? 'unknown';
        if (!isset($this->typeIndex[$nodeType])) {
            $this->typeIndex[$nodeType] = [];
        }
        $this->typeIndex[$nodeType][] = $node->id;

        // Track partial constructions separately for quick access
        if (($node->metadata['is_partial'] ?? false) === true) {
            $this->partialConstructions[$node->id] = $node;
        }
    }

    /**
     * Get a node by ID
     *
     * @param string $id Node ID
     */
    public function getNode(string $id): Node|null
    {
        return $this->nodes[$id] ?? null;
    }

    /**
     * Get all nodes of a specific type (filtered by metadata)
     *
     * Uses type index for O(1) lookup instead of O(N) filtering.
     *
     * @param string $nodeType Type from metadata (construction, mwe, lemma, etc.)
     * @return array Array of Node instances
     */
    public function getNodesByType(string $nodeType): array
    {
        $nodeIds = $this->typeIndex[$nodeType] ?? [];

        // Map IDs to actual node instances
        $nodes = [];
        foreach ($nodeIds as $id) {
            if (isset($this->nodes[$id])) {
                $nodes[$id] = $this->nodes[$id];
            }
        }

        return $nodes;
    }

    /**
     * Get all predicted nodes (not yet confirmed)
     *
     * Returns nodes that were created from predictions but haven't been
     * confirmed yet by backward compatibility checking.
     *
     * @return array Predicted nodes
     */
    public function getPredictedNodeByName(string $name): ?Node
    {
        $nodes = array_filter(
            $this->nodes,
            fn($node) => ($node->metadata['is_predicted'] ?? false) === true
                && ($node->metadata['prediction_confirmed'] ?? false) === false
                && ($node->metadata['name'] == $name)
        );
        return array_shift($nodes);
    }

    /**
     * Get waiting nodes
     *
     * @return array Waiting nodes
     */
    public function getWaitingNodeByName(string $name): array
    {
        $nodes = array_filter(
            $this->nodes,
            fn($node) => ($node->metadata['is_partial'] ?? false) === true
                && ($node->metadata['name'] == $name)
        );
        return $nodes;
    }

    /**
     * Remove a node by ID
     *
     * @param string $id Node ID to remove
     */
    public function removeNode(string $id): void
    {
        $node = $this->nodes[$id] ?? null;

        if ($node !== null) {
            // Clean up bidirectional connections before removing node
            // Remove this node from all its input nodes' output lists
            foreach ($node->getInputNodes() as $inputId => $inputData) {
                $inputNode = $inputData['node'] ?? null;
                if ($inputNode) {
                    $inputNode->removeOutput($id);
                }
            }

            // Remove this node from all its output nodes' input lists
            foreach ($node->getOutputNodes() as $outputId => $outputNode) {
                $outputNode->removeInput($id);
            }

            // Remove from type index
            $nodeType = $node->metadata['node_type'] ?? 'unknown';
            if (isset($this->typeIndex[$nodeType])) {
                $this->typeIndex[$nodeType] = array_filter(
                    $this->typeIndex[$nodeType],
                    fn($nodeId) => $nodeId !== $id
                );

                // Clean up empty type arrays
                if (empty($this->typeIndex[$nodeType])) {
                    unset($this->typeIndex[$nodeType]);
                }
            }
        }

        unset($this->nodes[$id]);
        unset($this->partialConstructions[$id]);
        unset($this->partialConstructionBoosts[$id]);
    }

    /**
     * Get all nodes in this layer
     *
     * @return array All nodes indexed by ID
     */
    public function getAllNodes(): array
    {
        return $this->nodes;
    }

    // ========================================================================
    // Activation from L23
    // ========================================================================

    /**
     * Set parent column reference
     *
     * @param CLNColumn $column The parent column
     */
    public function setColumn(CLNColumn $column): void
    {
        $this->column = $column;
    }

    /**
     * Get reference to parent column
     *
     * @return CLNColumn|null Parent column
     */
    public function getColumn(): ?CLNColumn
    {
        return $this->column;
    }

    /**
     * Receive input from L23 layer (feed-forward circuit)
     *
     * Processes L23 activations to match against construction patterns.
     * This is where pattern matching happens - L23 nodes are checked against
     * the first element of each construction pattern to create partial constructions.
     *
     * @param L23Layer $l23 The L23 layer in the same column
     */
    public function receiveL23Input(L23Layer $l23): void
    {
        // Get all activated L23 nodes
        // $l23Nodes = $l23->getAllNodes();
        $allL23Nodes = $l23->getAllNodes();
        $l23Nodes = [];
        foreach ($allL23Nodes as $l23Node) {
            // NEW: Skip construction nodes that confirmed predictions FIRST
            // These nodes should not propagate to L5 to create new partial constructions
            //            if (($l23Node->metadata['node_type'] ?? '') === 'construction' &&
            //                ($l23Node->metadata['prediction_confirmed'] ?? false)) {
            //                continue;
            //            }

            if (count($l23Node->getOutputNodes()) == 0) {
                $l23Nodes[] = $l23Node;
            }
        }

        // Get available constructions from column
        $constructions = $this->getAvailableConstructions();

        // Try to start new partial constructions
        $this->tryStartNewConstructions($l23Nodes, $constructions);
    }

    /**
     * Receive L23 construction nodes for pattern matching (parallel activation)
     *
     * When cross-column processing creates new L23 construction nodes at earlier
     * positions, those nodes propagate to L5 for pattern matching. This implements
     * the cortical network principle where columns are always active.
     *
     * Key behaviors:
     * - NODE REUSE: If construction with same name exists, UPDATE it (argument growth)
     * - NODE CREATE: If construction doesn't exist, CREATE new one
     * - LOOP PREVENTION: Track growth stages to prevent infinite loops
     *
     * Example: ARG construction at pos_1 grows as new elements are discovered:
     *   MOD → ARG (create)
     *   MOD_HEAD → ARG (update existing)
     *   ARG_REL → ARG (update existing)
     *
     * @param array $l23Nodes New L23 construction nodes to process
     * @param L23Layer $l23 The L23 layer (for reference)
     */
    public function receiveL23ConstructionNodes(array $l23Nodes, L23Layer $l23): void
    {
        if (empty($l23Nodes)) {
            return;
        }

        $matcher = new PatternMatcher;
        $constructions = $this->getAvailableConstructions();

        foreach ($l23Nodes as $l23Node) {
            // NEW: Skip construction nodes that confirmed predictions (defensive check)
            // These nodes should already be filtered by propagateNewL23ConstructionsToL5()
            if ($l23Node->metadata['prediction_confirmed'] ?? false) {
                continue;
            }

            $l23NodeName = $l23Node->metadata['name'] ?? 'UNKNOWN';

            // Try to match against construction patterns
            foreach ($constructions as $construction) {
                $constructionName = $construction['name'] ?? 'UNKNOWN';
                $graph = $construction['graph'] ?? [];
                $startingNodes = $this->findStartingNodes($graph);

                // Try each possible starting node
                foreach ($startingNodes as $startNode) {
                    if ($matcher->matchesNode([$l23Node], $startNode['node'])) {
                        // MATCH! This L23 construction can activate this pattern

                        // Check if we already have this construction (for REUSE)
                        $existingConstruction = $this->findConstructionByName($constructionName);

                        if ($existingConstruction) {
                            // NODE REUSE: Update existing construction (argument growth)
                            $this->updateConstructionGrowth(
                                construction: $existingConstruction,
                                newStage: $l23NodeName,
                                l23Node: $l23Node
                            );
                        } else {
                            // NODE CREATE: Create new construction
                            $this->createConstructionFromL23Node(
                                construction: $construction,
                                graph: $graph,
                                startNode: $startNode,
                                l23Node: $l23Node,
                                matcher: $matcher
                            );
                        }

                        // Only match once per L23 node
                        break 2;
                    }
                }
            }
        }
    }

    /**
     * Try to start new partial constructions based on L23 input
     *
     * Checks each construction pattern to see if its first element matches
     * the current L23 nodes. If so, creates a partial construction.
     *
     * Uses either GraphPatternMatcher (shared graph) or PatternMatcher (individual graphs)
     * based on configuration.
     *
     * @param array $l23Nodes Array of activated L23 nodes
     * @param array $constructions Array of compiled constructions
     */
    private function tryStartNewConstructions(array $l23Nodes, array $constructions): void
    {
        // NODE-CENTRIC Phase 1: Skip centralized construction activation if enabled
        if (config('cln.node_centric_phases.construction_activation', false)) {
            // Construction activation happens via L23 nodes calling triggerConstructionActivation()
            return;
        }

        // LEGACY PATH: Centralized construction activation
        // Check if shared graph pattern matching is enabled
        if (config('cln.pattern_matching.use_shared_graph', false)) {
            $this->tryStartNewConstructionsSharedGraph($l23Nodes);
        } else {
            $this->tryStartNewConstructionsIndividualGraphs($l23Nodes, $constructions);
        }
    }

    /**
     * Try to start new partial constructions using shared pattern graph (OPTIMIZED)
     *
     * Uses GraphPatternMatcher to check all patterns in parallel via the shared graph.
     * This is significantly faster than checking each construction individually.
     *
     * @param array $l23Nodes Array of activated L23 nodes
     */
    private function tryStartNewConstructionsSharedGraph(array $l23Nodes): void
    {
        $graphMatcher = new GraphPatternMatcher;
        $matcher = new PatternMatcher;

        // Find ALL matching patterns in one pass (parallel matching via shared graph)
        $matches = $graphMatcher->findMatchingPatterns($l23Nodes);

        foreach ($matches as $match) {
            $patternId = $match['pattern_id'];
            $startNodeId = $match['node_id'];
            $startNode = $match['node'];

            // Get construction metadata
            $constructionMeta = $graphMatcher->getConstructionMetadata($patternId);
            if ($constructionMeta === null) {
                continue; // Invalid construction
            }

            $constructionId = $constructionMeta['id'];
            $constructionName = $constructionMeta['name'];
            $compiledPattern = $constructionMeta['compiledPattern'] ?? [];
            $graph = $compiledPattern;

            // Extract pattern sequence
            $patternSequence = $this->extractPatternSequence($graph);

            // Initialize matched array: first element is true (we just matched it), rest are false
            $matched = array_fill(0, count($patternSequence), false);
            if (count($matched) > 0) {
                $matched[0] = true; // First element matched
            }

            // Initialize traversal state (using shared graph node ID)
            $traversalState = [
                'current_node_id' => $startNodeId,
                'path_taken' => [$startNodeId],
                'alternative_choices' => [],
                'repetition_state' => [],
                'bypassed_nodes' => [],
                'pattern_id' => $patternId,  // Track which pattern we're following
                'use_shared_graph' => true,  // Flag to use GraphPatternMatcher for advancement
            ];

            $partialConstruction = $this->createPartialConstruction(
                constructionId: $constructionId,
                metadata: [
                    'construction_id' => $constructionId,
                    'name' => $constructionName,
                    'pattern' => $patternSequence,
                    'graph' => $graph,
                    'graph_nodes' => $graph['nodes'] ?? [],
                    'traversal_state' => $traversalState,
                    'matched' => $matched,
                    'anchor_position' => $this->columnPosition,
                    'span_length' => 1,
                ]
            );

            // CIRCUIT 1: L23 → L5 (Feed-Forward)
            // Link matching L23 nodes to this construction
            $this->linkL23ToConstruction($l23Nodes, $partialConstruction, $startNode, $matcher);

            // Check if pattern is already complete (single-element patterns)
            if ($graphMatcher->isPatternComplete($startNodeId, $patternId)) {
                $this->confirmConstruction($partialConstruction->id);
            }
        }
    }

    /**
     * Try to start new partial constructions using individual pattern graphs (LEGACY)
     *
     * Original implementation: checks each construction's individual pattern graph.
     * Kept for backward compatibility when shared graph is disabled.
     *
     * @param array $l23Nodes Array of activated L23 nodes
     * @param array $constructions Array of compiled constructions
     */
    private function tryStartNewConstructionsIndividualGraphs(array $l23Nodes, array $constructions): void
    {
        $matcher = new PatternMatcher;

        foreach ($constructions as $construction) {
            $graph = $construction['graph'] ?? [];
            $graphNodes = $graph['nodes'] ?? [];

            // Find ALL possible starting nodes (handles alternatives at start)
            $startingNodes = $this->findStartingNodes($graph);

            // Try each possible starting node
            foreach ($startingNodes as $startNode) {
                if ($matcher->matchesNode($l23Nodes, $startNode['node'])) {
                    // MATCH! Create partial construction with graph traversal state

                    // Initialize traversal state
                    $traversalState = $this->initializeTraversalState($graph, $startNode['node_id']);

                    // Extract pattern sequence for matched array initialization
                    $patternSequence = $this->extractPatternSequence($graph);

                    // Initialize matched array: first element is true (we just matched it), rest are false
                    $matched = array_fill(0, count($patternSequence), false);
                    if (count($matched) > 0) {
                        $matched[0] = true; // First element matched
                    }

                    $partialConstruction = $this->createPartialConstruction(
                        constructionId: $construction['id'],
                        metadata: [
                            'construction_id' => $construction['id'],
                            'name' => $construction['name'] ?? 'UNKNOWN',
                            'pattern' => $patternSequence,
                            'graph' => $graph,
                            'graph_nodes' => $graphNodes,
                            'traversal_state' => $traversalState,
                            'matched' => $matched,
                            'anchor_position' => $this->columnPosition,
                            'span_length' => 1,
                        ]
                    );

                    // CIRCUIT 1: L23 → L5 (Feed-Forward)
                    // Link matching L23 nodes to this construction
                    $this->linkL23ToConstruction($l23Nodes, $partialConstruction, $startNode['node'], $matcher);

                    // Check if partial construction is already complete (for single-element patterns)
                    // Use new graph traversal method
                    if ($this->isPatternComplete($graph, $traversalState)) {
                        $this->confirmConstruction($partialConstruction->id);
                    }

                    // For alternatives at start, only create ONE partial per construction
                    // (we matched one branch, that's our choice)
                    break;
                }
            }
        }
    }

    /**
     * Check if partial construction is complete (all elements matched)
     *
     * @param Node $partialConstruction Partial construction node
     * @return bool True if all pattern elements are matched
     */
    private function isPartialConstructionComplete(Node $partialConstruction): bool
    {
        $traversalState = $partialConstruction->metadata['traversal_state'] ?? null;
        $graph = $partialConstruction->metadata['graph'] ?? null;

        if ($traversalState === null || $graph === null) {
            throw new \RuntimeException(
                'Partial construction missing required graph traversal state. ' .
                'All partial constructions must use graph-based pattern matching.'
            );
        }

        return $this->isPatternComplete($graph, $traversalState);
    }

    /**
     * Find first pattern node in graph (excluding START/END meta-nodes)
     *
     * @param array $graphNodes Array of graph nodes
     * @return array|null First pattern node or null
     */
    private function findFirstPatternNode(array $graphNodes): ?array
    {
        foreach ($graphNodes as $nodeId => $node) {
            $type = $node['type'] ?? '';
            if (!in_array($type, ['START', 'END'])) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Extract pattern sequence from graph by walking edges
     *
     * Converts graph structure (nodes + edges) into linear sequence of pattern elements.
     * Walks from START to END following edges.
     *
     * @param array $graph Pattern graph with nodes and edges
     * @return array Linear sequence of pattern element values
     */
    public function extractPatternSequence(array $graph): array
    {
        $sequence = [];
        $nodes = $graph['nodes'] ?? [];
        $edges = $graph['edges'] ?? [];

        // Find START node
        $currentNode = null;
        foreach ($nodes as $id => $node) {
            if (($node['type'] ?? '') === 'START') {
                $currentNode = $id;
                break;
            }
        }

        if ($currentNode === null) {
            return $sequence;
        }

        // Walk edges from START to END
        $visited = [];
        while ($currentNode !== null) {
            if (isset($visited[$currentNode])) {
                break; // Cycle detected
            }
            $visited[$currentNode] = true;

            // Find next edge from current node
            $nextNode = null;
            foreach ($edges as $edge) {
                if ($edge['from'] === $currentNode) {
                    $nextNode = $edge['to'];
                    break;
                }
            }

            if ($nextNode === null) {
                break;
            }

            $node = $nodes[$nextNode] ?? null;
            if ($node && !in_array($node['type'] ?? '', ['START', 'END'])) {
                // Add value from LITERAL nodes, or pos from SLOT nodes
                $value = $node['value'] ?? $node['pos'] ?? '*';

                // Strip quotes from LITERAL values
                if (($node['type'] ?? '') === 'LITERAL') {
                    $value = trim($value, '"\'');
                }

                $sequence[] = $value;
            }

            $currentNode = $nextNode;
        }

        return $sequence;
    }

    /**
     * Find graph node by pattern index
     *
     * Maps from a linear pattern index (0, 1, 2, ...) back to the actual
     * graph node at that position by walking the pattern graph from START to END.
     * This is needed for prediction generation which uses array indices from the matched array.
     *
     * @param array $graphData Full graph with nodes/edges
     * @param int $index Pattern element index (0-based)
     * @return array|null Graph node at that index, or null if not found
     */
    private function findGraphNodeByIndex(array $graphData, int $index): ?array
    {
        $nodes = $graphData['nodes'] ?? $graphData;
        $edges = $graphData['edges'] ?? [];

        $patternNodes = [];

        // Find START node
        $currentNodeId = null;
        foreach ($nodes as $id => $node) {
            if (($node['type'] ?? '') === 'START') {
                $currentNodeId = $id;
                break;
            }
        }

        if ($currentNodeId === null) {
            return null;
        }

        // Walk edges from START to END
        $visited = [];
        while ($currentNodeId !== null) {
            if (isset($visited[$currentNodeId])) {
                break; // Cycle detected
            }
            $visited[$currentNodeId] = true;

            // Find next edge from current node
            $nextNodeId = null;
            foreach ($edges as $edge) {
                if ($edge['from'] === $currentNodeId) {
                    $nextNodeId = $edge['to'];
                    break;
                }
            }

            if ($nextNodeId === null) {
                break;
            }

            $node = $nodes[$nextNodeId] ?? null;
            if ($node && !in_array($node['type'] ?? '', ['START', 'END', 'INTERMEDIATE', 'REP_CHECK'])) {
                $patternNodes[] = $node;
            }

            $currentNodeId = $nextNodeId;
        }

        // Return node at specified index
        return $patternNodes[$index] ?? null;
    }

    /**
     * Get available constructions from parent column
     *
     * @return array Array of compiled constructions
     */
    private function getAvailableConstructions(): array
    {
        return $this->column?->getConstructions() ?? [];
    }

    // ========================================================================
    // Partial Construction Management
    // ========================================================================

    /**
     * Create a partial construction node
     *
     * Partial constructions represent partially activated constructions that predict
     * what should come next based on the construction pattern.
     *
     * @param int $constructionId Construction ID from database
     * @param array $metadata Metadata (name, pattern, matched, etc.)
     * @return Node The created partial construction node
     */
    public function createPartialConstruction(int $constructionId, array $metadata): Node
    {
        $partialId = "partial_{$this->columnPosition}_{$constructionId}";
        $name = $metadata['name'] ?? 'UNKNOWN';

        // Check if partial already exists - if so, UPDATE it instead of recreating
        // This preserves prediction links and other output connections
//        $existingPartial = $this->getNode($partialId);
//        if ($existingPartial !== null) {
//            // UPDATE existing partial's metadata instead of creating new one
//            $existingPartial->metadata['name'] = $name;
//            $existingPartial->metadata['pattern'] = $metadata['pattern'] ?? [];
//            $existingPartial->metadata['pattern_id'] = $metadata['pattern_id'] ?? 0;
//            $existingPartial->metadata['matched'] = $metadata['matched'] ?? [];
//            $existingPartial->metadata['is_partial'] = true;
//            $existingPartial->metadata['construction_id'] = $constructionId;
//
//            $existingPartial->metadata['is_predicted'] = $metadata['is_predicted'] ?? false;
//            $existingPartial->metadata['prediction_strength'] = $metadata['strength'] ?? 0;
//            $existingPartial->metadata['source_construction_id'] = $metadata['construction_id'] ?? 0;
//            $existingPartial->metadata['source_partial_id'] = $metadata['partial_construction_id'] ?? null;
//
//            // Update additional metadata
//            if (isset($metadata['graph_nodes'])) {
//                $existingPartial->metadata['graph_nodes'] = $metadata['graph_nodes'];
//            }
//            if (isset($metadata['graph'])) {
//                $existingPartial->metadata['graph'] = $metadata['graph'];
//            }
//            if (isset($metadata['traversal_state'])) {
//                $existingPartial->metadata['traversal_state'] = $metadata['traversal_state'];
//            }
//            if (isset($metadata['span_length'])) {
//                $existingPartial->metadata['span_length'] = $metadata['span_length'];
//            }
//
//            return $existingPartial;
//        }

        // Add is_partial flag to metadata
//        $metadata['is_partial'] = true;
//        $metadata['construction_id'] = $constructionId;

        // Create partial construction node using factory
        $partialConstruction = $this->factory->createConstructionNode(
            constructionId: $constructionId,
            name: $name,
            pattern: $metadata['pattern'] ?? [],
            pattern_id: $metadata['pattern_id'] ?? 0,
            matched: $metadata['matched'] ?? [],
            isPartial: true,
            anchorPosition: $metadata['anchor_position'] ?? $this->columnPosition,
            id: $partialId,
            is_predicted: false,
            prediction_strength: 0,
            source_construction_id: 0,
            source_partial_id: 0,
        );

        // Add additional metadata that factory doesn't pass through
        // CRITICAL: graph_nodes, graph, and traversal_state needed for cross-column pattern matching
        if (isset($metadata['graph_nodes'])) {
            $partialConstruction->metadata['graph_nodes'] = $metadata['graph_nodes'];
        }
        if (isset($metadata['graph'])) {
            $partialConstruction->metadata['graph'] = $metadata['graph'];
        }
        if (isset($metadata['traversal_state'])) {
            $partialConstruction->metadata['traversal_state'] = $metadata['traversal_state'];
        }
        if (isset($metadata['span_length'])) {
            $partialConstruction->metadata['span_length'] = $metadata['span_length'];
        }

        $this->addNode($partialConstruction);

        // Subscribe partial to future position events for node-centric pattern matching
//        if (config('cln.node_centric_phases.pattern_matching', false) && $this->eventRegistry) {
//            $this->subscribePartialToFuturePositions($partialConstruction);
//        }

        // NODE-CENTRIC Phase 2: Auto-generate initial prediction when partial is created
        if (config('cln.node_centric_phases.prediction_generation', false)) {
            $manager = $this->column?->getSequenceManager();
            if ($manager !== null) {
//                debug($partialConstruction);
                $partialConstruction->generatePrediction($this, $manager);
            }
        }

        return $partialConstruction;
    }

    /**
     * Subscribe partial construction to future position events
     *
     * When pattern matching feature is enabled, partials subscribe to events
     * at future positions so they can check themselves when tokens arrive.
     *
     * @param Node $partialConstruction The partial construction node
     */
    private function subscribePartialToFuturePositions(Node $partialConstruction): void
    {
        $anchorPosition = $partialConstruction->metadata['anchor_position'] ?? $this->columnPosition;
        $pattern = $partialConstruction->metadata['pattern'] ?? [];
        $maxLength = count($pattern);

        // Subscribe to positions where this partial might need to advance
        // Range: anchor+1 to anchor+maxLength (reasonable upper bound)
        for ($targetPosition = $anchorPosition + 1; $targetPosition <= $anchorPosition + $maxLength + 5; $targetPosition++) {
            $this->eventRegistry->subscribeToPosition(
                $targetPosition,
                NodeEvent::ACTIVATED,
                function ($data) use ($partialConstruction, $targetPosition) {
                    $l23Nodes = $data['l23_nodes'] ?? [];
                    if (empty($l23Nodes)) {
                        return;
                    }

                    // Let partial check if it should advance at this position
                    $partialConstruction->tryAdvance($l23Nodes, $targetPosition);
                }
            );
        }
    }

    /**
     * Update source partial construction when prediction is confirmed
     *
     * Updates the 'matched' array in the source partial construction
     * and checks if the pattern is now complete.
     *
     * @param Node $construction
     */
    private function updatePartialConstruction(Node $construction, Node $source): void
    {
        // Update matched array using pattern_index from prediction metadata
        $matched = $construction->metadata['matched'] ?? [];
        $matchedIndex = $source->metadata['pattern_index'] ?? null;

        if ($matchedIndex !== null && isset($matched[$matchedIndex])) {
            $matched[$matchedIndex] = true;
            $construction->metadata['matched'] = $matched;

            // Check if pattern complete
            if (!in_array(false, $matched, true)) {
                // All elements matched - confirm construction
                $this->confirmConstruction($construction->id);
            }
        }
    }

    /**
     * Confirm partial construction to full construction
     *
     * When all pattern elements are matched, partial construction becomes a confirmed construction.
     *
     * NODE-CENTRIC: When completion_checking phase is enabled, delegates to the node's
     * self-confirmation method.
     *
     * @param string $partialConstructionId ID of partial construction to confirm
     */
    public function confirmConstruction(string $partialConstructionId): void
    {
        $partial = $this->partialConstructions[$partialConstructionId] ?? null;

        if (!$partial) {
            return;
        }

        // NODE-CENTRIC Phase 4: Delegate to node's self-confirmation
        if (config('cln.node_centric_phases.completion_checking', false)) {
            $partial->confirmConstruction($this);
            // Remove from partial constructions tracking
            unset($this->partialConstructions[$partialConstructionId]);
            $partial->triggerConstructionActivation($this);

//            return;
        }

        // LEGACY PATH: Layer-orchestrated confirmation
        // Change metadata to indicate this is no longer partial
//        $partial->metadata['is_partial'] = false;
//        $partial->metadata['node_type'] = 'construction';

        // Remove from partial constructions tracking
//        unset($this->partialConstructions[$partialConstructionId]);

        // NEW: Create L23 feedback node for recursive composition
        //        $this->createL23FeedbackNode($partial);
    }

    /**
     * Create L23 construction node as feedback from completed L5 construction
     *
     * CIRCUIT 2B: L5 → L23 Feedback (construction completion) - UNIDIRECTIONAL
     *
     * When a construction completes in L5, create a corresponding node in L23
     * at the anchor position. This enables recursive composition:
     * - Word tokens → MWE construction
     * - MWE construction → Phrase construction
     * - Phrase construction → Clause construction
     *
     * SIMPLIFIED APPROACH (to prevent infinite loops):
     * - Creates L23 node without immediate propagation back to L5
     * - The L23 node will be available for pattern matching in next processing cycle
     * - Unidirectional: L5 → L23 only (no immediate feedback loop)
     *
     * @param Node $construction The completed L5 construction node
     */
    private function createL23FeedbackNode(Node $construction): void
    {
        // Check configuration
        if (!config('cln.composition.enable_recursive_composition', true)) {
            return;
        }

        // Prevent duplicate feedback creation
        if ($construction->metadata['l23_feedback_created'] ?? false) {
            return;
        }

        // Check composition depth limit
        $currentDepth = $construction->metadata['composition_depth'] ?? 0;
        $maxDepth = config('cln.composition.max_depth', 3);

        if ($currentDepth >= $maxDepth) {
            return; // Max depth reached
        }

        // Get L23 layer from parent column
        if ($this->column === null) {
            return;
        }

        $l23 = $this->column->getL23();
        //        debug("createL23FeedbackNode:", array_keys($l23->getAllNodes()));

        // Check by name if the L23 node already exists
        $existingNode = $l23->getNodesByTypeName('construction', $construction->metadata['name'] ?? 'UNKNOWN');

        if (is_null($existingNode)) {
            // Extract metadata
            $constructionId = $construction->metadata['construction_id'] ?? 0;
            $name = $construction->metadata['name'] ?? 'UNKNOWN';
            $spanLength = $construction->metadata['span_length'] ?? 1;

            // Create L23 construction node (no immediate propagation)
            $l23ConstructionNode = $l23->receiveConstructionFeedback(
                constructionId: $constructionId,
                name: $name,
                spanLength: $spanLength,
                metadata: [
                    'pattern' => $construction->metadata['pattern'] ?? [],
                    'graph' => $construction->metadata['graph'] ?? [],
                    'source_l5_node_id' => $construction->id,
                    'composition_depth' => $currentDepth + 1,
                ]
            );

            // Create bidirectional feedback link: L5 construction ↔ L23 construction
            // This is a STRUCTURAL link (for graph traversal/visualization)
            // It does NOT create an activation loop - activation flow is controlled separately
            $construction->addOutput($l23ConstructionNode);
            $l23ConstructionNode->addInput($construction);

            // Keep metadata reference for backward compatibility
            $construction->metadata['l23_feedback_node_id'] = $l23ConstructionNode->id;

            // Mark feedback created
            $construction->metadata['l23_feedback_created'] = true;

            // NEW: Immediately check if this construction node confirms a prediction
            // This prevents it from being matched against other patterns in the same cycle
            if ($this->column) {
                $this->column->checkIfConstructionConfirmsPrediction($l23ConstructionNode);
            }

            // NOTE: We do NOT propagate back to L5 immediately to prevent infinite ACTIVATION loops.
            // The bidirectional LINK exists, but the L23 construction node will be picked up
            // in the next pattern matching cycle to control activation flow.
        }
    }

    /**
     * Expire partial construction
     *
     * When predictions don't match or construction is no longer viable,
     * remove the partial construction.
     *
     * @param string $partialConstructionId ID of partial construction to expire
     */
    public function expirePartialConstruction(string $partialConstructionId): void
    {
        $this->removeNode($partialConstructionId);
    }

    /**
     * Get all partial constructions
     *
     * @return array Array of partial construction Nodes
     */
    public function getPartialConstructions(): array
    {
        return $this->partialConstructions;
    }

    /**
     * Boost a partial construction (from lateral confirmation)
     *
     * When predictions match in the next column, that column sends confirmation
     * back to boost this partial construction's activation.
     *
     * @param string $partialConstructionId ID of partial construction to boost
     * @param float $boostAmount Amount to boost (0-1)
     */
    public function boostPartialConstruction(string $partialConstructionId, float $boostAmount): void
    {
        $partial = $this->partialConstructions[$partialConstructionId] ?? null;

        if (!$partial || !($partial instanceof Node)) {
            return;
        }

        // Track cumulative boosts
        if (!isset($this->partialConstructionBoosts[$partialConstructionId])) {
            $this->partialConstructionBoosts[$partialConstructionId] = 0.0;
        }

        $this->partialConstructionBoosts[$partialConstructionId] += $boostAmount;

        // Boosts could modify activation threshold or priority
        // Exact mechanism TBD in Phase 5
    }

    /**
     * Get boost amount for a partial construction
     *
     * @param string $partialConstructionId Partial construction ID
     * @return float Total boost received
     */
    public function getPartialConstructionBoost(string $partialConstructionId): float
    {
        return $this->partialConstructionBoosts[$partialConstructionId] ?? 0.0;
    }

    // ========================================================================
    // Prediction Generation
    // ========================================================================

    /**
     * Generate predictions for same column (NEW FLOW)
     *
     * Based on active partial constructions and their patterns, predict what should
     * come next in the pattern. Creates ACTUAL predicted nodes in L23 at the SAME
     * column position.
     *
     * When a token arrives at the NEXT column, backward compatibility checking
     * will activate matching predicted nodes from the PREVIOUS column.
     *
     * @param int $targetPosition Target column position (typically same as current, or next for recursive)
     * @return array Array of Prediction objects (for tracking/debugging)
     */
    public function generatePredictions(int $targetPosition): array
    {
        // Depth checking for loop prevention
        if ($this->predictionDepth >= self::MAX_PREDICTION_DEPTH) {
            return []; // Prevent infinite recursion
        }

        $this->predictionDepth++;

        // NODE-CENTRIC Phase 2: Use node-driven prediction generation
//        if (config('cln.node_centric_phases.prediction_generation', false)) {
        $this->generatePredictionsNodeCentric();
        $this->predictionDepth--;

        return []; // Return empty array since predictions are registered with manager
//        }

        // LEGACY PATH: Centralized prediction generation
//        $predictions = [];
//        $matcher = new PatternMatcher;
//
//        foreach ($this->partialConstructions as $partial) {
//            // Use full graph if available, otherwise fall back to graph_nodes (for backward compatibility)
//            $graphData = $partial->metadata['graph'] ?? $partial->metadata['graph_nodes'] ?? [];
//            $matched = $partial->metadata['matched'] ?? [];
//            $constructionId = $partial->metadata['construction_id'] ?? 0;
//            $anchorPosition = $partial->metadata['anchor_position'] ?? $this->columnPosition;
//
//            // Find the next unmatched element in the pattern
//            $expectedIndex = null;
//            foreach ($matched as $idx => $isMatched) {
//                if (! $isMatched) {
//                    $expectedIndex = $idx;
//                    break;
//                }
//            }
//
//            // If all elements matched or no unmatched found, skip
//            if ($expectedIndex === null) {
//                continue;
//            }
//
//            // Calculate ACTUAL target position for this prediction
//            // NEW FLOW: Predicted nodes are created at the SAME position as the partial construction
//            // NOT at anchor + expectedIndex (future position)
//            // The backward search will find these predictions when future tokens arrive
//            $actualTargetPosition = $anchorPosition;
//
//            debug('generatePredictions:'.$partial->metadata['name'].'   expectedIndex='.$expectedIndex.'   anchorPosition='.$anchorPosition.'   actualTargetPosition='.$actualTargetPosition);
//            // debug($partial); // REMOVED: Causes memory overflow when printing entire node structure
//
//            // Get next graph node using pattern graph navigation
//            $nextNode = $this->findGraphNodeByIndex($graphData, $expectedIndex);
//
//            if (! $nextNode) {
//                continue;
//            }
//
//            // Extract prediction type and value from graph node
//            $predictionType = $matcher->getPredictionType($nextNode);
//            $predictionValue = $matcher->extractPredictedValue($nextNode);
//
//            // Calculate strength including boosts
//            $strength = $this->calculatePartialConstructionStrength($partial);
//
//            // Skip weak predictions
//            if ($strength < config('cln.activation.partial_construction_threshold', 0.25)) {
//                continue;
//            }
//
//            $predictions[] = new Prediction(
//                sourcePosition: $this->columnPosition,
//                targetPosition: $actualTargetPosition, // Use calculated target position
//                type: $predictionType,
//                value: $predictionValue,
//                strength: $strength,
//                constructionId: $constructionId,
//                metadata: [
//                    'partial_construction_id' => $partial->id,
//                    'pattern_index' => $expectedIndex,
//                ]
//            );
//        }
//
//        // DUAL PATH: Run both old and new prediction systems in parallel
//        // TODO: Remove old path after migration is complete
//        if (config('cln.predictions.centralized_manager', false)) {
//            // NEW PATH: Send predictions to centralized manager
//            $this->sendPredictionsToManager($predictions);
//        } else {
//            // OLD PATH: Create predicted nodes in L23 directly
//            $this->createPredictedNodesInL23($predictions);
//        }
//
//        $this->predictionDepth--;
//
//        return $predictions; // Return for tracking/debugging
    }

    /**
     * Generate predictions using node-centric approach (NODE-CENTRIC Phase 2)
     *
     * Each partial construction generates its own prediction and registers it
     * with the centralized ColumnSequenceManager.
     *
     * Part of Phase 2 of node-centric refactoring.
     */
    private function generatePredictionsNodeCentric(): void
    {
        // Get ColumnSequenceManager from column
        if ($this->column === null) {
            return;
        }

        $manager = $this->column->getSequenceManager();
        if ($manager === null) {
            return; // No manager available
        }

        // Each partial generates its own prediction
        foreach ($this->partialConstructions as $partial) {
            $partial->generatePrediction($this, $manager);
        }
    }

    /**
     * Create predicted nodes in L23 layer based on predictions
     *
     * NEW FLOW: Predictions now create ACTUAL nodes in L23, each at its calculated target position.
     * Nodes are marked as 'predicted' and initially NOT activated.
     *
     * When a compatible token arrives, backward compatibility checking activates matching predicted nodes.
     *
     * @param array $predictions Array of Prediction objects (each with its own targetPosition)
     */
    private function createPredictedNodesInL23(array $predictions): void
    {
        if ($this->column === null || empty($predictions)) {
            return;
        }

        // Group predictions by target position
        $predictionsByPosition = [];
        foreach ($predictions as $prediction) {
            $targetPos = $prediction->targetPosition;
            if (!isset($predictionsByPosition[$targetPos])) {
                $predictionsByPosition[$targetPos] = [];
            }
            $predictionsByPosition[$targetPos][] = $prediction;
        }

        // Process each target position
        foreach ($predictionsByPosition as $targetPosition => $positionPredictions) {
            // Get the target column's L23 layer
            $targetL23 = $this->getL23ForPosition($targetPosition);
            if ($targetL23 === null) {
                continue; // Target column doesn't exist yet
            }

            foreach ($positionPredictions as $prediction) {
                $nodeId = sprintf('predicted_%d_%s_%s', $targetPosition, $prediction->type, $prediction->value);

                // Check if predicted node already exists
                if ($targetL23->getNode($nodeId) !== null) {
                    continue; // Already predicted
                }

                // Create predicted node using factory
                $predictedNode = $this->factory->createPredictedNode(
                    type: $prediction->type,
                    value: $prediction->value,
                    columnPosition: $targetPosition,
                    metadata: [
                        'is_predicted' => true,
                        'prediction_strength' => $prediction->strength,
                        'source_construction_id' => $prediction->constructionId,
                        'source_partial_id' => $prediction->metadata['partial_construction_id'] ?? null,
                    ]
                );

                // Add to target L23 WITHOUT activation
                $targetL23->addNode($predictedNode);

                // Auto-subscribe predicted node to target position for node-centric prediction checking
                if (config('cln.node_centric_phases.predictions', false) && $this->eventRegistry) {
                    $predictedNode->autoSubscribeToPosition($targetPosition, $this->eventRegistry);
                }

                // Link L5 partial construction → predicted node (prediction link)
                $partialId = $prediction->metadata['partial_construction_id'] ?? null;
                if ($partialId !== null) {
                    $partial = $this->getNode($partialId);
                    if ($partial !== null) {
                        // Correct direction: partial predicts → predicted node
                        $partial->addOutput($predictedNode);
                        $predictedNode->addInput($partial);
                    }
                }
            }
        }
    }

    /**
     * Create predicted nodes in L5 layer based on prediction
     *
     * @param array $prediction Array of Prediction object data
     */
    public function createPredictedNode(int $constructionId, array $metadata): Node
    {
        $partialId = "partial_{$this->columnPosition}_{$constructionId}";
        $name = $metadata['name'] ?? 'UNKNOWN';

        // Check if partial already exists - if so, UPDATE it instead of recreating
        // This preserves prediction links and other output connections
        $existingPartial = $this->getNode($partialId);
        if ($existingPartial !== null) {
            // UPDATE existing partial's metadata instead of creating new one
            $existingPartial->metadata['name'] = $name;
            $existingPartial->metadata['pattern'] = $metadata['pattern'] ?? [];
            $existingPartial->metadata['pattern_id'] = $metadata['pattern_id'] ?? 0;
            $existingPartial->metadata['matched'] = $metadata['matched'] ?? [];
            $existingPartial->metadata['is_partial'] = true;
            $existingPartial->metadata['construction_id'] = $constructionId;

            $existingPartial->metadata['is_predicted'] = true;
            $existingPartial->metadata['prediction_strength'] = $metadata['strength'];
            $existingPartial->metadata['source_construction_id'] = $metadata['construction_id'];
            $existingPartial->metadata['source_partial_id'] = $metadata['partial_construction_id'] ?? null;

            // Update additional metadata
            if (isset($metadata['graph_nodes'])) {
                $existingPartial->metadata['graph_nodes'] = $metadata['graph_nodes'];
            }
            if (isset($metadata['graph'])) {
                $existingPartial->metadata['graph'] = $metadata['graph'];
            }
            if (isset($metadata['traversal_state'])) {
                $existingPartial->metadata['traversal_state'] = $metadata['traversal_state'];
            }
            if (isset($metadata['span_length'])) {
                $existingPartial->metadata['span_length'] = $metadata['span_length'];
            }

            return $existingPartial;
        }

        // Add is_partial flag to metadata
        $metadata['is_partial'] = true;
        $metadata['construction_id'] = $constructionId;

        // Create partial construction node using factory
        $predictedNode = $this->factory->createConstructionNode(
            constructionId: $constructionId,
            name: $name,
            pattern: $metadata['pattern'] ?? [],
            pattern_id: $metadata['pattern_id'] ?? 0,
            matched: $metadata['matched'] ?? [],
            isPartial: true,
            anchorPosition: $metadata['anchor_position'] ?? $this->columnPosition,
            id: $partialId,
            is_predicted: $metadata['is_predicted'] ?? false,
            prediction_strength: $metadata['prediction_strength'] ?? 0,
            source_construction_id: $metadata['source_construction_id'] ?? 0,
            source_partial_id: $metadata['source_partial_id'] ?? 0,
        );

        // Add additional metadata that factory doesn't pass through
        // CRITICAL: graph_nodes, graph, and traversal_state needed for cross-column pattern matching
        if (isset($metadata['graph_nodes'])) {
            $predictedNode->metadata['graph_nodes'] = $metadata['graph_nodes'];
        }
        if (isset($metadata['graph'])) {
            $predictedNode->metadata['graph'] = $metadata['graph'];
        }
        if (isset($metadata['traversal_state'])) {
            $predictedNode->metadata['traversal_state'] = $metadata['traversal_state'];
        }
        if (isset($metadata['span_length'])) {
            $predictedNode->metadata['span_length'] = $metadata['span_length'];
        }
        //$nodeId = sprintf('predicted_%d_%s_%s', $targetPosition, $prediction->type, $prediction->value);

        // Check if predicted node already exists
        //if ($this->getNode($prediction->construction_id) !== null) {
        //reuse
        //}

        // Create predicted node using factory
//        $predictedNode = $this->factory->createPredictedNode(
//            type: $prediction->type,
//            value: $prediction->value,
//            columnPosition: $prediction->target_position,
//            metadata: [
//                'is_predicted' => true,
//                'prediction_strength' => $prediction->strength,
//                'source_construction_id' => $prediction->construction_id,
//                'source_partial_id' => $prediction->metadata['partial_construction_id'] ?? null,
//            ]
//        );

//        // Add to target L23 WITHOUT activation
//        $this->addNode($predictedNode);
//
//        // Auto-subscribe predicted node to target position for node-centric prediction checking
////                if (config('cln.node_centric_phases.predictions', false) && $this->eventRegistry) {
////                    $predictedNode->autoSubscribeToPosition($targetPosition, $this->eventRegistry);
////                }
//
//        // Link L5 partial construction → predicted node (prediction link)
//        $partialId = $prediction->metadata['partial_construction_id'] ?? null;
//        if ($partialId !== null) {
//            $partial = $this->getNode($partialId);
//            if ($partial !== null) {
//                // Correct direction: partial predicts → predicted node
////                $partial->addOutput($predictedNode);
////                $predictedNode->addInput($partial);
//                $partial->addInput($predictedNode);
//                $predictedNode->addOutput($partial);
//            }
//        }
////            }
////        }
        return $predictedNode;
    }

    /**
     * Send predictions to ColumnSequenceManager (centralized prediction control)
     *
     * NEW ARCHITECTURE: Instead of creating predicted nodes in L23 directly,
     * predictions are registered with the ColumnSequenceManager.
     * When L23 nodes are created, they query the manager for waiting predictions.
     *
     * @param array $predictions Array of Prediction objects
     */
    private function sendPredictionsToManager(array $predictions): void
    {
        if ($this->column === null || empty($predictions)) {
            return;
        }

        $manager = $this->column->getSequenceManager();
        if ($manager === null) {
            return; // Fallback: no manager available
        }

        foreach ($predictions as $prediction) {
            // Extract construction name from partial metadata
            $partialId = $prediction->metadata['partial_construction_id'] ?? null;
            $constructionName = 'UNKNOWN';

            if ($partialId !== null) {
                $partial = $this->getNode($partialId);
                if ($partial !== null && ($partial instanceof Node)) {
                    $constructionName = $partial->metadata['name'] ?? 'UNKNOWN';
                }
            }

            // Register prediction with manager
            $manager->registerPrediction(
                constructionName: $constructionName,
                sourceColumn: $this->columnPosition,
                type: $prediction->type,
                value: $prediction->value,
                strength: $prediction->strength,
                sourcePartialId: $partialId ?? '',
                constructionId: $prediction->constructionId,
                metadata: $prediction->metadata
            );
        }
    }

    /**
     * Get L23 layer for a given position
     *
     * @param int $position Target position
     * @return mixed|null L23Layer or null if position doesn't exist
     */
    private function getL23ForPosition(int $position)
    {
        if ($position === $this->columnPosition) {
            return $this->column->getL23();
        }

        // Navigate to target position
        $targetColumn = $this->column;
        $currentPos = $this->columnPosition;

        if ($position > $currentPos) {
            // Navigate forward
            while ($currentPos < $position) {
                $targetColumn = $targetColumn->getNextColumn();
                if ($targetColumn === null) {
                    return null;
                }
                $currentPos++;
            }
        } elseif ($position < $currentPos) {
            // Navigate backward
            while ($currentPos > $position) {
                $targetColumn = $targetColumn->getPreviousColumn();
                if ($targetColumn === null) {
                    return null;
                }
                $currentPos--;
            }
        }

        return $targetColumn->getL23();
    }

    /**
     * Get index of next unmatched element in pattern
     *
     * @param array $matched Boolean array of matched elements
     * @return int|null Index of next unmatched, or null if all matched
     */
    private function getNextUnmatchedIndex(array $matched): ?int
    {
        foreach ($matched as $index => $isMatched) {
            if (!$isMatched) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Calculate partial construction activation strength
     *
     * Stronger partial constructions (more elements matched + boosts) make stronger predictions.
     *
     * @param Node $partial The partial construction node
     * @return float Strength value (0-1)
     */
    private function calculatePartialConstructionStrength(Node $partial): float
    {
        $matched = $partial->metadata['matched'] ?? [];
        $pattern = $partial->metadata['pattern'] ?? [];

        if (empty($pattern)) {
            return 0.0;
        }

        // Base strength: proportion of elements matched
        $matchedCount = count(array_filter($matched));
        $baseStrength = $matchedCount / count($pattern);

        // Add confirmation boosts
        $boost = $this->getPartialConstructionBoost($partial->id);

        // Combine (capped at 1.0)
        return min(1.0, $baseStrength + $boost);
    }

    /**
     * Determine element type from pattern element
     *
     * @param string $element Pattern element (word, POS tag, feature)
     * @return string Type ('word', 'pos', 'feature')
     */
    private function determineElementType(string $element): string
    {
        // Simple heuristic:
        // - ALL_CAPS = POS tag
        // - Contains "=" = feature
        // - Otherwise = word

        if (ctype_upper(str_replace('_', '', $element))) {
            return 'pos';
        }

        if (str_contains($element, '=')) {
            return 'feature';
        }

        return 'word';
    }

    // ========================================================================
    // Competition
    // ========================================================================

    /**
     * Apply lateral competition between constructions
     *
     * Mutually exclusive constructions compete. Stronger ones inhibit weaker ones.
     * This prevents multiple overlapping constructions from activating.
     */
    public function applyCompetition(): void
    {
        // Find competing constructions (overlapping positions/patterns)
        // Apply inhibition based on relative strength
        // This will be fully implemented in Phase 5
    }

    // ========================================================================
    // Introspection
    // ========================================================================

    /**
     * Get total activation level of this layer
     *
     * @return float Sum of activations from all nodes
     */
    public function getTotalActivation(): float
    {
        $total = 0.0;

        foreach ($this->nodes as $node) {
            if ($node instanceof Node && $node->isFired()) {
                // For partial constructions, include boost in activation
                $activation = 1.0;
                if (isset($this->partialConstructions[$node->id])) {
                    $activation += $this->getPartialConstructionBoost($node->id);
                }
                $total += $activation;
            } elseif ($node instanceof Node && $node->isActivated()) {
                $total += 1.0;
            }
        }

        return $total;
    }

    /**
     * Get array representation of this layer
     *
     * @return array Layer state as array
     */
    public function toArray(): array
    {
        return [
            'column_position' => $this->columnPosition,
            'layer' => 'L5',
            'node_count' => count($this->nodes),
            'partial_construction_count' => count($this->partialConstructions),
            'nodes' => array_map(
                fn($node) => [
                    'id' => $node->id,
                    'type' => $node instanceof Node ? 'Node' : 'Node',
                    'metadata' => $node->metadata,
                    'boost' => isset($this->partialConstructions[$node->id])
                        ? $this->getPartialConstructionBoost($node->id)
                        : 0.0,
                ],
                $this->nodes
            ),
            'total_activation' => $this->getTotalActivation(),
        ];
    }

    /**
     * Link L23 nodes to L5 construction (Circuit 1: L23 → L5 feed-forward)
     *
     * Establishes connections from matching L23 nodes to the construction node.
     * This creates the feed-forward activation circuit within the column.
     *
     * @param Node $node L23 node
     * @param Node $construction The construction node to link to
     * @param array $graphNode The pattern graph node that matched
     * @param PatternMatcher $matcher Pattern matcher for finding matches
     */
    public function linkNodeToConstruction(Node $node, Node $construction, array $graphNode, PatternMatcher $matcher): void
    {
        // Find which L23 nodes actually matched the pattern
        // Note: For SLOT patterns with constraints, we need to pass ALL l23Nodes
        // so that matchConstraint() can find the feature nodes
//        foreach ($l23Nodes as $node) {
        // Check if this specific node matches the graph node requirement
        // IMPORTANT: Pass all L23 nodes, not just this one, so constraint checking works
        if ($matcher->matchesNode($node, $graphNode)) {
            $nodeType = $node->metadata['node_type'] ?? '';

            // Link nodes that are directly relevant to this pattern
            // For LITERAL: link word/lemma nodes
            // For SLOT: link POS node
            // For WILDCARD: link word/POS nodes
            $shouldLink = match ($graphNode['type'] ?? '') {
                'LITERAL' => in_array($nodeType, ['word', 'lemma']),
                'SLOT' => $nodeType === 'pos',
                'WILDCARD' => in_array($nodeType, ['word', 'pos']),
                'CONSTRUCTION_REF' => $nodeType === 'construction',
                default => false,
            };

            if ($shouldLink) {
                // Establish bidirectional connection
                $node->addOutput($construction);
                $construction->addInput($node);
            }
        }
//        }
    }

    // ========================================================================
    // GRAPH TRAVERSAL METHODS (Graph-Based Pattern Matching)
    // ========================================================================

    /**
     * Find all possible starting nodes in pattern graph
     *
     * For simple patterns: returns single node after START
     * For alternatives at start: returns all alternative branches
     *
     * @param array $graph Pattern graph with nodes and edges
     * @return array Array of ['node_id' => string, 'node' => array]
     */
    private function findStartingNodes(array $graph): array
    {
        $nodes = $graph['nodes'] ?? [];
        $edges = $graph['edges'] ?? [];

        // Find START node
        $startNodeId = null;
        foreach ($nodes as $nodeId => $node) {
            if (($node['type'] ?? '') === 'START') {
                $startNodeId = $nodeId;
                break;
            }
        }

        if ($startNodeId === null) {
            return [];
        }

        // Find all nodes reachable from START (excluding END)
        $startingNodes = [];
        foreach ($edges as $edge) {
            if ($edge['from'] === $startNodeId) {
                $targetId = $edge['to'];
                $targetNode = $nodes[$targetId] ?? null;

                if ($targetNode && ($targetNode['type'] ?? '') !== 'END') {
                    // Check if we already added this node (avoid duplicates in alternatives)
                    $alreadyAdded = false;
                    foreach ($startingNodes as $existing) {
                        if ($existing['node_id'] === $targetId) {
                            $alreadyAdded = true;
                            break;
                        }
                    }

                    if (!$alreadyAdded) {
                        $startingNodes[] = [
                            'node_id' => $targetId,
                            'node' => $targetNode,
                        ];
                    }
                }
            }
        }

        return $startingNodes;
    }

    /**
     * Initialize traversal state for new partial construction
     *
     * @param array $graph Pattern graph
     * @param string $startingNodeId The node that matched initially
     * @return array Traversal state structure
     */
    private function initializeTraversalState(array $graph, string $startingNodeId): array
    {
        return [
            'current_node_id' => $startingNodeId,
            'path_taken' => [$startingNodeId],
            'alternative_choices' => [],
            'repetition_state' => [],
            'bypassed_nodes' => [],
        ];
    }

    /**
     * Find possible next nodes from current position in graph
     *
     * Handles:
     * - Sequential: next node in chain
     * - Alternatives: all branches at fork
     * - Repetition: loop back OR exit
     * - Optional: through element OR bypass
     *
     * @param array $graph Pattern graph
     * @param string $currentNodeId Current node ID
     * @param array $traversalState Current traversal state
     * @return array Array of possible next nodes with metadata
     */
    private function findNextPossibleNodes(array $graph, string $currentNodeId, array $traversalState): array
    {
        $possibilities = [];
        $edges = $graph['edges'] ?? [];
        $nodes = $graph['nodes'] ?? [];

        // Find all outgoing edges from current node
        foreach ($edges as $edge) {
            if ($edge['from'] !== $currentNodeId) {
                continue;
            }

            $targetNodeId = $edge['to'];
            $targetNode = $nodes[$targetNodeId] ?? null;

            if (!$targetNode) {
                continue;
            }

            // Skip END meta-node (handled by isPatternComplete)
            if (($targetNode['type'] ?? '') === 'END') {
                continue;
            }

            // Determine path type
            $pathType = 'sequential';

            if ($edge['bypass'] ?? false) {
                $pathType = 'bypass';
            }

            // Check if this is a loop-back (repetition)
            if ($targetNodeId === $currentNodeId) {
                $pathType = 'repeat';
            }

            // Special handling for REP_CHECK nodes
            if (($targetNode['type'] ?? '') === 'REP_CHECK') {
                // REP_CHECK creates a decision point:
                // We need to find edges FROM the REP_CHECK node
                // One path loops back, one exits
                foreach ($edges as $repEdge) {
                    if ($repEdge['from'] === $targetNodeId) {
                        $repTargetId = $repEdge['to'];
                        $repTargetNode = $nodes[$repTargetId] ?? null;

                        if ($repTargetNode && ($repTargetNode['type'] ?? '') !== 'END') {
                            $repPathType = ($repTargetId === $currentNodeId) ? 'repeat' : 'exit_repeat';

                            $possibilities[] = [
                                'node_id' => $repTargetId,
                                'node' => $repTargetNode,
                                'path_type' => $repPathType,
                                'edge' => $repEdge,
                            ];
                        }
                    }
                }

                continue; // Don't add REP_CHECK itself as a possibility
            }

            $possibilities[] = [
                'node_id' => $targetNodeId,
                'node' => $targetNode,
                'path_type' => $pathType,
                'edge' => $edge,
            ];
        }

        return $possibilities;
    }

    /**
     * Select best match from possible next nodes
     *
     * Priority rules:
     * 1. Required over optional (non-bypass over bypass)
     * 2. Continue repetition over exit (greedy)
     * 3. More specific match: LITERAL > SLOT > WILDCARD
     *
     * @param array $l23Nodes L23 nodes from current column
     * @param array $possibleNextNodes From findNextPossibleNodes()
     * @param PatternMatcher $matcher Pattern matcher instance
     * @return array|null Best match or null if no match
     */
    private function selectBestMatch(array $l23Nodes, array $possibleNextNodes, PatternMatcher $matcher): ?array
    {
        $matches = [];

        // Find all possibilities that match
        foreach ($possibleNextNodes as $possibility) {
            if ($matcher->matchesNode($l23Nodes, $possibility['node'])) {
                $matches[] = $possibility;
            }
        }

        if (empty($matches)) {
            // No match: check for bypass paths (optionals we can skip)
            foreach ($possibleNextNodes as $possibility) {
                if ($possibility['path_type'] === 'bypass') {
                    // Take bypass even though nothing matched
                    return $possibility;
                }
            }

            return null; // No match and no bypass available
        }

        // Priority 1: Prefer required over optional (non-bypass over bypass)
        $nonBypass = array_filter($matches, fn($m) => $m['path_type'] !== 'bypass');
        if (!empty($nonBypass)) {
            $matches = $nonBypass;
        }

        // Priority 2: Prefer continuing repetition over exiting (greedy)
        $repeats = array_filter($matches, fn($m) => $m['path_type'] === 'repeat');
        if (!empty($repeats)) {
            return $repeats[0]; // Continue repetition
        }

        // Priority 3: Prefer more specific matches (LITERAL > SLOT > WILDCARD)
        usort($matches, function ($a, $b) {
            $typeRank = ['LITERAL' => 3, 'SLOT' => 2, 'WILDCARD' => 1];
            $rankA = $typeRank[$a['node']['type'] ?? ''] ?? 0;
            $rankB = $typeRank[$b['node']['type'] ?? ''] ?? 0;

            return $rankB <=> $rankA; // Descending
        });

        // Return highest priority match
        return $matches[0];
    }

    /**
     * Advance traversal state after successful match
     *
     * Updates current_node_id, path_taken, and relevant tracking arrays
     *
     * @param array $traversalState Current state
     * @param string $matchedNodeId Node that was matched
     * @param string $pathType Type of path taken
     * @return array Updated traversal state
     */
    private function advanceTraversalState(array $traversalState, string $matchedNodeId, string $pathType): array
    {
        $newState = $traversalState;

        // Update current position
        $newState['current_node_id'] = $matchedNodeId;

        // Record path taken
        $newState['path_taken'][] = $matchedNodeId;

        // Track special path types
        switch ($pathType) {
            case 'alternative':
                // Record which alternative was chosen
                // (In a fork, record that we chose this branch)
                $previousNode = $traversalState['current_node_id'] ?? null;
                if ($previousNode) {
                    $newState['alternative_choices'][$previousNode] = $matchedNodeId;
                }
                break;

            case 'repeat':
                // Increment repetition count
                $count = $newState['repetition_state'][$matchedNodeId]['count'] ?? 0;
                $newState['repetition_state'][$matchedNodeId] = ['count' => $count + 1];
                break;

            case 'bypass':
                // Record that we bypassed this node
                $newState['bypassed_nodes'][] = $matchedNodeId;
                break;
        }

        return $newState;
    }

    /**
     * Check if pattern is complete (reached END node)
     *
     * @param array $graph Pattern graph
     * @param array $traversalState Current traversal state
     * @return bool True if at END node
     */
    private function isPatternComplete(array $graph, array $traversalState): bool
    {
        $currentNodeId = $traversalState['current_node_id'] ?? null;
        $nodes = $graph['nodes'] ?? [];
        $edges = $graph['edges'] ?? [];

        if ($currentNodeId === null) {
            return false;
        }

        // Check if there's a direct edge to END from current node
        foreach ($edges as $edge) {
            if ($edge['from'] === $currentNodeId) {
                $targetNode = $nodes[$edge['to']] ?? null;
                if ($targetNode && ($targetNode['type'] ?? '') === 'END') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Find END node ID in graph
     *
     * @param array $graph Pattern graph
     * @return string|null END node ID or null
     */
    private function findEndNode(array $graph): ?string
    {
        $nodes = $graph['nodes'] ?? [];

        foreach ($nodes as $nodeId => $node) {
            if (($node['type'] ?? '') === 'END') {
                return $nodeId;
            }
        }

        return null;
    }

    /**
     * Check if END node is reachable from current position
     *
     * Uses BFS to check path exists to END
     *
     * @param array $graph Pattern graph
     * @param string $fromNodeId Starting node ID
     * @return bool True if END is reachable
     */
    private function canReachEnd(array $graph, string $fromNodeId): bool
    {
        $endNodeId = $this->findEndNode($graph);
        if ($endNodeId === null) {
            return false;
        }

        $edges = $graph['edges'] ?? [];
        $queue = [$fromNodeId];
        $visited = [];

        while (!empty($queue)) {
            $current = array_shift($queue);

            if ($current === $endNodeId) {
                return true;
            }

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            // Add neighbors to queue
            foreach ($edges as $edge) {
                if ($edge['from'] === $current) {
                    $queue[] = $edge['to'];
                }
            }
        }

        return false;
    }

    /**
     * Find existing construction by name (for node reuse)
     *
     * @param string $constructionName Name of construction to find
     * @return Node|null Existing construction node or null
     */
    private function findConstructionByName(string $constructionName): ?Node
    {
        // Check both completed constructions and partial constructions
        foreach ($this->nodes as $node) {
            if (($node->metadata['node_type'] ?? null) === 'construction') {
                $nodeName = $node->metadata['name'] ?? '';
                if (mb_strtoupper($nodeName, 'UTF-8') === mb_strtoupper($constructionName, 'UTF-8')) {
                    return $node;
                }
            }
        }

        return null;
    }

    /**
     * Update construction growth (node reuse)
     *
     * When a new L23 construction matches an existing construction pattern,
     * update the existing construction to reflect argument growth instead of
     * creating a duplicate.
     *
     * Guard rails prevent infinite loops:
     * - Track growth stages to detect circular growth
     * - Limit maximum growth stages (5)
     * - Detect same-stage repetition
     *
     * @param Node $construction Existing construction to update
     * @param string $newStage New growth stage (e.g., "ARG_REL")
     * @param Node $l23Node L23 construction node that triggered growth
     */
    private function updateConstructionGrowth(Node $construction, string $newStage, Node $l23Node): void
    {
        // Guard rail: Check if already at this growth stage (prevent loop)
        $currentStage = $construction->metadata['growth_stage'] ?? null;
        if ($currentStage === $newStage) {
            return; // Already at this stage, skip
        }

        // Guard rail: Prevent excessive growth (max 5 stages)
        $growthHistory = $construction->metadata['growth_history'] ?? [];
        if (count($growthHistory) >= 5) {
            return; // Too many updates, something is wrong
        }

        // Guard rail: Detect circular growth (same stage repeating)
        if (in_array($newStage, $growthHistory)) {
            // Circular growth detected (e.g., MOD → ARG_REL → MOD)
            $construction->metadata['malformed'] = true;

            return;
        }

        // Update growth metadata
        $construction->metadata['growth_stage'] = $newStage;
        $construction->metadata['growth_history'][] = $newStage;
        $construction->metadata['last_updated_at'] = microtime(true);
        $construction->metadata['update_count'] = count($growthHistory) + 1;

        // Link L23 node to construction (for visualization)
        $l23Node->addOutput($construction);
        $construction->addInput($l23Node);

        // Re-propagate: Check if this updated construction now completes higher patterns
        // or creates new predictions
        // Note: Don't create L23 feedback again - the L23 node already exists
        $construction->metadata['needs_repropagation'] = true;

        // Update partial construction metadata if applicable
        if ($construction->metadata['is_partial'] ?? false) {
            // Update matched array to reflect new element
            $matched = $construction->metadata['matched'] ?? [];
            $matched[] = true;
            $construction->metadata['matched'] = $matched;
        }
    }

    /**
     * Create new construction from L23 node
     *
     * @param array $construction Construction definition
     * @param array $graph Construction graph
     * @param array $startNode Starting node in graph
     * @param Node $l23Node L23 construction node
     * @param PatternMatcher $matcher Pattern matcher instance
     */
    private function createConstructionFromL23Node(
        array          $construction,
        array          $graph,
        array          $startNode,
        Node           $l23Node,
        PatternMatcher $matcher
    ): void
    {
        // Initialize traversal state
        $traversalState = $this->initializeTraversalState($graph, $startNode['node_id']);

        // Extract pattern sequence
        $patternSequence = $this->extractPatternSequence($graph);

        // Initialize matched array
        $matched = array_fill(0, count($patternSequence), false);
        if (count($matched) > 0) {
            $matched[0] = true; // First element matched
        }

        // Create partial construction
        $partialConstruction = $this->createPartialConstruction(
            constructionId: $construction['id'],
            metadata: [
                'construction_id' => $construction['id'],
                'name' => $construction['name'] ?? 'UNKNOWN',
                'pattern' => $patternSequence,
                'graph' => $graph,
                'graph_nodes' => $graph['nodes'] ?? [],
                'traversal_state' => $traversalState,
                'matched' => $matched,
                'anchor_position' => $this->columnPosition,
                'span_length' => 1,
                'growth_stage' => $l23Node->metadata['name'] ?? 'UNKNOWN',
                'growth_history' => [$l23Node->metadata['name'] ?? 'UNKNOWN'],
                'update_count' => 1,
            ]
        );

        // Link L23 node to this construction
        $this->linkL23ToConstruction([$l23Node], $partialConstruction, $startNode['node'], $matcher);

        // Check if already complete (single-element pattern)
        if ($this->isPatternComplete($graph, $traversalState)) {
            $this->confirmConstruction($partialConstruction->id);
        }
    }

    /**
     * Create confirmation link when prediction matches
     *
     * @param Node $realNode The real construction node at current column
     * @param \App\Data\CLN\PredictionEntry $entry The matched prediction entry
     */
    public function confirmPredictionWithLink(Node $sourceNode, Node $predictedNode): void
    {
//        $manager = $this->getSequenceManager();
//        if ($manager === null) {
//            return;
//        }
//
//        // Get L23 layer at source column
//        $sourceColumn = $manager->getColumn($entry->sourceColumn);
//        if ($sourceColumn === null) {
//            return;
//        }
//
//        $sourceL23 = $sourceColumn->getL23();
//
//        // Create predicted node at source column
//        $predictedNode = $this->factory->createPredictedNode(
//            type: $entry->type,
//            value: $entry->value,
//            columnPosition: $entry->sourceColumn,
//            metadata: [
//                'source_partial_id' => $entry->sourcePartialId,
//                'source_construction_id' => $entry->constructionId,
//                'prediction_strength' => $entry->strength,
//                'prediction_confirmed' => true, // Immediately confirmed
//                'confirmed_at_column' => $this->columnPosition, // Where match occurred
//            ]
//        );

        // Confirm prediction (sets threshold to 0)
//        $predictedNode->confirmPrediction();

        // Add to source L23 layer
//        $sourceNode->addNode($predictedNode);

        // Create link
        $predictedNode->addInput($sourceNode);
        $sourceNode->addOutput($predictedNode);

        // Mark real node as having confirmed a prediction
        $predictedNode->metadata['prediction_confirmed'] = true;
        $predictedNode->metadata['prediction_source_column'] = 0;//$entry->sourceColumn;

        // Update source partial construction
        $this->updatePartialConstruction($predictedNode, $sourceNode);
    }


    /**
     * Reset layer state (clear activations, boosts)
     */
    public function reset(): void
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof Node) {
                $node->reset();
            } elseif ($node instanceof Node) {
                $node->reset();
            }
        }

        $this->partialConstructionBoosts = [];
    }
}
