<?php

namespace App\Models\CLN_RNT;

use App\Data\CLN\ColumnActivationResult;
use App\Models\CLN\JNode;
use App\Services\CLN_RNT\GhostManager;
use App\Services\CLN_RNT\NodeFactory;

/**
 * CLN Column
 *
 * Represents a single cortical column in the CLN architecture using
 * node-centric event-driven processing.
 *
 * Combines L23 (input) and L5 (output) layers for one position in the sequence.
 *
 * Architecture:
 * - L23 receives input tokens and creates word/POS/feature nodes
 * - L5 performs pattern matching and generates construction nodes
 * - Predicted nodes confirm themselves via NodeEventRegistry events
 * - Partial constructions advance themselves when patterns match
 *
 * Processing flow:
 * 1. Process ghost nodes (null instantiation)
 * 2. Activate L23 layer from input token (creates word, POS, feature, evoked construction nodes)
 * 3. Propagate L23 → L5 (feed-forward pattern matching)
 * 4. Iterative composition loop:
 *    a. Check construction predictions before propagation
 *    b. Propagate new L23 construction nodes to L5
 *    c. L5 creates partial constructions
 *    d. Confirmed L5 constructions create L23 feedback nodes
 *    e. Repeat until no new constructions or max depth reached
 * 5. Final construction prediction check
 * 6. Generate predictions for next column position
 *
 * Note: Predicted nodes check themselves via NodeEventRegistry when tokens arrive.
 * Cross-column confirmation happens through node links, not column orchestration.
 */
class CLNColumn
{
    /**
     * Position in the sequence
     */
    public readonly int $position;

    /**
     * L23 layer (input)
     */
    private L23Layer $l23;

    /**
     * L5 layer (output)
     */
    private L5Layer $l5;

    /**
     * Previous column in sequence (anterior)
     */
    private ?CLNColumn $previousColumn = null;

    /**
     * Next column in sequence (posterior)
     */
    private ?CLNColumn $nextColumn = null;

    /**
     * Current token being processed
     */
    private ?object $currentToken = null;

    /**
     * Current column state
     */
    private ColumnState $state;

    /**
     * Ghost manager for null instantiation handling
     */
    private ?GhostManager $ghostManager = null;

    /**
     * Compiled constructions available for pattern matching
     */
    private array $constructions = [];

    /**
     * Node factory for creating nodes
     */
    private NodeFactory $factory;

    /**
     * Track which construction nodes have already found their backward match
     * Format: ['nodeId' => true]
     */
    private array $processedConstructions = [];

    /**
     * Sequence manager reference for centralized prediction control
     */
    private ?\App\Services\CLN_RNT\ColumnSequenceManager $sequenceManager = null;

    /**
     * Create a new CLN Column
     *
     * @param  int  $position  Position in sequence (0-based)
     * @param  NodeFactory|null  $factory  Optional factory for nodes
     * @param  GhostManager|null  $ghostManager  Optional ghost manager
     * @param  \App\Services\CLN_RNT\NodeEventRegistry|null  $eventRegistry  Optional event registry for node-centric architecture
     */
    public function __construct(
        int $position,
        ?NodeFactory $factory = null,
        ?GhostManager $ghostManager = null,
        ?\App\Services\CLN_RNT\NodeEventRegistry $eventRegistry = null
    ) {
        $this->position = $position;
        $this->factory = $factory ?? new NodeFactory;
        $this->l23 = new L23Layer($position, $this->factory, $eventRegistry);
        $this->l5 = new L5Layer($position, $this->factory, $eventRegistry);
        $this->ghostManager = $ghostManager ?? new GhostManager($this->factory);
        $this->state = ColumnState::EMPTY;

        // Link L5 back to this column for construction access
        $this->l5->setColumn($this);
    }

    /**
     * Set available constructions for pattern matching
     *
     * @param  array  $constructions  Compiled constructions from CLNParser
     */
    public function setConstructions(array $constructions): void
    {
        $this->constructions = $constructions;
        // Also pass constructions to L23 for evocation lookup
        $this->l23->setConstructions($constructions);
    }

    /**
     * Get available constructions
     *
     * @return array Compiled constructions
     */
    public function getConstructions(): array
    {
        return $this->constructions;
    }

    // ========================================================================
    // Column Linking
    // ========================================================================

    /**
     * Set previous column (anterior)
     *
     * @param  CLNColumn  $column  Previous column
     */
    public function setPreviousColumn(CLNColumn $column): void
    {
        $this->previousColumn = $column;
    }

    /**
     * Set next column (posterior)
     *
     * @param  CLNColumn  $column  Next column
     */
    public function setNextColumn(CLNColumn $column): void
    {
        $this->nextColumn = $column;
    }

    /**
     * Get previous column
     */
    public function getPreviousColumn(): ?CLNColumn
    {
        return $this->previousColumn;
    }

    /**
     * Get next column
     */
    public function getNextColumn(): ?CLNColumn
    {
        return $this->nextColumn;
    }

    /**
     * Set sequence manager reference
     *
     * @param  \App\Services\CLN_RNT\ColumnSequenceManager  $manager  Sequence manager
     */
    public function setSequenceManager(\App\Services\CLN_RNT\ColumnSequenceManager $manager): void
    {
        $this->sequenceManager = $manager;
    }

    /**
     * Get sequence manager reference
     */
    public function getSequenceManager(): ?\App\Services\CLN_RNT\ColumnSequenceManager
    {
        return $this->sequenceManager;
    }

    // ========================================================================
    // Main Processing
    // ========================================================================

    /**
     * Process input token through this column
     *
     * Node-centric processing flow:
     * 1. Process ghost nodes (null instantiation)
     * 2. Activate L23 from input (creates word, POS, feature, evoked construction nodes)
     * 3. Propagate L23 → L5 (feed-forward pattern matching)
     * 4. Iterative composition loop:
     *    - Check construction predictions before each propagation
     *    - Propagate new L23 construction feedback nodes to L5
     *    - L5 matches patterns and creates partial constructions
     *    - Confirmed L5 constructions create L23 feedback for recursive composition
     *    - Repeat until no new constructions created or max depth reached
     * 5. Final construction prediction check after loop completes
     * 6. Generate predictions for next column position
     *
     * Note: Predicted nodes autonomously check themselves via NodeEventRegistry.
     * Cross-column confirmation happens through node links.
     *
     * @param  object  $token  UDPipe token
     * @return ColumnActivationResult Processing result
     */
    public function processInput(object $token, int $position): ColumnActivationResult
    {
        $this->currentToken = $token;

        // 1. Process ghost nodes (null instantiation)
        // $ghostResults = $this->processGhostNodes($token);

        // 2. Activate L23 from input (creates word, pos, feature, construction nodes)
        // Evoked constructions (single-element matches) are created as L23 construction nodes
        $activatedL23Nodes = $this->l23->activateFromInput($token, $position);

        // NODE-CENTRIC Phase 1: Trigger construction activation after L23 nodes created
        if (config('cln.node_centric_phases.construction_activation', false) && ! empty($activatedL23Nodes)) {
            foreach ($activatedL23Nodes as $node) {
                $node->triggerConstructionActivation($this->l5);
            }
        }

        // 3. Event-driven composition cascade
        // L23 activation triggers construction activation via node triggers
        // L5 constructions that complete call confirmConstruction()
        // confirmConstruction() creates L23 feedback via createL23Feedback()
        // L23 feedback triggers construction activation (cascade continues)
        // Cascade stops automatically at max composition_depth (tracked in node metadata)

        // 7. Update state
        $this->state = ColumnState::ACTIVATED;

        // 8. Build and return result
        return new ColumnActivationResult(
            position: $this->position,
            hasPredictionMatch: false, // Not tracked in node-centric mode (happens via events)
            matchedPredictions: [],     // Not tracked in node-centric mode (happens via events)
            activatedL23Nodes: $this->l23->getAllNodes(),
            activatedFeatures: $this->l23->getNodesByType('feature'),
            activatedPartialConstructions: $this->snapshotPartialConstructions(),
            confirmedConstructions: $this->l5->getNodesByType('construction'),
            generatedPredictions: [],
            totalActivation: $this->l23->getTotalActivation() + $this->l5->getTotalActivation()
        );
    }

    // ========================================================================
    // L23 Processing
    // ========================================================================

    /**
     * Activate L23 layer from input token
     *
     * @param  object  $token  UDPipe token
     */
    private function activateL23(object $token): void
    {
        $this->l23->activateFromInput($token);
    }

    // ========================================================================
    // Ghost Node Processing (Null Instantiation)
    // ========================================================================

    /**
     * Process ghost nodes at this position
     *
     * Handles null instantiation: predicted-but-not-realized elements.
     *
     * @param  object  $token  Current token
     * @return array Ghost processing results
     */
    private function processGhostNodes(object $token): array
    {
        if ($this->previousColumn === null) {
            return [
                'matched_ghosts' => [],
                'persisted_mandatory_ghosts' => [],
                'deactivated_optional_ghosts' => [],
                'created_l23_nodes' => [],
            ];
        }

        return $this->ghostManager->processToken(
            currentL23: $this->l23,
            previousL23: $this->previousColumn->getL23(),
            previousL5: $this->previousColumn->getL5(),
            position: $this->position,
            token: $token
        );
    }

    // ========================================================================
    // L5 Processing
    // ========================================================================

    /**
     * Propagate L23 activations to L5 (feed-forward)
     */
    private function propagateToL5(): void
    {
        $this->l23->propagateToL5($this->l5);
    }

    /**
     * Propagate new L23 construction nodes to L5 (parallel activation)
     *
     * In a cortical network model, columns are always active. When cross-column
     * processing creates new L23 construction nodes at earlier positions (e.g.,
     * ARG_REL@L23@pos_1 created during pos_2 processing), those nodes should
     * naturally propagate to L5 at their own position.
     *
     * This method:
     * 1. Finds NEW L23 construction nodes (from cross-column feedback)
     * 2. Propagates them to L5 for pattern matching
     * 3. Uses node REUSE - if construction with same name exists, updates it
     * 4. Includes loop prevention guard rails
     *
     * This simulates parallel cortical processing where columns don't "finish"
     * but remain active and responsive to new inputs.
     */
    public function propagateNewL23ConstructionsToL5(): void
    {
        // Get new L23 construction nodes that haven't been propagated yet
        $constructionNodes = $this->l23->getNodesByType('construction');
        $newNodes = [];

        foreach ($constructionNodes as $node) {
            // Skip nodes that have already been propagated
            if ($node->metadata['propagated_to_l5'] ?? false) {
                continue;
            }

            // Only propagate construction nodes from L5 feedback
            if (! ($node->metadata['is_from_l5_feedback'] ?? false)) {
                continue;
            }

            // NEW: Skip construction nodes that confirmed a prediction
            // When a L23 construction confirms a predicted node, it should NOT propagate to L5
            // This prevents the confirmed construction from generating new partial constructions
            if ($node->metadata['prediction_confirmed'] ?? false) {
                continue;
            }

            $newNodes[] = $node;
        }

        if (empty($newNodes)) {
            return;
        }

        // Propagate new nodes to L5 with reuse logic
        $this->l5->receiveL23ConstructionNodes($newNodes, $this->l23);

        // Mark nodes as propagated
        foreach ($newNodes as $node) {
            $node->metadata['propagated_to_l5'] = true;
        }
    }

    /**
     * Check if L23 has construction nodes (from L5 feedback)
     *
     * Returns true if any construction nodes were created in L23 as feedback
     * from completed L5 constructions. This indicates that recursive composition
     * is possible and we should propagate L23→L5 again.
     *
     * @return bool True if construction nodes exist in L23
     */
    private function hasConstructionNodesInL23ToPropagate(): bool
    {
        $constructionNodes = $this->l23->getNodesByType('construction');

        $n = 0;
        // Prevent duplicate feedback creation
        foreach ($constructionNodes as $constructionNode) {
            if ($constructionNode->metadata['l23_feedback_created'] ?? false) {
                continue;
            }
            $n++;
        }

        return $n > 0; // count($constructionNodes) > 0;
    }

    // ========================================================================
    // Prediction Handling
    // ========================================================================

    /**
     * Generate predictions for current column position
     *
     * L5 generates predictions, creating predicted nodes in L23 that wait
     * for confirmation when the next token arrives at this position.
     *
     * @return array Array of Prediction objects (for tracking/debugging)
     */
    private function generatePredictions(): array
    {
        return $this->l5->generatePredictions($this->position);
    }

    // ========================================================================
    // Construction Prediction Checking
    // ========================================================================

    /**
     * Public wrapper for checkConstructionPredictions()
     *
     * Allows external callers (e.g., after confirming a partial construction)
     * to trigger construction prediction checking.
     */
    public function checkConstructionPredictionsPublic(): void
    {
        $this->checkConstructionPredictions();
    }

    /**
     * Check if a specific construction node confirms a prediction
     *
     * Called immediately when a L23 construction node is created as feedback from L5.
     * If the construction confirms a prediction, mark it to prevent propagation to L5.
     *
     * @param  mixed  $construction  The L23 construction node to check
     */
    public function checkIfConstructionConfirmsPrediction($construction): void
    {
        if ($construction->metadata['is_predicted'] ?? false) {
            return; // Skip predicted nodes themselves
        }

        $constructionName = $construction->metadata['name'] ?? '';
        if (empty($constructionName)) {
            return;
        }

        // Check BACKWARD predictions from ALL previous columns
        $checkColumn = $this->previousColumn;
        while ($checkColumn !== null) {
            $checkL23 = $checkColumn->getL23();
            $predictedNodes = $checkL23->getPredictedNodes();

            // Filter for construction-type predictions
            $constructionPredictions = array_filter(
                $predictedNodes,
                fn ($node) => ($node->metadata['node_type'] ?? '') === 'construction'
            );

            foreach ($constructionPredictions as $predictedNode) {
                $predictedValue = $predictedNode->metadata['value'] ?? '';

                if (mb_strtoupper($constructionName, 'UTF-8') === mb_strtoupper($predictedValue, 'UTF-8')) {
                    // MATCH! Mark this construction as prediction_confirmed
                    $construction->metadata['prediction_confirmed'] = true;

                    return; // Found match, no need to continue
                }
            }

            // Move to next previous column
            $checkColumn = $checkColumn->getPreviousColumn();
        }
    }

    /**
     * Check construction predictions after L5 processing
     *
     * Construction predictions can only be checked AFTER L5 creates construction
     * feedback nodes in L23. This happens after propagateToL5().
     *
     * Checks BOTH:
     * 1. Backward predictions (from previous column)
     * 2. Self predictions (from current column via forward predictions)
     */
    private function checkConstructionPredictions(): void
    {
        // Get construction feedback nodes in current L23
        $allConstructions = $this->l23->getNodesByType('construction');

        // EXCLUDE predicted nodes - we only want confirmed construction feedback nodes
        $currentConstructions = array_filter(
            $allConstructions,
            fn ($node) => ! ($node->metadata['is_predicted'] ?? false)
        );

        if (empty($currentConstructions)) {
            return;
        }

        // 1. Check SELF construction predictions (forward predictions to current column)
        $selfPredictedNodes = $this->l23->getPredictedNodes();
        $selfConstructionPredictions = array_filter(
            $selfPredictedNodes,
            fn ($node) => ($node->metadata['node_type'] ?? '') === 'construction'
        );

        foreach ($selfConstructionPredictions as $predictedNode) {
            $predictedValue = $predictedNode->metadata['value'] ?? '';

            foreach ($currentConstructions as $construction) {
                $constructionName = $construction->metadata['name'] ?? '';

                if (mb_strtoupper($constructionName, 'UTF-8') === mb_strtoupper($predictedValue, 'UTF-8')) {
                    // MATCH! Confirm the predicted node
                    $predictedNode->confirmPrediction();

                    // Create self-confirmation link: predicted node → confirming construction
                    $predictedNode->addOutput($construction);
                    $construction->addInput($predictedNode);

                    // Mark this construction as processed
                    $this->processedConstructions[$construction->id] = true;

                    // NEW: Mark construction as having confirmed a prediction
                    // This prevents it from propagating to L5 (see propagateNewL23ConstructionsToL5)
                    $construction->metadata['prediction_confirmed'] = true;

                    // Update partial construction in previous column
                    if ($this->previousColumn !== null) {
                        $previousL5 = $this->previousColumn->getL5();
                        $this->updatePartialConstructionFromBackwardMatch(
                            $previousL5,
                            [
                                'node' => $predictedNode,
                                'source_partial_id' => $predictedNode->metadata['source_partial_id'] ?? null,
                            ],
                            $this->position
                        );
                    }

                    break; // Only match once
                }
            }
        }

        // 2. Check BACKWARD construction predictions (from ALL previous columns)
        // For EACH current construction, search backward to find ONE matching predicted node
        // This allows multiple constructions to match their predictions (e.g., PRED and REL)
        foreach ($currentConstructions as $construction) {
            $constructionName = $construction->metadata['name'] ?? '';

            // Skip if this construction has already found its backward match
            if (isset($this->processedConstructions[$construction->id])) {
                continue;
            }

            $checkColumn = $this->previousColumn;
            $matchFoundForThisConstruction = false;

            // Search backward through previous columns for a match for THIS specific construction
            while ($checkColumn !== null && ! $matchFoundForThisConstruction) {
                $checkL23 = $checkColumn->getL23();
                $predictedNodes = $checkL23->getPredictedNodes();

                // Filter for construction-type predictions
                $constructionPredictions = array_filter(
                    $predictedNodes,
                    fn ($node) => ($node->metadata['node_type'] ?? '') === 'construction'
                );

                foreach ($constructionPredictions as $predictedNode) {
                    $predictedValue = $predictedNode->metadata['value'] ?? '';

                    if (mb_strtoupper($constructionName, 'UTF-8') === mb_strtoupper($predictedValue, 'UTF-8')) {
                        // MATCH! Confirm the predicted node
                        $predictedNode->confirmPrediction();

                        // Create backward confirmation link: predicted node → confirming construction
                        $predictedNode->addOutput($construction);
                        $construction->addInput($predictedNode);

                        // NEW: Mark construction as having confirmed a prediction
                        // This prevents it from propagating to L5 (see propagateNewL23ConstructionsToL5)
                        $construction->metadata['prediction_confirmed'] = true;

                        // Create composition link from confirmed predicted node to source partial construction
                        $checkL5 = $checkColumn->getL5();
                        $sourcePartialId = $predictedNode->metadata['source_partial_id'] ?? null;
                        if ($sourcePartialId !== null) {
                            $partials = array_merge(
                                $checkL5->getPartialConstructions(),
                                $checkL5->getNodesByType('construction')
                            );

                            foreach ($partials as $partial) {
                                if ($partial->id === $sourcePartialId) {
                                    // CIRCUIT 3: L23 → L5 (Composition - completing partial construction)
                                    $predictedNode->addOutput($partial);
                                    $partial->addInput($predictedNode);
                                    break;
                                }
                            }
                        }

                        // Update partial construction
                        $this->updatePartialConstructionFromBackwardMatch(
                            $checkL5,
                            [
                                'node' => $predictedNode,
                                'source_partial_id' => $predictedNode->metadata['source_partial_id'] ?? null,
                            ],
                            $this->position
                        );

                        // Mark this construction as processed so it won't be checked again
                        $this->processedConstructions[$construction->id] = true;

                        $matchFoundForThisConstruction = true; // Stop searching for THIS construction
                        break; // Exit predicted node loop
                    }
                }

                // Move to next previous column if no match found yet for this construction
                if (! $matchFoundForThisConstruction) {
                    $checkColumn = $checkColumn->getPreviousColumn();
                }
            }
        }
    }

    /**
     * Update partial construction when backward prediction confirmed
     *
     * When a predicted node is confirmed by backward compatibility checking,
     * update the partial construction's matched array and check if complete.
     *
     * @param  L5Layer  $l5  L5 layer with partial construction
     * @param  array  $match  Match data from backward compatibility check
     * @param  int  $matchedPosition  Position that matched the prediction
     */
    private function updatePartialConstructionFromBackwardMatch(
        L5Layer $l5,
        array $match,
        int $matchedPosition
    ): void {
        $partialId = $match['source_partial_id'] ?? null;
        if ($partialId === null) {
            return;
        }

        $partial = $l5->getNode($partialId);
        if ($partial === null || ! ($partial instanceof JNode)) {
            return;
        }

        // Update matched array
        $matched = $partial->metadata['matched'] ?? [];
        $anchorPosition = $partial->metadata['anchor_position'] ?? 0;
        $matchedIndex = $matchedPosition - $anchorPosition;

        if (isset($matched[$matchedIndex])) {
            $matched[$matchedIndex] = true;
            $partial->metadata['matched'] = $matched;

            // Check if pattern complete
            if (! in_array(false, $matched, true)) {
                $l5->confirmConstruction($partialId);

                // CRITICAL: After confirming construction, it creates L23 feedback
                // We need to immediately check if this new construction can confirm
                // any predicted nodes in previous columns!
                $confirmedColumn = $l5->getColumn();
                if ($confirmedColumn !== null) {
                    $confirmedColumn->checkConstructionPredictionsPublic();
                }
            }
        }
    }

    // ========================================================================
    // State & Introspection
    // ========================================================================

    /**
     * Get current column state
     *
     * @return ColumnState Current state
     */
    public function getState(): ColumnState
    {
        return $this->state;
    }

    /**
     * Get L23 layer
     */
    public function getL23(): L23Layer
    {
        return $this->l23;
    }

    /**
     * Get L5 layer
     */
    public function getL5(): L5Layer
    {
        return $this->l5;
    }

    /**
     * Get current token
     */
    public function getCurrentToken(): ?object
    {
        return $this->currentToken;
    }

    /**
     * Create snapshot of partial constructions at current state
     *
     * Creates deep copies of partial construction nodes to preserve their
     * state at this moment. This prevents later mutations from affecting
     * the captured state in ColumnActivationResult.
     *
     * @return array Array of partial construction snapshots
     */
    private function snapshotPartialConstructions(): array
    {
        $partials = $this->l5->getPartialConstructions();
        $snapshots = [];

        foreach ($partials as $partial) {
            // Create a new node object with copied metadata
            $snapshot = clone $partial;
            $snapshot->metadata = $partial->metadata; // PHP arrays are copied by value

            $snapshots[] = $snapshot;
        }

        return $snapshots;
    }

    /**
     * Reset column state
     */
    public function reset(): void
    {
        $this->l23->reset();
        $this->l5->reset();
        $this->currentToken = null;
        $this->state = ColumnState::EMPTY;
    }

    /**
     * Get snapshot of column state
     *
     * @return array Column state as array
     */
    public function snapshot(): array
    {
        return [
            'position' => $this->position,
            'state' => $this->state->value,
            'has_previous' => $this->previousColumn !== null,
            'has_next' => $this->nextColumn !== null,
            'current_token' => $this->currentToken
                ? [
                    'form' => $this->currentToken->form ?? null,
                    'lemma' => $this->currentToken->lemma ?? null,
                    'upos' => $this->currentToken->upos ?? null,
                ]
                : null,
            'l23' => $this->l23->toArray(),
            'l5' => $this->l5->toArray(),
        ];
    }
}
