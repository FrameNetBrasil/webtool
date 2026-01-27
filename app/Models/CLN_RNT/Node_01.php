<?php

namespace App\Models\CLN_RNT;

use App\Services\CLN_RNT\ConstructionActivationService;
use App\Services\CLN_RNT\GraphPatternMatcher;
use App\Services\CLN_RNT\PatternMatcher;

class Node_01
{
    /**
     * Unique identifier for this node
     */
    public readonly string $id;

    public string $idPatternNode;

    /**
     * NeuralPopulation this node belongs to (L23, L5, VIP, PV, SOM)
     */
    public readonly NeuralPopulation $neuralPopulation;

    /**
     * Activation threshold (number of inputs that must fire)
     * Default: all inputs must fire (= count of input nodes)
     */
    public int $threshold;

    /**
     * Activation
     */
    public float $activation;

    /**
     * Whether the threshold was auto-set (true) or manually specified (false)
     */
//    private bool $autoThreshold;

    /**
     * Whether input order matters for activation (AND)
     * If true, inputs must fire in the order they were added
     */
    public bool $ordered;

    /**
     * Input nodes linked to this branch
     * Format: ['nodeId' => Node]
     */
    private array $inputNodes = [];

    /**
     * Output nodes this branch links to
     * Format: ['nodeId' => Node]
     */
    private array $outputNodes = [];

    /**
     * Current activation state
     */
    private bool $activated = false;

    /**
     * Activation history (for debugging/tracking)
     * Format: ['fromNodeId' => timestamp]
     */
    private array $activationHistory = [];

    /**
     * Current activation state from each input
     * Format: ['nodeId' => bool]
     */
    private array $activations = [];

    /**
     * Index of next expected input (for ordered AND nodes)
     */
    private int $nextInputIndex = 0;

    /**
     * Optional metadata of this node
     */
    public array $metadata = [];

    /**
     * Node type: DATA, OR, AND, SEQUENCER
     */
    public string $type;

    /**
     * Positions occupied by the node
     */
    public array $span;

    /**
     * Create a new Node
     *
     * @param NeuralPopulation $neuralPopoulation The neuralPopulation this node belongs to
     * @param string|null $id Optional custom ID (auto-generated if null)
     */
    public function __construct(
//        NeuralPopulation   $neuralPopulation,
        ?string $id,
        string $type, // DATA, OR, AND, SEQUENCER
        string $idPatternNode,
        array $span,
        array $metadata
    )
    {
        $this->id = $id ?? $this->generateId();
        $this->idPatternNode = $idPatternNode;
//        $this->neuralPopulation = $neuralPopulation;
        $this->threshold = $threshold ?? 0;
//        $this->autoThreshold = true;
        $this->ordered = true;
        $this->span = $span;
        $this->type = $type;
        $this->metadata = $metadata;
        $this->activation = 0.0;
    }

//    public function getLayer(): string {
//        return $this->neuralPopulation->getLayer();
//    }

    public function getName(): string {
        return $this->metadata['name'] ?? $this->id;
    }

    public function getIdPatternNode(): string {
        return $this->idPatternNode;
    }

    /**
     * Add an input node
     *
     * @param Node $node The node to add as input
     */
    public function addInput(Node_01 $node): self
    {
        $this->inputNodes[$node->id] = $node;

        return $this;
    }

    /**
     * Add an output node
     *
     * @param Node $node The node to add as output
     */
    public function addOutput(Node_01 $node): self
    {
        $this->outputNodes[$node->id] = $node;

        return $this;
    }

    /**
     * Remove an input node
     */
    public function removeInput(string $nodeId): self
    {
        unset($this->inputNodes[$nodeId]);

        return $this;
    }

    /**
     * Remove an output node
     */
    public function removeOutput(string $nodeId): self
    {
        unset($this->outputNodes[$nodeId]);

        return $this;
    }

    /**
     * Receive activation from an input node and propagate to outputs
     *
     * @param Node $fromNode The node sending the activation
     * @return array List of output nodes that were activated
     */
//    public function activate(Node $fromNode): array
//    {
//        // Validate input node exists
//        if (! isset($this->inputNodes[$fromNode->id])) {
//            return [];
//        }
//
//        // Check if activation is prevented (for predicted nodes)
//        if ($this->metadata['prevent_activation'] ?? false) {
//            return [];
//        }
//
//        // Record activation
//        $this->activated = true;
//        $this->activationHistory[$fromNode->id] = microtime(true);
//
//        // Emit ACTIVATED event for node-centric communication
//        $this->emit(NodeEvent::ACTIVATED, [
//            'from_node' => $fromNode,
//            'activation_time' => microtime(true),
//        ]);
//
//        // Propagate to all outputs
//        return $this->propagate();
//    }

    /**
     * Receive activation from an input node
     *
     * @param Node $fromNode The node sending the activation
     * @return bool True if this node fires (threshold reached)
     */
    public function activate(Node_01 $fromNode): bool
    {
        // Validate input node exists
        if (!isset($this->inputNodes[$fromNode->id])) {
            return false;
        }

        // For ordered nodes, check if this is the expected input
        if ($this->ordered) {
            $expectedOrder = $this->nextInputIndex;
            $actualOrder = $this->inputNodes[$fromNode->id]['order'];

            if ($actualOrder !== $expectedOrder) {
                // Out of order activation - ignore
                return false;
            }

            $this->nextInputIndex++;
        }

        // Record activation
        $this->activations[$fromNode->id] = true;

        // Check if threshold reached
        return $this->checkThreshold();
    }

    /**
     * Check if activation threshold has been reached
     *
     * @return bool True if threshold reached
     */
    public function checkThreshold(): bool
    {
        $activeCount = count(array_filter($this->activations));

        return $activeCount >= $this->threshold;
    }

    /**
     * Get current activation count
     */
    public function getActivationCount(): int
    {
        return count(array_filter($this->activations));
    }

    /**
     * Reset all activations
     */
    public function reset(): void
    {
        $this->activated = false;
        $this->activationHistory = [];
        foreach ($this->activations as $nodeId => $state) {
            $this->activations[$nodeId] = false;
        }
        $this->nextInputIndex = 0;
    }

    /**
     * Directly activate this node (for external input)
     *
     * Used when node receives activation from external source (e.g., token input)
     * rather than from another node in the network.
     */
    public function activateFromInput(): void
    {
        // Check if activation is prevented (for predicted nodes)
        if ($this->metadata['prevent_activation'] ?? false) {
            return;
        }

        $this->activated = true;
        $this->activationHistory['external_input'] = microtime(true);

        // Emit ACTIVATED event for node-centric communication
        $this->emit(NodeEvent::ACTIVATED, [
            'from_input' => true,
            'activation_time' => microtime(true),
        ]);
    }

    /**
     * Propagate activation to all output nodes
     *
     * @return array List of output nodes that were activated
     */
    public function propagate(): array
    {
        $activatedNodes = [];

        foreach ($this->outputNodes as $nodeId => $node) {
            // Activate each output node
            $node->activate($this);
            $activatedNodes[] = $node;
        }

        return $activatedNodes;
    }

    /**
     * Reset activation state
     */
//    public function reset(): void
//    {
//        $this->activated = false;
//        $this->activationHistory = [];
//    }

    /**
     * Get all input nodes
     *
     * @return array Format: ['nodeId' => Node]
     */
    public function getInputNodes(): array
    {
        return $this->inputNodes;
    }

    /**
     * Get all output nodes
     *
     * @return array Format: ['nodeId' => Node]
     */
    public function getOutputNodes(): array
    {
        return $this->outputNodes;
    }

    /**
     * Get activation history
     *
     * @return array Format: ['fromNodeId' => timestamp]
     */
    public function getActivationHistory(): array
    {
        return $this->activationHistory;
    }

    /**
     * Check if node is currently activated
     */
    public function isActivated(): bool
    {
        return $this->activated;
    }

    /**
     * Get the number of times this node has been activated
     */
//    public function getActivationCount(): int
//    {
//        return count($this->activationHistory);
//    }

    /**
     * Generate a unique node ID
     */
    private function generateId(): string
    {
        return 'bnode_' . uniqid();
    }

    /**
     * Check if this node is a predicted node (not yet confirmed)
     *
     * Predicted nodes are created from L5 predictions and wait for
     * backward compatibility confirmation from the next column.
     *
     * @return bool True if this is a predicted node
     */
    public function isPredicted(): bool
    {
        return ($this->metadata['is_predicted'] ?? false) === true;
    }

    /**
     * Confirm a predicted node and allow it to activate
     *
     * Called when backward compatibility checking finds a match.
     * Removes the activation barrier so the node can fire normally.
     */
    public function confirmPrediction(): void
    {
        if (!$this->isPredicted()) {
            return;
        }

        // Mark as confirmed
        $this->metadata['prediction_confirmed'] = true;

        // Remove activation prevention
        $this->metadata['prevent_activation'] = false;

        // Activate the node immediately
        $this->activated = true;
        $this->activationHistory['prediction_confirmed'] = microtime(true);
    }

    /**
     * Confirm partial construction (NODE-CENTRIC Phase 4)
     *
     * When a partial construction is complete (all pattern elements matched),
     * this method transitions it to confirmed state
     *
     * Part of Phase 4 of node-centric refactoring.
     *
     * @param \App\Models\CLN_RNT\L5Layer $l5 L5 layer containing this partial
     */
    public function confirmConstruction(\App\Models\CLN_RNT\L5Layer $l5): void
    {
        // Only partial constructions can be confirmed
        if (!($this->metadata['is_partial'] ?? false)) {
            return;
        }

        // Mark as confirmed (no longer partial)
        $this->metadata['is_partial'] = false;
        $this->metadata['node_type'] = 'construction';

        // Emit confirmation event for tracking/debugging
//        $this->emit(NodeEvent::CONSTRUCTION_CONFIRMED, [
//            'construction' => $this,
//            'name' => $this->metadata['name'] ?? 'UNKNOWN',
//            'construction_id' => $this->metadata['construction_id'] ?? null,
//        ]);
    }


    /**
     * Trigger construction activation (NODE-CENTRIC Phase 1)
     *
     * When this node activates, it triggers construction pattern matching.
     * This enables node-centric construction activation instead of having
     * layers iterate over all constructions in a centralized loop.
     *
     * Part of Phase 1 of node-centric refactoring.
     *
     * @param L5Layer $l5 L5 layer for construction creation
     * @return array Created partial constructions
     */
    public function triggerConstructionActivation(L5Layer $l5): void
    {
        // Check if node-centric construction activation is enabled
//        if (! config('cln.node_centric_phases.construction_activation', false)) {
//            return []; // Feature disabled, use legacy path
//        }

        // Get all L23 nodes from same column (siblings)
//        $column = $l5->getColumn();
//        if ($column === null) {
//            return [];
//        }

//        $l23 = $column->getL23();
//        $l23Nodes = $l23->getAllNodes();

        // Check if shared graph pattern matching is enabled
//        if (! config('cln.pattern_matching.use_shared_graph', false)) {
//            return []; // Shared graph required for node-centric activation
//        }

        // Use shared graph matcher for O(1) matching
        $matcher = new \App\Services\CLN_RNT\GraphPatternMatcher;

        //$l23Nodes = [$this];
        debug('##'. ($this->metadata['name'] ?? $this->id));
        // Delegate to service
        \App\Services\CLN_RNT\ConstructionActivationService::checkActivation(
            $this,
            $matcher,
            $l5
        );

//        debug($this->metadata);



    }

    /**
     * Link Node to construction
     *
     * Establishes connections from Node to the construction node.
     * This creates the feed-forward activation circuit within the column.
     *
     * @param Node $construction The construction node to link to
     * @param array $graphNode The pattern graph node that matched
     * @param PatternMatcher $matcher Pattern matcher for finding matches
     */
    public function linkToConstruction(Node_01 $construction, array $graphNode, GraphPatternMatcher $matcher, L5Layer $l5): void
    {
        // Find which L23 nodes actually matched the pattern
        // Note: For SLOT patterns with constraints, we need to pass ALL l23Nodes
        // so that matchConstraint() can find the feature nodes
//        foreach ($l23Nodes as $node) {
        // Check if this specific node matches the graph node requirement
        //  X IMPORTANT: Pass all L23 nodes, not just this one, so constraint checking works
        $patternMatcher = new \App\Services\CLN_RNT\PatternMatcher;
        if ($patternMatcher->matchesNode($this, $graphNode)) {
            $nodeType = $this->metadata['node_type'] ?? '';

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
                $this->addOutput($construction);
                $construction->addInput($this);


                if ($matcher->isPatternComplete($graphNode['id'], $construction->metadata['pattern_id'])) {
                    $construction->confirmConstruction($l5);
                    debug('%% is complete '. ($construction->metadata['name'] ?? $construction->id));
                    ConstructionActivationService::checkAndConfirmPrediction(
                        $construction,
                        $l5
                    );
                    $construction->triggerConstructionActivation($l5);

                }
            }
        }
//        }
    }

    /**
     * Generate prediction for next expected element (NODE-CENTRIC Phase 2)
     *
     * For partial construction nodes, generates a prediction for the next
     * unmatched element in the pattern and registers it with the centralized manager.
     *
     * This method enables partial constructions to generate their own predictions
     * rather than having L5Layer generate them in a loop.
     *
     * Part of Phase 2 of node-centric refactoring.
     *
     * @param \App\Models\CLN_RNT\L5Layer|null $l5 L5 layer for accessing helpers
     * @param \App\Services\CLN_RNT\ColumnSequenceManager|null $manager Manager for registration
     */
    public function generatePrediction(?\App\Models\CLN_RNT\L5Layer $l5 = null, ?\App\Services\CLN_RNT\ColumnSequenceManager $manager = null): void
    {
        // Only partial constructions generate predictions
        if (!($this->metadata['is_partial'] ?? false)) {
            return;
        }

        // Require L5 layer and manager
        if ($l5 === null || $manager === null) {
            return;
        }

        // Delegate to service
        \App\Services\CLN_RNT\PredictionGenerationService::calculatePrediction($this, $manager, $l5);
    }

    /**
     * Check if node is currently fired (threshold reached)
     */
    public function isFired(): bool
    {
        return $this->checkThreshold();
    }

    /**
     * Get string representation of this node
     */
    public function __toString(): string
    {
        return sprintf(
            'BNode[%s, %s, in:%d, out:%d]',
            $this->id,
//            $this->layer->value,
            $this->activated ? 'active' : 'inactive',
            count($this->inputNodes),
            count($this->outputNodes)
        );
    }

}
