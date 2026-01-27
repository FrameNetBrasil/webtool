<?php

namespace App\Services\SeqGraph;

use App\Models\SeqGraph\ActivationResult;
use App\Models\SeqGraph\SeqNode;
use App\Models\SeqGraph\SequenceGraph;

/**
 * Core engine for activating sequence graphs with input elements.
 *
 * Manages multiple sequence graphs, maintains a listener index for
 * efficient matching, and propagates activation through graphs as
 * elements arrive. Tracks timestamps and handles nested pattern
 * completion.
 */
class ActivationEngine
{
    /**
     * All registered sequence graphs, indexed by pattern name.
     *
     * @var array<string, SequenceGraph>
     */
    private array $graphs = [];

    /**
     * Listener index mapping element types to active nodes.
     *
     * Format: ['elementType' => [['pattern' => name, 'nodeId' => id], ...]]
     * Provides O(1) lookup of which nodes are waiting for each element type.
     *
     * @var array<string, array<array{pattern: string, nodeId: string}>>
     */
    private array $listenerIndex = [];

    /**
     * Current logical time (increments with each input).
     */
    private int $currentTime = 0;

    /**
     * Register a sequence graph with the engine.
     *
     * @param  SequenceGraph  $graph  Graph to register
     */
    public function registerGraph(SequenceGraph $graph): void
    {
        $this->graphs[$graph->patternName] = $graph;
    }

    /**
     * Initialize all graphs by activating their start nodes.
     *
     * Sets up initial listening state so graphs are ready to receive input.
     */
    public function initialize(): void
    {
        foreach ($this->graphs as $graph) {
            $startNode = $graph->getNode($graph->startId);
            if ($startNode !== null) {
                $startNode->activate();
                $this->propagateFrom($graph, $startNode->id);
            }
        }
        $this->rebuildListenerIndex();
    }

    /**
     * Process an input element through all active listeners.
     *
     * Finds matching active nodes, fires them with timestamp, propagates
     * activation to successors, and handles pattern completions.
     *
     * @param  string  $elementType  Type of input element (e.g., 'NOUN')
     * @param  string  $elementValue  Value of input element (e.g., 'cat')
     * @return ActivationResult Result with fired nodes, completions, new listeners
     */
    public function processInput(string $elementType, string $elementValue): ActivationResult
    {
        $this->currentTime++;
        $firedNodes = [];
        $completedPatterns = [];
        $newListeners = [];

        // Find all active nodes listening for this element type
        $listeners = $this->listenerIndex[$elementType] ?? [];

        foreach ($listeners as $listener) {
            $graph = $this->graphs[$listener['pattern']];
            $node = $graph->getNode($listener['nodeId']);

            if ($node === null || ! $node->active) {
                continue;
            }

            // Check if element value matches (if specified)
            if ($node->elementValue !== null && $node->elementValue !== $elementValue) {
                continue;
            }

            // Fire the node
            $node->fire($this->currentTime);
            $firedNodes[] = [$listener['pattern'], $listener['nodeId']];

            // Deactivate and propagate
            $node->deactivate();
            $this->propagateFrom($graph, $node->id);
        }

        // Rebuild listener index after propagation
        $this->rebuildListenerIndex();

        // Check for completed patterns (end nodes that fired during this input)
        foreach ($this->graphs as $patternName => $graph) {
            $endNode = $graph->getNode($graph->endId);
            if ($endNode !== null && count($endNode->timestamps) > 0) {
                $lastTimestamp = end($endNode->timestamps);
                if ($lastTimestamp === $this->currentTime) {
                    // Pattern completed during this input - record it and handle as element
                    $completedPatterns[] = $patternName;

                    // Reactivate the pattern so it can match again (without clearing history)
                    $startNode = $graph->getNode($graph->startId);
                    if ($startNode !== null) {
                        $startNode->activate();
                        $this->propagateFrom($graph, $startNode->id);
                    }

                    // Process completion as an element for nested patterns
                    $nestedResult = $this->processInput($patternName, $patternName);

                    // Merge nested results
                    $firedNodes = array_merge($firedNodes, $nestedResult->firedNodes);
                    $completedPatterns = array_merge($completedPatterns, $nestedResult->completedPatterns);
                }
            }
        }

        // Collect new listeners
        foreach ($this->graphs as $patternName => $graph) {
            foreach ($graph->getActiveListeners() as $node) {
                $newListeners[] = [$patternName, $node->id];
            }
        }

        return new ActivationResult($firedNodes, $completedPatterns, $newListeners);
    }

    /**
     * Get current state of all graphs.
     *
     * Returns detailed state including active listeners and node timestamps.
     *
     * @return array<string, mixed> State information
     */
    public function getState(): array
    {
        $state = [
            'time' => $this->currentTime,
            'graphs' => [],
        ];

        foreach ($this->graphs as $patternName => $graph) {
            $graphState = [
                'pattern' => $patternName,
                'activeListeners' => [],
                'nodes' => [],
            ];

            foreach ($graph->getActiveListeners() as $node) {
                $graphState['activeListeners'][] = [
                    'id' => $node->id,
                    'elementType' => $node->elementType,
                ];
            }

            foreach ($graph->getElementNodes() as $node) {
                if (count($node->timestamps) > 0) {
                    $graphState['nodes'][] = [
                        'id' => $node->id,
                        'elementType' => $node->elementType,
                        'timestamps' => $node->timestamps,
                    ];
                }
            }

            $state['graphs'][$patternName] = $graphState;
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
     * Propagate activation from a node to its successors.
     *
     * Routing nodes propagate immediately. Element nodes become active listeners.
     *
     * @param  SequenceGraph  $graph  Graph containing the node
     * @param  string  $nodeId  ID of node to propagate from
     */
    private function propagateFrom(SequenceGraph $graph, string $nodeId): void
    {
        $successors = $graph->getSuccessors($nodeId);

        foreach ($successors as $successor) {
            $successor->activate();

            // Routing nodes (start/end) propagate immediately
            if ($successor->isRouting()) {
                $successor->fire($this->currentTime);
                $this->propagateFrom($graph, $successor->id);
            }
            // Element nodes become active listeners (handled by listener index)
        }
    }

    /**
     * Rebuild the listener index from current graph state.
     *
     * Scans all graphs for active element nodes and indexes them
     * by element type for fast lookup.
     */
    private function rebuildListenerIndex(): void
    {
        $this->listenerIndex = [];

        foreach ($this->graphs as $patternName => $graph) {
            foreach ($graph->getActiveListeners() as $node) {
                $key = $this->makeListenerKey($node);
                if (! isset($this->listenerIndex[$key])) {
                    $this->listenerIndex[$key] = [];
                }
                $this->listenerIndex[$key][] = [
                    'pattern' => $patternName,
                    'nodeId' => $node->id,
                ];
            }
        }
    }

    /**
     * Make a listener index key for a node.
     *
     * Uses element type as the key for indexing.
     *
     * @param  SeqNode  $node  Node to create key for
     * @return string Index key
     */
    private function makeListenerKey(SeqNode $node): string
    {
        return $node->elementType ?? '';
    }
}
