<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\RuntimeGraph;
use Illuminate\Support\Facades\DB;

/**
 * Activation Dynamics for Parsing (RNT Pattern Graph)
 *
 * Implements simple forward-pass activation propagation through the RNT pattern graph:
 * - Word/POS nodes activate and propagate forward
 * - OR nodes: Activate just once when any input arrives
 * - AND nodes: Only propagate when BOTH left and right operands are active
 * - SEQUENCER nodes: Only propagate when the head component is activated
 *   - Once head-activated, a SEQUENCER can't receive new head activations (create new instance)
 *   - But can still receive activation from right link
 *
 * Uses lazy database queries with caching for pattern graph access.
 */
class ActivationDynamicsParser_01
{
    /**
     * Activation threshold for considering a node "active"
     */
    private const ACTIVATION_THRESHOLD = 0.5;

    /**
     * Initial activation level for input word/POS nodes
     */
    private const INITIAL_ACTIVATION = 0.95;

    /**
     * Activation propagation decay factor
     */
    private const PROPAGATION_FACTOR = 0.9;

    /**
     * Maximum number of propagation iterations
     */
    private const MAX_ITERATIONS = 10;

    /**
     * Track which OR nodes have been activated (activate once rule)
     *
     * @var array<int, bool> or_node_id => true
     */
    private array $activatedOrNodes = [];

    /**
     * Track which SEQUENCER instances (L2 nodes) have been head-activated
     * Key: L2 node ID in runtime graph
     * Value: true
     *
     * @var array<string, bool>
     */
    private array $headActivatedSequencers = [];

    /**
     * Cache for pattern graph edges (lazy loaded)
     * Key: from_node_id
     * Value: array of edge objects
     *
     * @var array<int, array>
     */
    private array $edgeCache = [];

    /**
     * Cache for pattern graph nodes (lazy loaded)
     * Key: node_id
     * Value: node data array
     *
     * @var array<int, array>
     */
    private array $nodeCache = [];

    /**
     * Cache for DATA node lookups
     * Key: "literal:{word}" or "slot:{pos}"
     * Value: array of matching DATA node IDs
     *
     * @var array<string, array>
     */
    private array $dataNodeCache = [];

    /**
     * Reset activation state for a new parse
     */
    public function reset(): void
    {
        $this->activatedOrNodes = [];
        $this->headActivatedSequencers = [];
    }

    /**
     * Propagate activation through the runtime graph using the pattern graph
     *
     * This is called after L1 nodes are created.
     * It propagates activation through OR, AND, and SEQUENCER nodes according to their rules.
     *
     * @param  RuntimeGraph  $graph  Runtime graph with active nodes
     * @param  int  $maxIterations  Maximum propagation iterations
     * @return array Statistics about propagation
     */
    public function propagateActivation(RuntimeGraph $graph, int $maxIterations = self::MAX_ITERATIONS): array
    {
        $stats = [
            'iterations' => 0,
            'nodes_activated' => 0,
            'or_nodes_activated' => 0,
            'and_nodes_activated' => 0,
            'sequencer_nodes_activated' => 0,
        ];

        for ($i = 0; $i < $maxIterations; $i++) {
            $stats['iterations']++;
            $activationChanged = false;

            // Get all active L1 nodes in runtime graph
            //$activeL1Nodes = $this->getActiveNodes($graph, 'L1', 'L5');
            $activeL1Nodes = $graph->getLiteralNodes();

            // For each active L1 node, find matching DATA nodes in pattern graph
            foreach ($activeL1Nodes as $l1Node) {
                $matchingDataNodes = $this->findMatchingDataNodes($l1Node);

                // Propagate from DATA nodes to OR nodes
                foreach ($matchingDataNodes as $dataNodeId) {
                    $changed = $this->propagateFromDataNode($dataNodeId, $graph, $stats);
                    $activationChanged = $activationChanged || $changed;
                }
            }

            // Propagate from active OR nodes
            $activeOrNodeIds = array_keys($this->activatedOrNodes);
            foreach ($activeOrNodeIds as $orNodeId) {
                $changed = $this->propagateFromOrNode($orNodeId, $graph, $stats);
                $activationChanged = $activationChanged || $changed;
            }

            // Propagate from active AND nodes
            $activeAndNodes = $this->getAllAndNodeIds();
            foreach ($activeAndNodes as $andNodeId) {
                $changed = $this->propagateFromAndNode($andNodeId, $graph, $stats);
                $activationChanged = $activationChanged || $changed;
            }

            // Propagate from active SEQUENCER nodes (for left/right links)
            $activeSequencerNodes = $this->getActiveSequencerNodes($graph);
            foreach ($activeSequencerNodes as $l2NodeId => $sequencerNodeId) {
                $changed = $this->propagateFromSequencerNode($sequencerNodeId, $l2NodeId, $graph, $stats);
                $activationChanged = $activationChanged || $changed;
            }

            // If nothing changed, we've converged
            if (! $activationChanged) {
                break;
            }
        }

        return $stats;
    }

    /**
     * Get active nodes from runtime graph at a specific cortical level
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  string  $level  Cortical level (L1, L2)
     * @return array Array of active nodes
     */
    private function getActiveNodes(RuntimeGraph $graph, string $level, string $layer): array
    {
        $nodes = $graph->getNodesByLevel($level, $layer);

        return array_filter($nodes, fn ($node) => $node->activation >= self::ACTIVATION_THRESHOLD);
    }

    /**
     * Find DATA nodes in pattern graph that match an L1 node's features
     *
     * @param  \App\Models\CLN_RNT\Column  $l1Node  L1 runtime node
     * @return array Array of matching DATA node IDs
     */
    private function findMatchingDataNodes($l1Node): array
    {
        $matches = [];
        $word = $l1Node->features['value'] ?? null;
        $pos = $l1Node->features['pos'] ?? null;

        // Match LITERAL nodes by word
        if ($word) {
            $literalMatches = $this->findDataNodesByLiteral($word);
            $matches = array_merge($matches, $literalMatches);
        }

        // Match SLOT nodes by POS
        if ($pos) {
            $slotMatches = $this->findDataNodesByPos($pos);
            $matches = array_merge($matches, $slotMatches);
        }

        return array_unique($matches);
    }

    /**
     * Find DATA nodes matching a literal word value
     *
     * @param  string  $word  Word to match
     * @return array Array of DATA node IDs
     */
    private function findDataNodesByLiteral(string $word): array
    {
        $cacheKey = "literal:{$word}";

        if (isset($this->dataNodeCache[$cacheKey])) {
            return $this->dataNodeCache[$cacheKey];
        }

        $nodes = DB::table('parser_pattern_node')
            ->where('type', 'DATA')
            ->where('value', $word)
            ->whereRaw("JSON_EXTRACT(specification, '$.dataType') = 'literal'")
            ->pluck('id')
            ->toArray();

        $this->dataNodeCache[$cacheKey] = $nodes;

        return $nodes;
    }

    /**
     * Find DATA nodes matching a POS tag
     *
     * @param  string  $pos  POS tag to match
     * @return array Array of DATA node IDs
     */
    private function findDataNodesByPos(string $pos): array
    {
        $cacheKey = "slot:{$pos}";

        if (isset($this->dataNodeCache[$cacheKey])) {
            return $this->dataNodeCache[$cacheKey];
        }

        $nodes = DB::table('parser_pattern_node')
            ->where('type', 'DATA')
            ->where('pos', $pos)
            ->whereRaw("JSON_EXTRACT(specification, '$.dataType') = 'slot'")
            ->pluck('id')
            ->toArray();

        $this->dataNodeCache[$cacheKey] = $nodes;

        return $nodes;
    }

    /**
     * Get pattern graph node by ID (with caching)
     *
     * @param  int  $nodeId  Node ID
     * @return array|null Node data or null
     */
    private function getPatternNode(int $nodeId): ?array
    {
        if (isset($this->nodeCache[$nodeId])) {
            return $this->nodeCache[$nodeId];
        }

        $node = DB::table('parser_pattern_node')
            ->where('id', $nodeId)
            ->select('id', 'type', 'specification', 'construction_name')
            ->first();

        if (! $node) {
            return null;
        }

        $this->nodeCache[$nodeId] = [
            'id' => $node->id,
            'type' => $node->type,
            'specification' => json_decode($node->specification, true),
            'construction_name' => $node->construction_name,
        ];

        return $this->nodeCache[$nodeId];
    }

    /**
     * Get outgoing edges from a node (with caching)
     *
     * @param  int  $fromNodeId  Source node ID
     * @return array Array of edge objects
     */
    private function getOutgoingEdges(int $fromNodeId): array
    {
        if (isset($this->edgeCache[$fromNodeId])) {
            return $this->edgeCache[$fromNodeId];
        }

        $edges = DB::table('parser_pattern_edge')
            ->where('from_node_id', $fromNodeId)
            ->select('from_node_id', 'to_node_id', 'properties', 'sequence')
            ->get()
            ->all();

        $this->edgeCache[$fromNodeId] = $edges;

        return $edges;
    }

    /**
     * Get incoming edges to a node
     *
     * @param  int  $toNodeId  Target node ID
     * @return array Array of edge objects
     */
    private function getIncomingEdges(int $toNodeId): array
    {
        // We don't cache incoming edges as they're less frequently used
        return DB::table('parser_pattern_edge')
            ->where('to_node_id', $toNodeId)
            ->select('from_node_id', 'to_node_id', 'properties', 'sequence')
            ->get()
            ->all();
    }

    /**
     * Propagate activation from a DATA node to connected OR nodes
     *
     * @param  int  $dataNodeId  Pattern graph DATA node ID
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  array  $stats  Statistics array (updated by reference)
     * @return bool True if any activation changed
     */
    private function propagateFromDataNode(int $dataNodeId, RuntimeGraph $graph, array &$stats): bool
    {
        $changed = false;
        $edges = $this->getOutgoingEdges($dataNodeId);

        foreach ($edges as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (! $targetNode || $targetNode['type'] !== 'OR') {
                continue;
            }

            //
            $l2OrNode = $graph->addL1Node(
                position: $data['position'],
                constructionType: 'literal',
                features: $data['features']
            );


//            // OR nodes activate just once
//            if (isset($this->activatedOrNodes[$targetNodeId])) {
//                continue;
//            }
//
//            // Activate this OR node
//            $this->activatedOrNodes[$targetNodeId] = true;

            $stats['or_nodes_activated']++;
            $stats['nodes_activated']++;
            $changed = true;
        }

        return $changed;
    }

    /**
     * Propagate activation from an OR node to connected nodes (AND, other OR, SEQUENCER)
     *
     * @param  int  $orNodeId  Pattern graph OR node ID
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  array  $stats  Statistics array (updated by reference)
     * @return bool True if any activation changed
     */
    private function propagateFromOrNode(int $orNodeId, RuntimeGraph $graph, array &$stats): bool
    {
        $changed = false;
        $edges = $this->getOutgoingEdges($orNodeId);

        foreach ($edges as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (! $targetNode) {
                continue;
            }

            $targetType = $targetNode['type'];

            if ($targetType === 'OR') {
                // OR → OR propagation (for construction references)
                if (! isset($this->activatedOrNodes[$targetNodeId])) {
                    $this->activatedOrNodes[$targetNodeId] = true;
                    $stats['or_nodes_activated']++;
                    $stats['nodes_activated']++;
                    $changed = true;
                }
            } elseif ($targetType === 'AND') {
                // OR → AND: Mark as changed to trigger AND node check
                $changed = true;
            } elseif ($targetType === 'SEQUENCER') {
                // OR → SEQUENCER: Check if this is from the head OR
                $properties = json_decode($edge->properties, true);
                $position = $properties['position'] ?? null;

                // Only propagate if this edge is from the head position
                if ($position === 'head') {
                    $constructionName = $targetNode['construction_name'] ?? null;
                    if ($constructionName) {
                        // Always create a NEW L2 node for each head activation
                        // (SEQUENCER can't be reused after head activation)
                        $l2Node = $this->createNewL2NodeForConstruction($graph, $constructionName, $targetNodeId);
                        $l2Node->activation = self::INITIAL_ACTIVATION * self::PROPAGATION_FACTOR;

                        // Mark this L2 node as head-activated
                        $this->headActivatedSequencers[$l2Node->id] = true;

                        $stats['sequencer_nodes_activated']++;
                        $stats['nodes_activated']++;
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Propagate activation from an AND node
     *
     * AND nodes only propagate when BOTH left and right operands are active.
     *
     * @param  int  $andNodeId  Pattern graph AND node ID
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  array  $stats  Statistics array (updated by reference)
     * @return bool True if any activation changed
     */
    private function propagateFromAndNode(int $andNodeId, RuntimeGraph $graph, array &$stats): bool
    {
        // Check if both operands are active
        $leftActive = false;
        $rightActive = false;

        $incomingEdges = $this->getIncomingEdges($andNodeId);

        foreach ($incomingEdges as $edge) {
            $sourceId = $edge->from_node_id;
            $properties = json_decode($edge->properties, true);
            $label = $properties['label'] ?? null;

            $sourceNode = $this->getPatternNode($sourceId);
            if (! $sourceNode) {
                continue;
            }

            // Check if source is active
            $sourceActive = false;
            if ($sourceNode['type'] === 'OR' && isset($this->activatedOrNodes[$sourceId])) {
                $sourceActive = true;
            } elseif ($sourceNode['type'] === 'SEQUENCER') {
                // Check runtime graph for SEQUENCER activation
                $constructionName = $sourceNode['construction_name'] ?? null;
                if ($constructionName) {
                    $l2Nodes = $this->findL2NodesForConstruction($graph, $constructionName);
                    foreach ($l2Nodes as $l2Node) {
                        if ($l2Node->activation >= self::ACTIVATION_THRESHOLD) {
                            $sourceActive = true;
                            break;
                        }
                    }
                }
            }

            if ($label === 'left' && $sourceActive) {
                $leftActive = true;
            } elseif ($label === 'right' && $sourceActive) {
                $rightActive = true;
            }
        }

        // Only propagate if both operands are active
        if (! $leftActive || ! $rightActive) {
            return false;
        }

        // Both operands active - propagate to target nodes
        $changed = false;
        $edges = $this->getOutgoingEdges($andNodeId);

        foreach ($edges as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (! $targetNode) {
                continue;
            }

            if ($targetNode['type'] === 'OR') {
                // AND → OR propagation
                if (! isset($this->activatedOrNodes[$targetNodeId])) {
                    $this->activatedOrNodes[$targetNodeId] = true;
                    $stats['or_nodes_activated']++;
                    $stats['nodes_activated']++;
                    $stats['and_nodes_activated']++;
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * Propagate activation from a SEQUENCER node
     *
     * SEQUENCER nodes propagate to left and right OR nodes after being activated by head.
     * Note: Once head-activated, a SEQUENCER can still receive right link activations.
     *
     * @param  int  $sequencerNodeId  Pattern graph SEQUENCER node ID
     * @param  string  $l2NodeId  Runtime graph L2 node ID for this SEQUENCER instance
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  array  $stats  Statistics array (updated by reference)
     * @return bool True if any activation changed
     */
    private function propagateFromSequencerNode(int $sequencerNodeId, string $l2NodeId, RuntimeGraph $graph, array &$stats): bool
    {
        $changed = false;

        // Only propagate if this SEQUENCER has been head-activated
        if (! isset($this->headActivatedSequencers[$l2NodeId])) {
            return false;
        }

        // Find outgoing edges from SEQUENCER
        $outgoingEdges = $this->getOutgoingEdges($sequencerNodeId);

        foreach ($outgoingEdges as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (! $targetNode || $targetNode['type'] !== 'OR') {
                continue;
            }

            // OR nodes activate just once
            if (isset($this->activatedOrNodes[$targetNodeId])) {
                continue;
            }

            // Activate this OR node
            $this->activatedOrNodes[$targetNodeId] = true;
            $stats['or_nodes_activated']++;
            $stats['nodes_activated']++;
            $changed = true;

//            $sourceId = $edge->from_node_id;
//            $properties = json_decode($edge->properties, true);
//            $position = $properties['position'] ?? null;
//
//            // Activate the source OR nodes for left/right positions
//            // (head is already active since it gated the SEQUENCER)
//            if (in_array($position, ['left', 'right'])) {
//                $sourceNode = $this->getPatternNode($sourceId);
//                if ($sourceNode && $sourceNode['type'] === 'OR') {
//                    if (! isset($this->activatedOrNodes[$sourceId])) {
//                        $this->activatedOrNodes[$sourceId] = true;
//                        $stats['or_nodes_activated']++;
//                        $stats['nodes_activated']++;
//                        $changed = true;
//                    }
//                }
//            }
        }

        return $changed;
    }

    /**
     * Get all AND node IDs from pattern graph
     *
     * @return array Array of AND node IDs
     */
    private function getAllAndNodeIds(): array
    {
        // Query for AND nodes (not cached as it's a small set)
        return DB::table('parser_pattern_node')
            ->where('type', 'AND')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get active SEQUENCER nodes from runtime graph
     *
     * Returns mapping of L2 node ID => SEQUENCER pattern node ID
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array mapping L2 node ID => SEQUENCER pattern node ID
     */
    private function getActiveSequencerNodes(RuntimeGraph $graph): array
    {
        $activeSequencers = [];

        $l2Nodes = $graph->getNodesByLevel('L2');
        foreach ($l2Nodes as $l2Node) {
            if ($l2Node->activation < self::ACTIVATION_THRESHOLD) {
                continue;
            }

            // Get SEQUENCER pattern node ID from bindings
            $patternNodeId = $l2Node->bindings['pattern_node_id'] ?? null;
            if (! $patternNodeId) {
                continue;
            }

            // Verify it's a SEQUENCER node
            $patternNode = $this->getPatternNode($patternNodeId);
            if ($patternNode && $patternNode['type'] === 'SEQUENCER') {
                $activeSequencers[$l2Node->id] = $patternNodeId;
            }
        }

        return $activeSequencers;
    }

    /**
     * Create new L2 node in runtime graph for a construction
     *
     * Always creates a new node (never reuses existing ones).
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  string  $constructionName  Construction name
     * @param  int  $patternNodeId  Pattern graph node ID
     * @return \App\Models\CLN_RNT\Column L2 node
     */
    private function createNewL2NodeForConstruction(RuntimeGraph $graph, string $constructionName, int $patternNodeId): \App\Models\CLN_RNT\Column
    {
        // Create new L2 node
        // For now, use placeholder span - this should be determined by composition
        $l2Node = $graph->addL2Node(
            startPos: 0,
            endPos: 0,
            constructionType: $constructionName,
            bindings: ['pattern_node_id' => $patternNodeId],
            features: []
        );

        return $l2Node;
    }

    /**
     * Find all L2 nodes in runtime graph for a construction
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  string  $constructionName  Construction name
     * @return array Array of L2 nodes
     */
    private function findL2NodesForConstruction(RuntimeGraph $graph, string $constructionName): array
    {
        $l2Nodes = $graph->getNodesByLevel('L2');
        $matches = [];

        foreach ($l2Nodes as $node) {
            if ($node->construction_type === $constructionName) {
                $matches[] = $node;
            }
        }

        return $matches;
    }
}
