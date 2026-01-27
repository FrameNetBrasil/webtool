<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Layer;
use App\Models\CLN_RNT\Node;

/**
 * Node Factory for CLN
 *
 * Creates Node or Node instances based on configured node types.
 * Uses metadata to store information about what the node represents
 * (word, feature, construction, etc.).
 *
 * Architecture principle: Links matter, not node types.
 * Nodes are generic (Node/Node), metadata determines their role.
 */
class NodeFactory
{
    /**
     * Create a node with appropriate type and metadata
     *
     * @param string $nodeType Type of information ('word', 'feature', 'construction', etc.)
     * @param Layer $layer Layer this node belongs to (L23 or L5)
     * @param array $metadata Minimal metadata (whatever's needed for this node)
     * @param string|null $id Optional custom ID (auto-generated if null)
     */
    public function createNode(
        string  $nodeType,
        Layer   $layer,
        array   $metadata = [],
        ?string $id = null
    ): Node
    {
        // Get node class from config
        $nodeClass = config("cln.node_types.{$nodeType}");

        if ($nodeClass === null) {
            throw new \InvalidArgumentException("Unknown node type: {$nodeType}");
        }

        // Get threshold from metadata, config, or null (auto)
        $threshold = $metadata['threshold']
            ?? config("cln.thresholds.{$nodeType}");

        // Create appropriate node instance
        if ($nodeClass === Node::class) {
            $node = new Node(
                layer: $layer,
                //threshold: $threshold,
                //ordered: $metadata['ordered'] ?? false,
                id: $id
            );
        } else {
            $node = new Node(layer: $layer, id: $id);
        }

        // Store minimal metadata (just what's needed)
        // Always include node_type to identify what this node represents
        $node->metadata = array_merge(['node_type' => $nodeType], $metadata);

        return $node;
    }

    /**
     * Create a word node (lexical form in L23)
     *
     * @param string $word Surface form
     * @param string $lemma Base form
     * @param string|null $id Optional custom ID
     */
    public function createWordNode(string $word, string $lemma, ?string $id = null): Node
    {
        return $this->createNode(
            nodeType: 'word',
            layer: Layer::L23,
            metadata: [
                'value' => $word,
                'lemma' => $lemma,
            ],
            id: $id
        );
    }

    /**
     * Create a feature node (morphological feature in L23)
     *
     * @param string $feature Feature name (e.g., 'pos', 'gender', 'number')
     * @param string $value Feature value (e.g., 'NOUN', 'Masc', 'Sing')
     * @param string|null $id Optional custom ID
     */
    public function createFeatureNode(string $feature, string $value, ?string $id = null): Node
    {
        return $this->createNode(
            nodeType: 'feature',
            layer: Layer::L23,
            metadata: [
                'feature' => $feature,
                'value' => $value,
            ],
            id: $id
        );
    }

    /**
     * Create a POS node (part-of-speech tag in L23)
     *
     * @param string $pos POS tag (e.g., 'NOUN', 'VERB')
     * @param string|null $id Optional custom ID
     */
    public function createPOSNode(string $pos, ?string $id = null): Node
    {
        return $this->createNode(
            nodeType: 'pos',
            layer: Layer::L23,
            metadata: [
                'value' => $pos,
            ],
            id: $id
        );
    }

    /**
     * Create a single-element POS construction node in L23
     *
     * This represents a construction with just one POS element.
     * Instead of creating a bare POS node, we create a construction node
     * that wraps the POS, enabling immediate compositional processing.
     *
     * @param string $pos POS tag (e.g., 'NOUN', 'VERB')
     * @param int $columnPosition Column position where this construction appears
     * @param string|null $id Optional custom ID
     * @return Node The created construction node
     */
    public function createSingleElementPOSConstruction(
        string  $pos,
        int     $columnPosition,
        ?string $id = null
    ): Node
    {
        // Use negative construction_id to indicate synthetic single-element construction
        // This distinguishes it from database-loaded constructions with positive IDs
        $constructionId = -1;

        // Create a descriptive name for this construction
        $name = "POS:{$pos}";

        // Create ID if not provided
        $nodeId = $id ?? sprintf(
            'pos_cxn_%d_%s',
            $columnPosition,
            $pos
        );

        return $this->createNode(
            nodeType: 'construction',
            layer: Layer::L23,
            metadata: [
                'construction_id' => $constructionId,
                'name' => $name,
                'column_position' => $columnPosition,
                'span_length' => 1,
                'pos_value' => $pos,  // Store the POS for pattern matching
                'is_single_element_pos' => true,  // Flag to identify this type
                'is_from_l5_feedback' => false,  // Not from L5, created directly
            ],
            id: $nodeId
        );
    }

    /**
     * Create a construction node in L5 (can be partial or full)
     *
     * @param int $constructionId Construction ID from database
     * @param string $name Construction name
     * @param array $pattern Pattern elements
     * @param array $matched Which elements are matched (bool array)
     * @param bool $isGhost Whether this is a ghost (partial) activation
     * @param int $anchorPosition Column where construction started
     * @param string|null $id Optional custom ID
     */
    public function createConstructionNode(
        int     $constructionId,
        string  $name,
        array   $pattern,
        int     $pattern_id,
        array   $matched,
        bool    $isPartial = false,
        int     $anchorPosition = 0,
        ?string $id = null,
        bool    $is_predicted = false,
        int     $prediction_strength = 0,
        int     $source_construction_id = 0,
        int     $source_partial_id = 0,
    ): Node
    {
        return $this->createNode(
            nodeType: $isPartial ? 'partial_construction' : 'construction',
            layer: Layer::L5,
            metadata: [
                'construction_id' => $constructionId,
                'name' => $name,
                'pattern' => $pattern,
                'pattern_id' => $pattern_id,
                'matched' => $matched,
                'is_partial' => $isPartial,
                'anchor_position' => $anchorPosition,
                'is_predicted' => $is_predicted,
                'prediction_strength' => $prediction_strength,
                'source_construction_id' => $source_construction_id,
                'source_partial_id' => $source_partial_id,
            ],
            id: $id
        );
    }

    /**
     * Create a construction node in L23 (feedback from L5 completion)
     *
     * When an L5 construction completes, create a corresponding node in L23
     * that represents the compositional unit for higher-level pattern matching.
     *
     * This enables recursive composition: word → MWE → phrase → clause
     *
     * @param int $constructionId Construction ID from database
     * @param string $name Construction name
     * @param int $columnPosition Column position (anchor position of L5 construction)
     * @param int $spanLength How many tokens the construction spans
     * @param array $additionalMetadata Optional metadata (pattern, graph, etc.)
     * @param string|null $id Optional custom ID
     * @return Node The created L23 construction node
     */
    public function createL23ConstructionNode(
        int     $constructionId,
        string  $name,
        int     $columnPosition,
        int     $spanLength,
        array   $additionalMetadata = [],
        ?string $id = null
    ): Node
    {
        // Create ID: "construction_{columnPos}_{constructionId}_{timestamp}"
        // Include timestamp to prevent collisions if same construction matches multiple times
        $nodeId = $id ?? sprintf(
            'construction_%d_%d_%d',
            $columnPosition,
            $constructionId,
            (int)(microtime(true) * 1000)
        );

        return $this->createNode(
            nodeType: 'construction',
            layer: Layer::L23,
            metadata: array_merge([
                'construction_id' => $constructionId,
                'name' => $name,
                'column_position' => $columnPosition,
                'span_length' => $spanLength,
                'is_from_l5_feedback' => true,
            ], $additionalMetadata),
            id: $nodeId
        );
    }

    /**
     * Create a lemma node (abstract lexical concept in L5)
     *
     * @param string $lemma Lemma form
     * @param string $semanticType Semantic type (E, R, A, F, etc.)
     * @param string|null $id Optional custom ID
     */
    public function createLemmaNode(string $lemma, string $semanticType = 'E', ?string $id = null): Node
    {
        return $this->createNode(
            nodeType: 'lemma',
            layer: Layer::L5,
            metadata: [
                'lemma' => $lemma,
                'semantic_type' => $semanticType,
            ],
            id: $id
        );
    }

    /**
     * Create a predicted node in L23
     *
     * Predicted nodes are created from L5 predictions and are initially
     * NOT activated. They wait for confirmation via backward compatibility checking.
     *
     * When a compatible token arrives in the NEXT column, backward checking
     * activates the predicted node, which then propagates to L5.
     *
     * @param string $type Node type (word, pos, feature, construction)
     * @param string $value Node value
     * @param int $columnPosition Column position where prediction is created
     * @param array $metadata Additional metadata (source_construction_id, source_partial_id, etc.)
     * @return Node Created predicted node
     */
    public function createPredictedNode(
        string $type,
        string $value,
        int    $columnPosition,
        array  $metadata = []
    ): Node
    {
        // Create unique ID for predicted node
        $nodeId = sprintf('predicted_%d_%s_%s', $columnPosition, $type, $value);

        // Base metadata for all predicted nodes
        $baseMetadata = [
            'is_predicted' => true,
            'prediction_confirmed' => false,
            'column_position' => $columnPosition,
            'predicted_value' => $value,  // For checkMatch() in Phase 4
            'predicted_type' => $type,    // For checkMatch() in Phase 4
        ];

        // Merge with provided metadata
        $metadata = array_merge($baseMetadata, $metadata);

        // Create node based on type
        $node = match ($type) {
            'word' => $this->createWordNode(
                word: $value,
                lemma: $value, // Use same value for lemma (will be refined on confirmation)
                id: $nodeId
            ),
            'pos' => $this->createPOSNode(
                pos: $value,
                id: $nodeId
            ),
            'feature' => (function () use ($value, $nodeId) {
                // Parse feature string "name=value"
                if (str_contains($value, '=')) {
                    [$name, $val] = explode('=', $value, 2);

                    return $this->createFeatureNode(
                        feature: $name,
                        value: $val,
                        id: $nodeId
                    );
                }

                throw new \InvalidArgumentException("Invalid feature format: {$value}. Expected 'name=value'");
            })(),
            'construction' => $this->createNode(
                nodeType: 'construction',
                layer: Layer::L23,
                metadata: array_merge([
                    'value' => $value,
                    'name' => $value,
                ], $metadata),
                id: $nodeId
            ),
            default => throw new \InvalidArgumentException("Unknown prediction type: {$type}"),
        };

        // Override/merge metadata for predicted nodes
        $node->metadata = array_merge($node->metadata, $metadata);

        // For Node: set very high threshold to prevent auto-activation
        // For Node: set prevent_activation flag
        if ($node instanceof Node) {
            $node->threshold = PHP_INT_MAX;
        } elseif ($node instanceof Node) {
            $node->metadata['prevent_activation'] = true;
        }

        return $node;
    }
}
