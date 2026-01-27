<?php

namespace App\Models\CLN_RNT;

use App\Data\CLN\Confirmation;
use App\Data\CLN\Prediction;
use App\Models\CLN\BNode;
use App\Models\CLN\JNode;
use App\Services\CLN_RNT\NodeEventRegistry;
use App\Services\CLN_RNT\NodeFactory;

/**
 * L23 Layer (Input Layer)
 *
 * Represents the superficial cortical layers (2/3) in CLN architecture.
 * Contains word, lemma, POS, feature, and construction nodes that receive and process input.
 *
 * Key responsibilities:
 * - Receive input tokens and create corresponding nodes (word, lemma, POS, features)
 * - Evoke single-element constructions that match the input token
 * - Store predicted nodes from L5 predictions
 * - Check backward compatibility for predicted nodes
 * - Propagate activation to L5 layer (feed-forward)
 * - Receive construction feedback from L5 for recursive composition
 *
 * Design principle: Layer is a simple container. Node types are determined
 * by metadata, not class hierarchy. Node links determine activation flow.
 */
class L23Layer
{
    /**
     * Position of this layer in the sequence
     */
    public readonly int $columnPosition;

    /**
     * All nodes in this layer (indexed by node ID)
     * Type-agnostic: contains both JNode and BNode instances
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
     * Predictions received from L5 layer (typically from previous column)
     * Format: array of Prediction objects
     */
    private array $predictions = [];

    /**
     * Available constructions for evocation lookup
     * Format: array of compiled constructions from CLNParser
     */
    private array $constructions = [];

    /**
     * Create a new L23 Layer
     *
     * @param  int  $columnPosition  Position in the sequence (0-based)
     * @param  NodeFactory|null  $factory  Optional factory (creates default if null)
     * @param  NodeEventRegistry|null  $eventRegistry  Optional event registry for node-centric event communication
     */
    public function __construct(
        int $columnPosition,
        ?NodeFactory $factory = null,
        ?NodeEventRegistry $eventRegistry = null
    ) {
        $this->columnPosition = $columnPosition;
        $this->factory = $factory ?? new NodeFactory;
        $this->eventRegistry = $eventRegistry;
    }

    /**
     * Set available constructions for evocation lookup
     *
     * @param  array  $constructions  Compiled constructions from CLNParser
     */
    public function setConstructions(array $constructions): void
    {
        $this->constructions = $constructions;
    }

    /**
     * Get the node factory
     *
     * @return NodeFactory Node factory instance
     */
    public function getFactory(): NodeFactory
    {
        return $this->factory;
    }

    /**
     * Find constructions evoked by a token
     *
     * A construction is evoked if it has a single-element pattern that fully matches
     * the token's POS, word, or lemma. This includes alternation patterns like
     * ({NOUN} | {PROPN}) which are single-element with multiple alternatives.
     *
     * @param  object  $token  Input token (from Trankit/UDPipe)
     * @return array Evoked constructions (subset of $this->constructions)
     */
    private function findEvokedConstructions(object $token): array
    {
        $evoked = [];

        foreach ($this->constructions as $construction) {
            $graph = $construction['graph'] ?? [];
            $nodes = $graph['nodes'] ?? [];
            $edges = $graph['edges'] ?? [];

            // Get pattern elements (exclude START, END, INTERMEDIATE, REP_CHECK)
            $patternElements = array_filter($nodes, function ($node) {
                $type = $node['type'] ?? '';

                return ! in_array($type, ['START', 'END', 'INTERMEDIATE', 'REP_CHECK']);
            });

            if (count($patternElements) === 0) {
                continue;
            }

            // Check if this is a single-element construction
            // It's single-element if:
            // 1. Exactly one pattern element, OR
            // 2. Multiple pattern elements that are all alternatives (START → all elements → END)
            $isSingleElement = false;

            if (count($patternElements) === 1) {
                // Simple case: exactly one element
                $isSingleElement = true;
            } else {
                // Check if this is an alternation pattern (diamond structure)
                // In alternation: START connects to all pattern elements, all pattern elements connect to END
                $isSingleElement = $this->isAlternationPattern($nodes, $edges);
            }

            if (! $isSingleElement) {
                continue;
            }

            // For single-element constructions, check if ANY element matches the token
            foreach ($patternElements as $element) {
                $matches = match ($element['type'] ?? '') {
                    'LITERAL' => $this->matchesLiteral($token, $element),
                    'SLOT' => $this->matchesSlot($token, $element),
                    'WILDCARD' => true, // Wildcard always matches
                    default => false,
                };

                if ($matches) {
                    $evoked[] = $construction;
                    break; // One match is enough
                }
            }
        }

        return $evoked;
    }

    /**
     * Check if pattern graph represents an alternation (A | B | C)
     *
     * Alternation patterns have a diamond structure:
     * START → element1 → END
     * START → element2 → END
     * START → element3 → END
     */
    private function isAlternationPattern(array $nodes, array $edges): bool
    {
        // Find START and END nodes
        $startNode = null;
        $endNode = null;
        $patternNodes = [];

        foreach ($nodes as $nodeId => $node) {
            $type = $node['type'] ?? '';
            if ($type === 'START') {
                $startNode = $nodeId;
            } elseif ($type === 'END') {
                $endNode = $nodeId;
            } elseif (! in_array($type, ['INTERMEDIATE', 'REP_CHECK'])) {
                $patternNodes[] = $nodeId;
            }
        }

        if ($startNode === null || $endNode === null || count($patternNodes) === 0) {
            return false;
        }

        // Check edges: START should connect to all pattern nodes
        // and all pattern nodes should connect to END
        foreach ($patternNodes as $patternNode) {
            $hasStartEdge = false;
            $hasEndEdge = false;

            foreach ($edges as $edge) {
                $from = $edge['from'] ?? null;
                $to = $edge['to'] ?? null;

                if ($from === $startNode && $to === $patternNode) {
                    $hasStartEdge = true;
                }
                if ($from === $patternNode && $to === $endNode) {
                    $hasEndEdge = true;
                }
            }

            // If any pattern node doesn't have both edges, it's not alternation
            if (! $hasStartEdge || ! $hasEndEdge) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if token matches a LITERAL pattern element
     */
    private function matchesLiteral(object $token, array $element): bool
    {
        $literalValue = trim($element['value'] ?? '', '"\'');
        $word = mb_strtolower($token->form ?? '', 'UTF-8');
        $lemma = mb_strtolower($token->lemma ?? '', 'UTF-8');

        return mb_strtolower($literalValue, 'UTF-8') === $word
            || mb_strtolower($literalValue, 'UTF-8') === $lemma;
    }

    /**
     * Check if token matches a SLOT pattern element (POS)
     */
    private function matchesSlot(object $token, array $element): bool
    {
        $expectedPos = mb_strtoupper($element['pos'] ?? '', 'UTF-8');
        $actualPos = mb_strtoupper($token->upos ?? '', 'UTF-8');

        if ($expectedPos !== $actualPos) {
            return false;
        }

        // Check constraint if present
        $constraint = $element['constraint'] ?? null;
        if ($constraint === null) {
            return true;
        }

        // Parse constraint: "Feature=Value"
        if (! str_contains($constraint, '=')) {
            return true;
        }

        [$featureName, $featureValue] = explode('=', $constraint, 2);
        $tokenFeats = $this->parseFeatures($token->feats ?? '');

        return isset($tokenFeats[$featureName])
            && mb_strtoupper($tokenFeats[$featureName], 'UTF-8') === mb_strtoupper($featureValue, 'UTF-8');
    }

    // ========================================================================
    // Node Management (type-agnostic)
    // ========================================================================

    /**
     * Add a node to this layer
     *
     * @param  Node  $node  The node to add
     */
    public function addNode(Node $node): void
    {
        $this->nodes[$node->id] = $node;

        // Maintain type index for O(1) lookup
        $nodeType = $node->metadata['node_type'] ?? 'unknown';
        if (! isset($this->typeIndex[$nodeType])) {
            $this->typeIndex[$nodeType] = [];
        }
        $this->typeIndex[$nodeType][] = $node->id;
    }

    /**
     * Get a node by ID
     *
     * @param  string  $id  Node ID
     */
    public function getNode(string $id): ?Node
    {
        return $this->nodes[$id] ?? null;
    }

    /**
     * Get all nodes of a specific type (filtered by metadata)
     *
     * Uses type index for O(1) lookup instead of O(N) filtering.
     *
     * @param  string  $nodeType  Type from metadata (word, feature, pos, etc.)
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
     * Get node with specific type + name
     *
     * @param  string  $nodeType  Type from metadata (word, feature, pos, etc.)
     * @param  string  $nodeNamee  Name from metadata
     * @return object Node instance
     */
    public function getNodesByTypeName(string $nodeType, string $nodeName): ?Node
    {
        return array_find(
            $this->nodes,
            fn ($node) => (($node->metadata['node_type'] ?? null) === $nodeType) && ($node->metadata['name'] ?? null) === $nodeName
        );
    }

    /**
     * Remove a node by ID
     *
     * @param  string  $id  Node ID to remove
     */
    public function removeNode(string $id): void
    {
        $node = $this->nodes[$id] ?? null;

        if ($node !== null) {
            // Clean up bidirectional connections before removing node
            // Remove this node from all its input nodes' output lists
            $inputNodes = ($node instanceof \App\Models\CLN\JNode) ? $node->getInputNodes() : $node->getInputNodes();
            foreach ($inputNodes as $inputId => $inputData) {
                // Handle both JNode format (with 'node' key) and BNode format (direct node)
                $inputNode = is_array($inputData) ? ($inputData['node'] ?? null) : $inputData;
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
                    fn ($nodeId) => $nodeId !== $id
                );

                // Clean up empty type arrays
                if (empty($this->typeIndex[$nodeType])) {
                    unset($this->typeIndex[$nodeType]);
                }
            }
        }

        unset($this->nodes[$id]);
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
    // Activation
    // ========================================================================

    /**
     * Activate nodes from input token
     *
     * Creates and activates nodes based on token properties (word, lemma, features, POS).
     * This is the entry point for external input.
     *
     * Since these nodes come from external input, they are self-activating (don't need inputs).
     * - BNodes (word, lemma): directly activated
     * - JNodes (POS, features): threshold set to 0 to auto-fire (they ARE the input)
     *
     * @param  object  $token  UDPipe token with properties (form, lemma, upos, feats)
     * @return array Activated nodes
     */
    public function activateFromInput(object $token, int $position): array
    {
        $activatedNodes = [];

        // Create and activate word node (surface form)
        $wordNode = $this->factory->createWordNode(
            word: $token->form ?? '',
            lemma: $token->lemma ?? '',
            id: "word_{$position}_{$token->form}"
        );
        $wordNode->activateFromInput(); // Direct activation from external input
        $this->addNode($wordNode);
        $activatedNodes[] = $wordNode;

        // Create and activate lemma node (base form)
        if (! empty($token->lemma)) {
            $lemmaNode = $this->factory->createNode(
                nodeType: 'lemma',
                layer: Layer::L23,
                metadata: [
                    'value' => $token->lemma,
                ],
                id: "lemma_{$position}_{$token->lemma}"
            );
            $lemmaNode->activateFromInput(); // Direct activation from external input
            $this->addNode($lemmaNode);
            $activatedNodes[] = $lemmaNode;
        }

        // Create and activate POS node (part of speech)
        if (! empty($token->upos) && $token->upos !== '_') {
            $posNode = $this->factory->createNode(
                nodeType: 'pos',
                layer: Layer::L23,
                metadata: [
                    'value' => $token->upos,
                ],
                id: "pos_{$position}_{$token->upos}"
            );
            // For JNodes from input, set threshold to 0 so they auto-fire
            if ($posNode instanceof Node) {
                $posNode->threshold = 0;
            }
            $posNode->activateFromInput(); // Direct activation from external input
            $this->addNode($posNode);
            $activatedNodes[] = $posNode;
        }

        // Find and create construction nodes for constructions evoked by this token
        // This looks up single-element constructions that match the token's POS, word, or lemma
        //        $evokedConstructions = $this->findEvokedConstructions($token);
        //
        //        // DEBUG: Log evoked constructions (optional)
        //        if (config('app.debug') && !empty($evokedConstructions)) {
        //            logger()->debug("L23[{$this->columnPosition}] Found evoked constructions", [
        //                'token' => $token->form ?? '?',
        //                'upos' => $token->upos ?? '?',
        //                'evoked_count' => count($evokedConstructions),
        //                'evoked_names' => array_column($evokedConstructions, 'name'),
        //            ]);
        //        }
        //
        //        foreach ($evokedConstructions as $construction) {
        //            // Extract pattern sequence from graph (same approach as L5Layer)
        //            $patternSequence = $this->extractPatternSequence($construction['graph'] ?? []);
        //
        //            $constructionNode = $this->factory->createL23ConstructionNode(
        //                constructionId: $construction['id'],
        //                name: $construction['name'],
        //                columnPosition: $this->columnPosition,
        //                spanLength: 1,
        //                additionalMetadata: [
        //                    'pattern' => $patternSequence,  // Extract from graph, not construction name
        //                    'graph' => $construction['graph'],
        //                    'is_evoked_by_input' => true,  // Flag to identify evoked constructions
        //                    'is_from_l5_feedback' => false, // Evoked constructions are NOT from L5 feedback
        //                    'is_confirmed' => true,  // Evoked constructions are complete single-element matches
        //                ]
        //            );
        //            // For JNodes from input, set threshold to 0 so they fire immediately
        //            $constructionNode->threshold = 0;
        //            $this->addNode($constructionNode);
        //            $activatedNodes[] = $constructionNode;
        //        }

        // Create and activate feature nodes if available
        //        if (! empty($token->feats) && $token->feats !== '_') {
        //            $features = $this->parseFeatures($token->feats);
        //            foreach ($features as $feature => $value) {
        //                $featureNode = $this->factory->createFeatureNode(
        //                    feature: $feature,
        //                    value: $value,
        //                    id: "feat_{$this->columnPosition}_{$feature}_{$value}"
        //                );
        //                // For JNodes from input, set threshold to 0 so they auto-fire
        //                $featureNode->threshold = 0;
        //                $this->addNode($featureNode);
        //                $activatedNodes[] = $featureNode;
        //            }
        //        }

        return $activatedNodes;
    }

    /**
     * Check if current activation matches predictions
     *
     * Compares activated nodes against predictions received from L5.
     * Returns matched predictions.
     *
     * @param  object  $token  Current token
     * @return array Matched predictions
     */
    public function checkPredictions(object $token): array
    {
        $matches = [];

        foreach ($this->predictions as $prediction) {
            if ($this->predictionMatches($prediction, $token)) {
                $matches[] = $prediction;
            }
        }

        return $matches;
    }

    /**
     * Check if a prediction matches the current token
     *
     * @param  Prediction  $prediction  The prediction to check
     * @param  object  $token  The current token
     * @return bool True if prediction matches
     */
    private function predictionMatches(Prediction $prediction, object $token): bool
    {
        return match ($prediction->type) {
            'word' => mb_strtolower($token->form ?? '') === mb_strtolower($prediction->value),
            'pos' => mb_strtolower($token->upos ?? '') === mb_strtolower($prediction->value),
            'feature' => $this->featureMatches($token, $prediction->value),
            default => false,
        };
    }

    /**
     * Check if token has a specific feature value
     *
     * @param  object  $token  The token to check
     * @param  string  $featureValue  Feature in format "name=value" (e.g., "Gender=Masc")
     * @return bool True if feature matches
     */
    private function featureMatches(object $token, string $featureValue): bool
    {
        if (empty($token->feats)) {
            return false;
        }

        $features = $this->parseFeatures($token->feats);
        [$name, $value] = explode('=', $featureValue, 2);

        return isset($features[$name]) && strcasecmp($features[$name], $value) === 0;
    }

    /**
     * Extract pattern sequence from graph by walking edges
     *
     * Converts graph structure (nodes + edges) into linear sequence of pattern elements.
     * Walks from START to END following edges.
     *
     * @param  array  $graph  Pattern graph with nodes and edges
     * @return array Linear sequence of pattern element values
     */
    private function extractPatternSequence(array $graph): array
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
            if ($node && ! in_array($node['type'] ?? '', ['START', 'END'])) {
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
     * Parse UDPipe feature string into associative array
     *
     * @param  string  $featsString  Features in format "Gender=Masc|Number=Sing"
     * @return array Associative array of feature => value
     */
    private function parseFeatures(string $featsString): array
    {
        if (empty($featsString) || $featsString === '_') {
            return [];
        }

        $features = [];
        foreach (explode('|', $featsString) as $feat) {
            if (str_contains($feat, '=')) {
                [$name, $value] = explode('=', $feat, 2);
                $features[$name] = $value;
            }
        }

        return $features;
    }

    // ========================================================================
    // Prediction Handling
    // ========================================================================

    /**
     * Receive a prediction from L5 layer
     *
     * Predictions come from L5 of a previous column, suggesting what to expect.
     *
     * @param  Prediction  $prediction  The prediction to store
     */
    public function receivePrediction(Prediction $prediction): void
    {
        $this->predictions[] = $prediction;
    }

    /**
     * Get all predictions for this layer
     *
     * @return array Array of Prediction objects
     */
    public function getPredictions(): array
    {
        return $this->predictions;
    }

    /**
     * Clear all predictions
     */
    public function clearPredictions(): void
    {
        $this->predictions = [];
    }

    /**
     * Check previous column for predicted nodes that match current input
     *
     * Backward prediction checking: when token arrives at position N,
     * check position N-1 for predicted nodes. If match found:
     * 1. Confirm and activate the predicted node
     * 2. Update source partial construction's matched array
     *
     * @param  object  $token  Current token
     * @param  L23Layer|null  $previousL23  Previous column's L23 layer
     * @return array Matched predicted nodes from previous column
     */
    public function checkBackwardCompatibility(object $token, ?L23Layer $previousL23): array
    {
        if ($previousL23 === null) {
            return [];
        }

        $matches = [];

        // Get all predicted nodes from previous column
        $predictedNodes = $previousL23->getPredictedNodes();

        foreach ($predictedNodes as $predictedNode) {
            $nodeType = $predictedNode->metadata['node_type'] ?? '';
            $nodeValue = $predictedNode->metadata['value'] ?? '';

            // Check compatibility: exact type and value match
            $compatible = match ($nodeType) {
                'word' => mb_strtolower($token->form ?? '', 'UTF-8') === mb_strtolower($nodeValue, 'UTF-8'),
                'pos' => mb_strtoupper($token->upos ?? '', 'UTF-8') === mb_strtoupper($nodeValue, 'UTF-8'),
                'feature' => $this->featureMatches($token, $nodeValue),
                // For construction predictions: check after L5 creates construction feedback nodes
                'construction' => false, // Will be checked later after L5 processing
                default => false,
            };

            if ($compatible) {
                // ACTIVATE the predicted node
                $predictedNode->confirmPrediction();

                $matches[] = [
                    'node' => $predictedNode,
                    'source_construction_id' => $predictedNode->metadata['source_construction_id'] ?? null,
                    'source_partial_id' => $predictedNode->metadata['source_partial_id'] ?? null,
                ];
            }
        }

        return $matches;
    }

    /**
     * Check current column for predicted nodes that match current input
     *
     * SELF prediction checking: when token arrives at position N,
     * check position N for predicted nodes created by forward predictions.
     * If match found:
     * 1. Activate the predicted node
     * 2. Source partial construction (in previous column) gets updated
     *
     * @param  object  $token  Current token
     * @return array Matched predicted nodes from current column
     */
    public function checkSelfPredictions(object $token): array
    {
        $matches = [];

        // Get all predicted nodes from current column
        $predictedNodes = $this->getPredictedNodes();

        foreach ($predictedNodes as $predictedNode) {
            $nodeType = $predictedNode->metadata['node_type'] ?? '';
            $nodeValue = $predictedNode->metadata['value'] ?? '';

            // Check compatibility: exact type and value match
            $compatible = match ($nodeType) {
                'word' => mb_strtolower($token->form ?? '', 'UTF-8') === mb_strtolower($nodeValue, 'UTF-8'),
                'pos' => mb_strtoupper($token->upos ?? '', 'UTF-8') === mb_strtoupper($nodeValue, 'UTF-8'),
                'feature' => $this->featureMatches($token, $nodeValue),
                // For construction predictions: check after L5 creates construction feedback nodes
                'construction' => false, // Will be checked later after L5 processing
                default => false,
            };

            if ($compatible) {
                // ACTIVATE the predicted node
                $predictedNode->confirmPrediction();

                $matches[] = [
                    'node' => $predictedNode,
                    'source_construction_id' => $predictedNode->metadata['source_construction_id'] ?? null,
                    'source_partial_id' => $predictedNode->metadata['source_partial_id'] ?? null,
                ];
            }
        }

        return $matches;
    }

    /**
     * Get all predicted nodes (not yet confirmed)
     *
     * Returns nodes that were created from predictions but haven't been
     * confirmed yet by backward compatibility checking.
     *
     * @return array Predicted nodes
     */
    public function getPredictedNodes(): array
    {
        return array_filter(
            $this->nodes,
            fn ($node) => ($node->metadata['is_predicted'] ?? false) === true
                          && ($node->metadata['prediction_confirmed'] ?? false) === false
        );
    }

    /**
     * Cleanup unconfirmed predicted nodes
     *
     * Called after processing each column to remove predicted nodes
     * that weren't confirmed. This prevents accumulation of failed predictions.
     */
    public function cleanupUnconfirmedPredictions(): void
    {
        $unconfirmed = array_filter(
            $this->nodes,
            fn ($node) => ($node->metadata['is_predicted'] ?? false) === true
                          && ($node->metadata['prediction_confirmed'] ?? false) === false
        );

        foreach ($unconfirmed as $node) {
            $this->removeNode($node->id);
        }
    }

    // ========================================================================
    // Lateral Communication (UNIDIRECTIONAL: posterior → anterior)
    // ========================================================================

    /**
     * Send confirmation to anterior column (previous position)
     *
     * When predictions match, L23 sends confirmation BACK to the previous column
     * to boost L5 constructions. This is UNIDIRECTIONAL (posterior → anterior).
     *
     * @param  Confirmation  $confirmation  The confirmation to send
     * @return void This method would be called by column orchestration
     */
    public function sendConfirmationToAnterior(Confirmation $confirmation): void
    {
        // This is a marker method - actual sending is handled by column orchestration
        // The confirmation flows: L23[pos N] → L23[pos N-1] → L5[pos N-1]
    }

    /**
     * Receive confirmation from posterior column (next position)
     *
     * Receives confirmation from the NEXT column (posterior) when its predictions matched.
     * This flows to L5 in the same column to boost constructions.
     *
     * @param  Confirmation  $confirmation  The confirmation received
     * @return void Confirmation will be propagated to L5
     */
    public function receiveConfirmationFromPosterior(Confirmation $confirmation): void
    {
        // This is a marker method - actual boost is applied to L5
        // The confirmation flows: L23[pos N+1] → L23[pos N] → L5[pos N]
    }

    // ========================================================================
    // Feed-forward to L5
    // ========================================================================

    /**
     * Propagate activations to L5 layer
     *
     * Sends activated nodes from L23 to L5 within the same column.
     * This is the feed-forward circuit (L23 → L5, bottom-up).
     *
     * @param  L5Layer  $l5  The L5 layer in the same column
     */
    public function propagateToL5(L5Layer $l5): void
    {
        // Pass all activated nodes to L5 for processing
        // L5 will match them against construction patterns
        $l5->receiveL23Input($this);

        // Evoked constructions (single-element confirmed matches) should be confirmed in L5
        // These are complete constructions, not partial ones awaiting completion
        $constructionNodes = $this->getNodesByType('construction');
        $evokedConstructions = array_filter($constructionNodes, function ($node) {
            return ($node->metadata['is_evoked_by_input'] ?? false) &&
                   ! ($node->metadata['propagated_to_l5'] ?? false);  // Only propagate once
        });

        foreach ($evokedConstructions as $evokedNode) {
            // Mark as propagated to prevent duplicates in subsequent composition cycles
            $evokedNode->metadata['propagated_to_l5'] = true;

            // Directly confirm in L5 (evoked constructions are already complete)
            $l5->confirmEvokedConstruction(
                constructionId: $evokedNode->metadata['construction_id'],
                name: $evokedNode->metadata['name'],
                columnPosition: $this->columnPosition,
                spanLength: 1,
                metadata: $evokedNode->metadata
            );
        }
    }

    /**
     * Receive construction completion feedback from L5 (Circuit 2B: L5 → L23)
     *
     * Creates a construction node in L23 that can participate in higher-level
     * pattern matching for recursive composition.
     *
     * UNIDIRECTIONAL FEEDBACK (simplified to prevent infinite loops):
     * - L5 construction completes → creates L23 node
     * - L23 node does NOT immediately propagate back to L5
     * - Construction node will be available in next propagation cycle
     *
     * This enables hierarchical composition: word → MWE → phrase → clause
     *
     * @param  int  $constructionId  Completed construction ID
     * @param  string  $name  Construction name
     * @param  int  $spanLength  Number of tokens spanned
     * @param  array  $metadata  Additional metadata from L5 construction
     * @return JNode The created L23 construction node
     */
    public function receiveConstructionFeedback(
        int $constructionId,
        string $name,
        int $spanLength,
        array $metadata = []
    ): JNode {
        // Create construction node in L23
        $constructionNode = $this->factory->createL23ConstructionNode(
            constructionId: $constructionId,
            name: $name,
            columnPosition: $this->columnPosition,
            spanLength: $spanLength,
            additionalMetadata: $metadata
        );

        // Set threshold to 1 (will activate with single input from L5)
        $constructionNode->threshold = 1;

        // Add to layer
        $this->addNode($constructionNode);

        // NEW: Check for waiting prediction in centralized manager
        if (config('cln.predictions.centralized_manager', false)) {
            $this->checkAndConfirmPrediction($constructionNode, $name);
        }

        return $constructionNode;
    }

    // ========================================================================
    // Centralized Prediction Management
    // ========================================================================

    /**
     * Check for and confirm waiting prediction from centralized manager
     *
     * Queries the ColumnSequenceManager for predictions matching the given
     * construction name. If found, creates cross-column confirmation link.
     *
     * @param  JNode  $constructionNode  The L23 construction node just created
     * @param  string  $constructionName  Construction name to match
     */
    private function checkAndConfirmPrediction(JNode $constructionNode, string $constructionName): void
    {
        $manager = $this->getSequenceManager();
        if ($manager === null) {
            return;
        }

        // Query manager for waiting prediction
        $predictionEntry = $manager->checkForPrediction($constructionName);

        if ($predictionEntry !== null) {
            // Match found! Create cross-column link and update source partial
            $this->confirmPredictionWithCrossColumnLink($constructionNode, $predictionEntry);
        }
    }

    /**
     * Get sequence manager reference from column
     */
    private function getSequenceManager(): ?\App\Services\CLN_RNT\ColumnSequenceManager
    {
        return $this->column?->getSequenceManager();
    }

    /**
     * Create cross-column confirmation link when prediction matches
     *
     * Creates a predicted node at the source column and links it to the
     * real construction node at the current column.
     *
     * Link direction: predicted_node (source column) → real_node (current column)
     *
     * @param  JNode  $realNode  The real construction node at current column
     * @param  \App\Data\CLN\PredictionEntry  $entry  The matched prediction entry
     */
    private function confirmPredictionWithCrossColumnLink(JNode $realNode, $entry): void
    {
        $manager = $this->getSequenceManager();
        if ($manager === null) {
            return;
        }

        // Get L23 layer at source column
        $sourceColumn = $manager->getColumn($entry->sourceColumn);
        if ($sourceColumn === null) {
            return;
        }

        $sourceL23 = $sourceColumn->getL23();

        // Create predicted node at source column
        $predictedNode = $this->factory->createPredictedNode(
            type: $entry->type,
            value: $entry->value,
            columnPosition: $entry->sourceColumn,
            metadata: [
                'source_partial_id' => $entry->sourcePartialId,
                'source_construction_id' => $entry->constructionId,
                'prediction_strength' => $entry->strength,
                'prediction_confirmed' => true, // Immediately confirmed
                'confirmed_at_column' => $this->columnPosition, // Where match occurred
            ]
        );

        // Confirm prediction (sets threshold to 0)
        $predictedNode->confirmPrediction();

        // Add to source L23 layer
        $sourceL23->addNode($predictedNode);

        // Create cross-column link: predicted_node (source) → real_node (current)
        $predictedNode->addOutput($realNode);
        $realNode->addInput($predictedNode);

        // Mark real node as having confirmed a prediction
        $realNode->metadata['prediction_confirmed'] = true;
        $realNode->metadata['prediction_source_column'] = $entry->sourceColumn;

        // Update source partial construction
        $this->updateSourcePartialConstruction($entry);
    }

    /**
     * Update source partial construction when prediction is confirmed
     *
     * Updates the 'matched' array in the source partial construction
     * and checks if the pattern is now complete.
     *
     * @param  \App\Data\CLN\PredictionEntry  $entry  The confirmed prediction entry
     */
    private function updateSourcePartialConstruction($entry): void
    {
        $manager = $this->getSequenceManager();
        if ($manager === null) {
            return;
        }

        // Get source column and L5 layer
        $sourceColumn = $manager->getColumn($entry->sourceColumn);
        if ($sourceColumn === null) {
            return;
        }

        $l5 = $sourceColumn->getL5();
        $partial = $l5->getNode($entry->sourcePartialId);

        if ($partial === null || ! ($partial instanceof JNode)) {
            return;
        }

        // Update matched array using pattern_index from prediction metadata
        $matched = $partial->metadata['matched'] ?? [];
        $matchedIndex = $entry->metadata['pattern_index'] ?? null;

        if ($matchedIndex !== null && isset($matched[$matchedIndex])) {
            $matched[$matchedIndex] = true;
            $partial->metadata['matched'] = $matched;

            // Check if pattern complete
            if (! in_array(false, $matched, true)) {
                // All elements matched - confirm construction
                $l5->confirmConstruction($partial->id);
            }
        }
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
            if ($node instanceof JNode && $node->isFired()) {
                $total += 1.0;
            } elseif ($node instanceof BNode && $node->isActivated()) {
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
            'layer' => 'L23',
            'node_count' => count($this->nodes),
            'nodes' => array_map(
                fn ($node) => [
                    'id' => $node->id,
                    'type' => $node instanceof JNode ? 'JNode' : 'BNode',
                    'metadata' => $node->metadata,
                ],
                $this->nodes
            ),
            'prediction_count' => count($this->predictions),
            'total_activation' => $this->getTotalActivation(),
        ];
    }

    /**
     * Reset layer state (clear activations, predictions)
     */
    public function reset(): void
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof JNode) {
                $node->reset();
            } elseif ($node instanceof BNode) {
                $node->reset();
            }
        }

        $this->predictions = [];
    }
}
