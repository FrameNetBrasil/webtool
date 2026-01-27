<?php

namespace App\Data\Parser\V5;

use App\Services\Parser\GhostNodeManager;

/**
 * Parse State V5
 *
 * Extended parse state for Parser V5 with:
 * - Type Graph (construction ontology)
 * - Token Graph (runtime parse state with ghosts)
 * - Ghost Node Manager
 * - Reconfiguration Log
 * - State Snapshots
 *
 * Extends V4 parse state with V5-specific features while maintaining
 * backward compatibility.
 */
class ParseStateV5
{
    /**
     * @param  int  $idParserGraph  Parse graph ID
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @param  string  $sentence  Input sentence
     * @param  array  $tokens  Tokenized input
     * @param  TypeGraph|null  $typeGraph  Construction ontology
     * @param  TokenGraph|null  $tokenGraph  Runtime graph state
     * @param  GhostNodeManager|null  $ghostManager  Ghost node manager
     * @param  array  $alternatives  Active alternatives
     * @param  array  $confirmedNodes  Confirmed parse nodes
     * @param  array  $confirmedEdges  Confirmed parse edges
     * @param  array  $reconfigurationLog  History of reconfiguration operations
     * @param  array  $stateSnapshots  Snapshots at each position
     * @param  int  $currentPosition  Current parsing position
     * @param  string  $status  Parse status (parsing, complete, failed)
     * @param  array  $metadata  Additional metadata
     */
    public function __construct(
        public int $idParserGraph,
        public int $idGrammarGraph,
        public string $sentence,
        public array $tokens = [],
        public ?TypeGraph $typeGraph = null,
        public ?TokenGraph $tokenGraph = null,
        public ?GhostNodeManager $ghostManager = null,
        public array $alternatives = [],
        public array $confirmedNodes = [],
        public array $confirmedEdges = [],
        public array $reconfigurationLog = [],
        public array $stateSnapshots = [],
        public int $currentPosition = 0,
        public string $status = 'parsing',
        public array $metadata = []
    ) {
        // Initialize components if not provided
        $this->tokenGraph = $tokenGraph ?? new TokenGraph;
        $this->ghostManager = $ghostManager ?? new GhostNodeManager;
    }

    /**
     * Create new parse state for a sentence
     */
    public static function create(
        int $idParserGraph,
        int $idGrammarGraph,
        string $sentence,
        array $tokens,
        ?TypeGraph $typeGraph = null
    ): self {
        return new self(
            idParserGraph: $idParserGraph,
            idGrammarGraph: $idGrammarGraph,
            sentence: $sentence,
            tokens: $tokens,
            typeGraph: $typeGraph
        );
    }

    /**
     * Add a reconfiguration operation to the log
     */
    public function logReconfiguration(ReconfigurationOperation $operation): void
    {
        $this->reconfigurationLog[] = $operation;
    }

    /**
     * Capture current state as snapshot
     */
    public function captureSnapshot(): array
    {
        $snapshot = [
            'position' => $this->currentPosition,
            'tokenData' => $this->tokens[$this->currentPosition] ?? null,
            'tokenGraph' => $this->tokenGraph->toArray(),
            'activeAlternatives' => count($this->alternatives),
            'ghostNodes' => $this->ghostManager->toArray(),
            'confirmedNodes' => count($this->confirmedNodes),
            'confirmedEdges' => count($this->confirmedEdges),
            'reconfigurations' => array_map(
                fn (ReconfigurationOperation $op) => $op->toArray(),
                array_slice($this->reconfigurationLog, -10)  // Last 10 operations
            ),
            'timestamp' => microtime(true),
        ];

        $this->stateSnapshots[$this->currentPosition] = $snapshot;

        return $snapshot;
    }

    /**
     * Advance to next position
     */
    public function advance(): void
    {
        $this->currentPosition++;
    }

    /**
     * Mark parse as complete
     */
    public function markComplete(): void
    {
        $this->status = 'complete';

        // Expire any remaining pending ghosts
        if ($this->ghostManager) {
            $this->ghostManager->expirePendingGhosts();
        }
    }

    /**
     * Mark parse as failed
     */
    public function markFailed(string $errorMessage): void
    {
        $this->status = 'failed';
        $this->metadata['errorMessage'] = $errorMessage;
    }

    /**
     * Check if parsing is complete
     */
    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    /**
     * Check if parsing failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if currently parsing
     */
    public function isParsing(): bool
    {
        return $this->status === 'parsing';
    }

    /**
     * Get total token count
     */
    public function getTokenCount(): int
    {
        return count($this->tokens);
    }

    /**
     * Get current token
     */
    public function getCurrentToken(): ?array
    {
        return $this->tokens[$this->currentPosition] ?? null;
    }

    /**
     * Check if at end of input
     */
    public function isAtEnd(): bool
    {
        return $this->currentPosition >= count($this->tokens);
    }

    /**
     * Get progress percentage
     */
    public function getProgress(): float
    {
        if (count($this->tokens) === 0) {
            return 100.0;
        }

        return ($this->currentPosition / count($this->tokens)) * 100.0;
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'tokenCount' => count($this->tokens),
            'currentPosition' => $this->currentPosition,
            'progress' => $this->getProgress(),
            'status' => $this->status,
            'alternatives' => count($this->alternatives),
            'confirmedNodes' => count($this->confirmedNodes),
            'confirmedEdges' => count($this->confirmedEdges),
            'reconfigurations' => count($this->reconfigurationLog),
            'snapshots' => count($this->stateSnapshots),
            'tokenGraph' => $this->tokenGraph?->getStatistics() ?? [],
            'ghosts' => $this->ghostManager?->getStatistics() ?? [],
        ];
    }

    /**
     * Get reconfigurations at a specific position
     */
    public function getReconfigurationsAtPosition(int $position): array
    {
        return array_filter(
            $this->reconfigurationLog,
            fn (ReconfigurationOperation $op) => $op->position === $position
        );
    }

    /**
     * Get reconfigurations by type
     */
    public function getReconfigurationsByType(string $type): array
    {
        return array_filter(
            $this->reconfigurationLog,
            fn (ReconfigurationOperation $op) => $op->operationType === $type
        );
    }

    /**
     * Get snapshot at position
     */
    public function getSnapshotAtPosition(int $position): ?array
    {
        return $this->stateSnapshots[$position] ?? null;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'idParserGraph' => $this->idParserGraph,
            'idGrammarGraph' => $this->idGrammarGraph,
            'sentence' => $this->sentence,
            'tokenCount' => count($this->tokens),
            'currentPosition' => $this->currentPosition,
            'status' => $this->status,
            'alternatives' => count($this->alternatives),
            'confirmedNodes' => count($this->confirmedNodes),
            'confirmedEdges' => count($this->confirmedEdges),
            'tokenGraph' => $this->tokenGraph?->toArray() ?? [],
            'ghosts' => $this->ghostManager?->toArray() ?? [],
            'reconfigurations' => count($this->reconfigurationLog),
            'snapshots' => count($this->stateSnapshots),
            'metadata' => $this->metadata,
            'statistics' => $this->getStatistics(),
        ];
    }

    /**
     * Load from array
     */
    public static function fromArray(array $data, ?TypeGraph $typeGraph = null): self
    {
        $state = new self(
            idParserGraph: $data['idParserGraph'],
            idGrammarGraph: $data['idGrammarGraph'],
            sentence: $data['sentence'],
            tokens: $data['tokens'] ?? [],
            typeGraph: $typeGraph,
            currentPosition: $data['currentPosition'] ?? 0,
            status: $data['status'] ?? 'parsing',
            metadata: $data['metadata'] ?? []
        );

        // Load token graph
        if (isset($data['tokenGraph']) && $state->tokenGraph) {
            $state->tokenGraph->loadFromArray($data['tokenGraph']);
        }

        // Load ghost manager
        if (isset($data['ghosts']) && $state->ghostManager) {
            $state->ghostManager->loadFromArray($data['ghosts']);
        }

        // Load reconfiguration log
        if (isset($data['reconfigurationLog'])) {
            $state->reconfigurationLog = array_map(
                fn (array $opData) => ReconfigurationOperation::fromArray($opData),
                $data['reconfigurationLog']
            );
        }

        // Load snapshots
        if (isset($data['stateSnapshots'])) {
            $state->stateSnapshots = $data['stateSnapshots'];
        }

        return $state;
    }

    /**
     * Clone for parallel alternative evaluation
     */
    public function clone(): self
    {
        return new self(
            idParserGraph: $this->idParserGraph,
            idGrammarGraph: $this->idGrammarGraph,
            sentence: $this->sentence,
            tokens: $this->tokens,
            typeGraph: $this->typeGraph,
            tokenGraph: clone $this->tokenGraph,
            ghostManager: clone $this->ghostManager,
            alternatives: $this->alternatives,
            confirmedNodes: $this->confirmedNodes,
            confirmedEdges: $this->confirmedEdges,
            reconfigurationLog: $this->reconfigurationLog,
            stateSnapshots: $this->stateSnapshots,
            currentPosition: $this->currentPosition,
            status: $this->status,
            metadata: $this->metadata
        );
    }
}
