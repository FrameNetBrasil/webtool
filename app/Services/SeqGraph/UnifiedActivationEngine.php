<?php

namespace App\Services\SeqGraph;

use App\Models\SeqGraph\ActivationResult;
use App\Models\SeqGraph\ParseEvent;
use App\Models\SeqGraph\SeqNode;
use App\Models\SeqGraph\UnifiedSequenceGraph;

/**
 * Activation engine for unified sequence graphs.
 *
 * Simplified engine that operates on a single unified graph. Pattern
 * completion is handled via explicit graph edges (PATTERN → CONSTRUCTION_REF)
 * rather than recursive processing calls.
 */
class UnifiedActivationEngine
{
    /**
     * The unified sequence graph.
     */
    private UnifiedSequenceGraph $graph;

    /**
     * Listener index mapping element types to active node IDs.
     *
     * Format: ['elementType' => ['nodeId1', 'nodeId2', ...]]
     *
     * @var array<string, array<string>>
     */
    private array $listenerIndex = [];

    /**
     * Current logical time (increments with each input).
     */
    private int $currentTime = 0;

    /**
     * Parse events for building result tree.
     *
     * @var array<ParseEvent>
     */
    private array $parseEvents = [];

    /**
     * Current input type being processed.
     */
    private ?string $currentInputType = null;

    /**
     * Current input value being processed.
     */
    private ?string $currentInputValue = null;

    /**
     * Create a new unified activation engine.
     *
     * @param  UnifiedSequenceGraph  $graph  The unified graph
     */
    public function __construct(UnifiedSequenceGraph $graph)
    {
        $this->graph = $graph;
    }

    /**
     * Initialize the engine by activating the global START node.
     *
     * Propagates activation to all pattern entry nodes.
     */
    public function initialize(): void
    {
        $startNode = $this->graph->getNode($this->graph->globalStartId);
        if ($startNode !== null) {
            $startNode->activate();
            $this->propagateFrom($startNode->id);
        }
        $this->rebuildListenerIndex();
    }

    /**
     * Process an input element through all active listeners.
     *
     * @param  string  $elementType  Type of input element (e.g., 'NOUN', 'DET')
     * @param  string  $elementValue  Value of input element (e.g., 'cat', 'the')
     * @return ActivationResult Result with fired nodes, completions, new listeners
     */
    public function processInput(string $elementType, string $elementValue): ActivationResult
    {
        $this->currentTime++;
        $this->currentInputType = $elementType;
        $this->currentInputValue = $elementValue;
        $firedNodes = [];
        $completedPatterns = [];

        // Find all active nodes listening for this element type
        $listenerNodeIds = $this->listenerIndex[$elementType] ?? [];

        foreach ($listenerNodeIds as $nodeId) {
            $node = $this->graph->getNode($nodeId);

            if ($node === null || ! $node->active) {
                continue;
            }

            // Check if element value matches (if specified)
            if ($node->elementValue !== null && $node->elementValue !== $elementValue) {
                continue;
            }

            // Fire the node
            $node->fire($this->currentTime);
            $firedNodes[] = [$node->patternName ?? 'GLOBAL', $nodeId];

            // Record parse event for terminal element
            if ($node->patternName !== null) {
                $this->parseEvents[] = ParseEvent::elementFired(
                    $this->currentTime,
                    $node->patternName,
                    $nodeId,
                    $elementType,
                    $elementValue
                );
            }

            // Deactivate and propagate
            $node->deactivate();
            $completions = $this->propagateFrom($nodeId);

            // Collect any pattern completions from propagation
            foreach ($completions as $patternName) {
                $completedPatterns[] = $patternName;
            }
        }

        // Rebuild listener index after propagation
        $this->rebuildListenerIndex();

        // Collect new listeners
        $newListeners = [];
        foreach ($this->graph->getActiveListeners() as $node) {
            $newListeners[] = [$node->patternName ?? 'GLOBAL', $node->id];
        }

        return new ActivationResult($firedNodes, array_unique($completedPatterns), $newListeners);
    }

    /**
     * Propagate activation from a node to its successors.
     *
     * PATTERN nodes propagate to their linked CONSTRUCTION_REF listeners
     * automatically via the graph edges. When propagation flows from a
     * PATTERN node to an element node (CONSTRUCTION_REF), that element
     * fires immediately since the pattern completion IS the input for it.
     *
     * @param  string  $nodeId  ID of node to propagate from
     * @param  bool  $fromPattern  Whether propagation is from a PATTERN node
     * @return array<string> Pattern names that completed during propagation
     */
    private function propagateFrom(string $nodeId, bool $fromPattern = false): array
    {
        $completedPatterns = [];
        $sourceNode = $this->graph->getNode($nodeId);
        $successors = $this->graph->getSuccessors($nodeId);

        // Check if we're propagating from a PATTERN node
        $isPatternSource = $sourceNode !== null && $sourceNode->type === SeqNode::TYPE_PATTERN;

        foreach ($successors as $successor) {
            $successor->activate();

            // Routing nodes (start/end/pattern/intermediate) propagate immediately
            if ($successor->isRouting()) {
                $successor->fire($this->currentTime);

                // Track pattern completions
                if ($successor->type === SeqNode::TYPE_PATTERN && $successor->patternName !== null) {
                    $completedPatterns[] = $successor->patternName;

                    // Record pattern completion event
                    $this->parseEvents[] = ParseEvent::patternCompleted(
                        $this->currentTime,
                        $successor->patternName,
                        $nodeId
                    );

                    // Reactivate pattern entry nodes so pattern can match again
                    $this->reactivatePattern($successor->patternName);
                }

                // Continue propagation, marking that we're coming from a PATTERN node
                $nestedCompletions = $this->propagateFrom($successor->id, $successor->type === SeqNode::TYPE_PATTERN);
                $completedPatterns = array_merge($completedPatterns, $nestedCompletions);
            } elseif ($isPatternSource && $successor->isElement()) {
                // When propagating from PATTERN to element (CONSTRUCTION_REF),
                // fire the element immediately - the pattern completion IS the input
                $successor->fire($this->currentTime);
                $successor->deactivate();

                // Record construction ref fired event
                if ($successor->patternName !== null && $sourceNode->patternName !== null) {
                    $this->parseEvents[] = ParseEvent::constructionRefFired(
                        $this->currentTime,
                        $successor->patternName,
                        $successor->id,
                        $successor->elementType ?? '',
                        $sourceNode->patternName
                    );
                }

                // Continue propagation from this fired element
                $nestedCompletions = $this->propagateFrom($successor->id, false);
                $completedPatterns = array_merge($completedPatterns, $nestedCompletions);
            }
            // Other element nodes become active listeners (handled by listener index)
        }

        return $completedPatterns;
    }

    /**
     * Reactivate a pattern's entry nodes so it can match again.
     *
     * @param  string  $patternName  Pattern name to reactivate
     */
    private function reactivatePattern(string $patternName): void
    {
        $entryNodes = $this->graph->patternEntryNodes[$patternName] ?? [];
        foreach ($entryNodes as $entryNodeId) {
            $node = $this->graph->getNode($entryNodeId);
            if ($node !== null) {
                $node->activate();
            }
        }
    }

    /**
     * Rebuild the listener index from current graph state.
     */
    private function rebuildListenerIndex(): void
    {
        $this->listenerIndex = [];

        foreach ($this->graph->getActiveListeners() as $node) {
            $key = $node->elementType ?? '';
            if ($key === '') {
                continue;
            }

            if (! isset($this->listenerIndex[$key])) {
                $this->listenerIndex[$key] = [];
            }
            $this->listenerIndex[$key][] = $node->id;
        }
    }

    /**
     * Get current state of the graph.
     *
     * @return array<string, mixed> State information
     */
    public function getState(): array
    {
        $state = [
            'time' => $this->currentTime,
            'activeListeners' => [],
            'patternStates' => [],
        ];

        foreach ($this->graph->getActiveListeners() as $node) {
            $state['activeListeners'][] = [
                'id' => $node->id,
                'pattern' => $node->patternName,
                'elementType' => $node->elementType,
            ];
        }

        // Group fired nodes by pattern
        foreach ($this->graph->getPatternNames() as $patternName) {
            $patternState = [
                'firedNodes' => [],
            ];

            foreach ($this->graph->getNodesByPattern($patternName) as $node) {
                if (count($node->timestamps) > 0) {
                    $patternState['firedNodes'][] = [
                        'id' => $node->id,
                        'elementType' => $node->elementType,
                        'timestamps' => $node->timestamps,
                    ];
                }
            }

            $state['patternStates'][$patternName] = $patternState;
        }

        return $state;
    }

    /**
     * Get current logical time.
     *
     * @return int Current time value
     */
    public function getCurrentTime(): int
    {
        return $this->currentTime;
    }

    /**
     * Get the underlying unified graph.
     *
     * @return UnifiedSequenceGraph The unified graph
     */
    public function getGraph(): UnifiedSequenceGraph
    {
        return $this->graph;
    }

    /**
     * Get all parse events recorded during processing.
     *
     * @return array<ParseEvent> Array of parse events
     */
    public function getParseEvents(): array
    {
        return $this->parseEvents;
    }

    /**
     * Clear all parse events.
     */
    public function clearParseEvents(): void
    {
        $this->parseEvents = [];
    }
}
