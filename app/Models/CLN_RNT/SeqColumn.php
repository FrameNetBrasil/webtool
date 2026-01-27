<?php

namespace App\Models\CLN_RNT;

/**
 * SeqColumn - Sequential Pattern Column
 *
 * A specialized construction node that represents a cortical column with:
 * - L5 layer: One SEQUENCER node ("S") for pattern integration
 * - L23 layer: Multiple OR nodes for left/head/right pattern positions
 *   - Multiple left nodes (one per source)
 *   - One head node
 *   - Multiple right nodes (one per source)
 *
 * The L23 nodes are internally linked to the L5 SEQUENCER node.
 * External connections can be made to the L23 nodes to feed patterns into the column.
 */
class SeqColumn extends Column
{
    /**
     * Internal L23 OR nodes
     */
    public array $l_nodes = [];  // Left position nodes (sourceId => node)

    public Column $h_node;  // Head position node (single)

    public array $r_nodes = [];  // Right position nodes (sourceId => node)

    /**
     * Internal L5 SEQUENCER node
     */
    public Column $s_node;  // SEQUENCER integration node

    /**
     * PV interneuron nodes for lateral inhibition
     * One PV node for each left/right input pathway
     */
    public array $pv_l_nodes = [];  // PV nodes for left inputs (sourceId => NeuralPopulation)

    public array $pv_r_nodes = [];  // PV nodes for right inputs (sourceId => NeuralPopulation)

    /**
     * Internal edges connecting L23 to L5
     */
    public array $internal_edges = [];

    /**
     * Custom SEQUENCER name (e.g., "S1", "S2")
     */
    private ?string $sequencerName = null;

    /**
     * Create a new SeqColumn with internal structure
     *
     * @param  string  $construction_type  Type of construction
     * @param  array  $span  Span in input sequence
     * @param  string  $id  Unique identifier for this column
     * @param  array  $features  Additional features
     * @param  string|null  $sequencerName  Custom name for SEQUENCER node (e.g., "S1", "S2")
     */
    public function __construct(
        string $construction_type,
        array $span,
        string $id,
        array $features = [],
        ?string $sequencerName = null
    ) {
        // Initialize parent Column
        parent::__construct(
            cortical_level: 'L2',  // SeqColumn is treated as L2 (compositional)
            construction_type: $construction_type,
            span: $span,
            id: $id,
            features: $features
        );

        // Store custom SEQUENCER name
        $this->sequencerName = $sequencerName;

        // Create internal structure
        $this->createInternalStructure();
    }

    /**
     * Create the internal cortical column structure
     *
     * Creates:
     * - One head OR node in L23 (h)
     * - One SEQUENCER node in L5 (S)
     * - Internal edge connecting head → L5
     * - Left and right nodes are created dynamically as needed
     */
    private function createInternalStructure(): void
    {
        $baseId = $this->id;

        // Create L23 head OR node (always exists)
        $this->h_node = new Column(
            cortical_level: 'L23',
            construction_type: 'OR',
            span: $this->span,
            id: "{$baseId}_L23_h",
            features: [
                'type' => 'OR',
                'position' => 'head',
                'column_id' => $baseId,
            ]
        );

        // Create L5 SEQUENCER node with custom name if provided
        $sequencerId = $this->sequencerName ?? "{$baseId}_L5_S";
        $this->s_node = new Column(
            cortical_level: 'L5',
            construction_type: 'SEQUENCER',
            span: $this->span,
            id: $sequencerId,
            features: [
                'type' => 'SEQUENCER',
                'column_id' => $baseId,
            ]
        );

        // Create initial internal edge: head → L5
        $this->internal_edges = [
            new ConnectionEdge(
                source: $this->h_node->id,
                target: $this->s_node->id,
                type: 'feedforward',
                weight: 1.0
            ),
        ];
    }

    /**
     * Get or create a left node for a specific source
     *
     * @param  string  $sourceId  Identifier for the source (e.g., "NOUN", "SeqCol_NOUN_L5_S")
     * @return Column The left node for this source
     */
    public function getOrCreateLeftNode(string $sourceId): Column
    {
        $baseId = $this->id;
        if (empty($this->l_nodes)) {
            //            if (isset($this->l_nodes[$sourceId])) {
            //                return $this->l_nodes[$sourceId];
            //            }

            // Extract a clean source name for the node ID
            //            $sourceName = $this->extractSourceName($sourceId);
            $sourceName = '';

            // Create new left node
            $leftNode = new Column(
                cortical_level: 'L23',
                construction_type: 'OR',
                span: $this->span,
                //                id: "{$baseId}_L23_l_{$sourceName}",
                id: "{$baseId}_L23_l",
                features: [
                    'type' => 'OR',
                    'position' => 'left',
                    'column_id' => $baseId,
                    'source_id' => $sourceId,
                ]
            );

            // Create internal edge: left node → SEQUENCER
            $edge = new ConnectionEdge(
                source: $leftNode->id,
                target: $this->s_node->id,
                type: 'feedforward',
                weight: 1.0
            );
            $this->internal_edges[] = $edge;

            // Create corresponding PV interneuron for this left input
            $this->pv_l_nodes[$sourceId] = new NeuralPopulation(10.0);

            // Store the node
            // $this->l_nodes[$sourceId] = $leftNode;
            $this->l_nodes["{$baseId}_L23_l"] = $leftNode;
        } else {
            $leftNode = $this->l_nodes["{$baseId}_L23_l"];
        }

        return $leftNode;
    }

    /**
     * Get or create a right node for a specific source
     *
     * @param  string  $sourceId  Identifier for the source (e.g., "NOUN", "SeqCol_NOUN_L5_S")
     * @return Column The right node for this source
     */
    public function getOrCreateRightNode(string $sourceId): Column
    {
        if (isset($this->r_nodes[$sourceId])) {
            return $this->r_nodes[$sourceId];
        }

        // Extract a clean source name for the node ID
        $sourceName = $this->extractSourceName($sourceId);
        $baseId = $this->id;

        // Create new right node
        $rightNode = new Column(
            cortical_level: 'L23',
            construction_type: 'OR',
            span: $this->span,
            id: "{$baseId}_L23_r_{$sourceName}",
            features: [
                'type' => 'OR',
                'position' => 'right',
                'column_id' => $baseId,
                'source_id' => $sourceId,
            ]
        );

        // Create internal edge: right node → SEQUENCER
        $edge = new ConnectionEdge(
            source: $rightNode->id,
            target: $this->s_node->id,
            type: 'feedforward',
            weight: 1.0
        );
        $this->internal_edges[] = $edge;

        // Create corresponding PV interneuron for this right input
        $this->pv_r_nodes[$sourceId] = new NeuralPopulation(10.0);

        // Store the node
        $this->r_nodes[$sourceId] = $rightNode;

        return $rightNode;
    }

    /**
     * Extract a clean source name from source ID
     *
     * Examples:
     * - "SeqCol_NOUN_L5_S" → "NOUN"
     * - "SeqCol_L2_NOUN_ADP_NOUN_L5_S" → "L2_NOUN_ADP_NOUN"
     * - "L1_P-1_pos_NOUN" → "NOUN"
     *
     * @param  string  $sourceId  Full source ID
     * @return string Clean source name for node ID
     */
    private function extractSourceName(string $sourceId): string
    {
        // Pattern 1: SeqCol_XXX_L5_S → XXX
        if (preg_match('/^SeqCol_(.+?)_L5_S$/', $sourceId, $matches)) {
            return $matches[1];
        }

        // Pattern 2: L1_P-1_pos_XXX → XXX
        if (preg_match('/^L1_P-?\d+_pos_(.+)$/', $sourceId, $matches)) {
            return $matches[1];
        }

        // Default: use last part after underscore
        $parts = explode('_', $sourceId);

        return end($parts);
    }

    /**
     * Get all internal nodes (L23 + L5)
     *
     * @return array Array of Column
     */
    public function getInternalNodes(): array
    {
        $nodes = [
            'h' => $this->h_node,
            'S' => $this->s_node,
        ];

        // Add all left nodes
        foreach ($this->l_nodes as $sourceId => $node) {
            $nodes["l_{$sourceId}"] = $node;
        }

        // Add all right nodes
        foreach ($this->r_nodes as $sourceId => $node) {
            $nodes["r_{$sourceId}"] = $node;
        }

        return $nodes;
    }

    /**
     * Get all internal edges (L23 → L5)
     *
     * @return array Array of ConnectionEdge
     */
    public function getInternalEdges(): array
    {
        return $this->internal_edges;
    }

    /**
     * Get the L23 OR head node
     *
     * @return Column The head node
     */
    public function getHeadNode(): Column
    {
        return $this->h_node;
    }

    /**
     * Get all left nodes
     *
     * @return array Array of left nodes (sourceId => node)
     */
    public function getLeftNodes(): array
    {
        return $this->l_nodes;
    }

    /**
     * Get all right nodes
     *
     * @return array Array of right nodes (sourceId => node)
     */
    public function getRightNodes(): array
    {
        return $this->r_nodes;
    }

    /**
     * Get the L5 SEQUENCER node
     *
     * @return Column The SEQUENCER node
     */
    public function getSequencerNode(): Column
    {
        return $this->s_node;
    }

    /**
     * Compute column activation based on L5 SEQUENCER
     *
     * The column's activation is driven by the L5 SEQUENCER node
     *
     * @return float Column activation level
     */
    public function computeColumnActivation(): float
    {
        return $this->s_node->activation;
    }

    /**
     * Update internal dynamics for one timestep
     *
     * Propagates activation from L23 nodes to L5 SEQUENCER
     *
     * @param  float  $dt  Time step
     */
    public function updateInternalDynamics(float $dt = 1.0): void
    {
        // Collect input from all L23 nodes
        $l23_input = $this->h_node->activation;

        // Sum all left node activations
        foreach ($this->l_nodes as $leftNode) {
            $l23_input += $leftNode->activation;
        }

        // Sum all right node activations
        foreach ($this->r_nodes as $rightNode) {
            $l23_input += $rightNode->activation;
        }

        // Update L5 SEQUENCER activation based on L23 input
        // L5 accumulates L23 activity (use sum, not average, to preserve activation strength)
        $target_activation = min(1.0, $l23_input);
        $tau = 5.0; // Faster time constant for learning dynamics

        // Exponential approach to target
        $this->s_node->activation += ($target_activation - $this->s_node->activation) * ($dt / $tau);

        // Update column activation to match SEQUENCER
        $this->activation = $this->s_node->activation;
    }

    /**
     * Check if column is ready to fire
     *
     * Column fires when L5 SEQUENCER reaches threshold
     *
     * @param  float  $threshold  Activation threshold (default: 0.8)
     * @return bool True if ready to fire
     */
    public function isReadyToFire(float $threshold = 0.8): bool
    {
        return $this->s_node->activation >= $threshold;
    }

    /**
     * Get all PV nodes for left inputs
     *
     * @return array Array of PV NeuralPopulations (sourceId => NeuralPopulation)
     */
    public function getPVLeftNodes(): array
    {
        return $this->pv_l_nodes;
    }

    /**
     * Get all PV nodes for right inputs
     *
     * @return array Array of PV NeuralPopulations (sourceId => NeuralPopulation)
     */
    public function getPVRightNodes(): array
    {
        return $this->pv_r_nodes;
    }

    /**
     * Get a specific PV node for a left input source
     *
     * @param  string  $sourceId  The source identifier
     * @return NeuralPopulation|null The PV node or null if not found
     */
    public function getPVLeftNode(string $sourceId): ?NeuralPopulation
    {
        return $this->pv_l_nodes[$sourceId] ?? null;
    }

    /**
     * Get a specific PV node for a right input source
     *
     * @param  string  $sourceId  The source identifier
     * @return NeuralPopulation|null The PV node or null if not found
     */
    public function getPVRightNode(string $sourceId): ?NeuralPopulation
    {
        return $this->pv_r_nodes[$sourceId] ?? null;
    }

    /**
     * Get all PV nodes (both left and right)
     *
     * @return array Array of all PV nodes with descriptive keys
     */
    public function getAllPVNodes(): array
    {
        $allPV = [];

        foreach ($this->pv_l_nodes as $sourceId => $pvNode) {
            $allPV["left_{$sourceId}"] = $pvNode;
        }

        foreach ($this->pv_r_nodes as $sourceId => $pvNode) {
            $allPV["right_{$sourceId}"] = $pvNode;
        }

        return $allPV;
    }
}
