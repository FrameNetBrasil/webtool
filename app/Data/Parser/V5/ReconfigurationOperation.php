<?php

namespace App\Data\Parser\V5;

/**
 * Reconfiguration Operation
 *
 * Records a single reconfiguration operation performed on the Token Graph.
 * Used for debugging, state snapshots, and understanding parse evolution.
 *
 * Operation Types:
 * - ghost_created: New ghost node created
 * - ghost_fulfilled: Ghost merged with real node
 * - nodes_merged: Two nodes merged
 * - edges_relinked: Edges redirected after merge
 * - alternative_reevaluated: Alternative reconsidered after change
 * - mwe_aggregated: MWE nodes consumed
 */
class ReconfigurationOperation
{
    /**
     * Operation type constants
     */
    public const TYPE_GHOST_CREATED = 'ghost_created';

    public const TYPE_GHOST_FULFILLED = 'ghost_fulfilled';

    public const TYPE_NODES_MERGED = 'nodes_merged';

    public const TYPE_EDGES_RELINKED = 'edges_relinked';

    public const TYPE_ALTERNATIVE_REEVALUATED = 'alternative_reevaluated';

    public const TYPE_MWE_AGGREGATED = 'mwe_aggregated';

    public const TYPE_CONSTRUCTION_COMPLETED = 'construction_completed';

    public const TYPE_ALTERNATIVE_ABANDONED = 'alternative_abandoned';

    /**
     * @param  string  $operationType  Type of operation
     * @param  int  $position  Sentence position where operation occurred
     * @param  array  $affectedNodes  Node IDs affected by this operation
     * @param  array  $affectedEdges  Edge IDs affected by this operation
     * @param  array  $affectedAlternatives  Alternative IDs affected
     * @param  array|null  $before  State before operation
     * @param  array|null  $after  State after operation
     * @param  string|null  $reason  Why this operation was performed
     * @param  array  $metadata  Additional operation-specific data
     * @param  float|null  $timestamp  When operation occurred
     */
    public function __construct(
        public string $operationType,
        public int $position = 0,
        public array $affectedNodes = [],
        public array $affectedEdges = [],
        public array $affectedAlternatives = [],
        public ?array $before = null,
        public ?array $after = null,
        public ?string $reason = null,
        public array $metadata = [],
        public ?float $timestamp = null
    ) {
        $this->timestamp = $timestamp ?? microtime(true);
    }

    /**
     * Create ghost creation operation
     */
    public static function ghostCreated(
        int $ghostId,
        int $position,
        int $alternativeId,
        int $constructionId,
        string $ghostType,
        ?string $expectedCE = null,
        array $metadata = []
    ): self {
        return new self(
            operationType: self::TYPE_GHOST_CREATED,
            position: $position,
            affectedNodes: [$ghostId],
            affectedAlternatives: [$alternativeId],
            reason: "Mandatory element missing: {$ghostType}",
            metadata: array_merge([
                'ghostType' => $ghostType,
                'constructionId' => $constructionId,
                'expectedCE' => $expectedCE,
            ], $metadata)
        );
    }

    /**
     * Create ghost fulfillment operation
     */
    public static function ghostFulfilled(
        int $ghostId,
        int $realNodeId,
        int $position,
        string $fulfillmentReason = 'Compatible node arrived',
        array $metadata = []
    ): self {
        return new self(
            operationType: self::TYPE_GHOST_FULFILLED,
            position: $position,
            affectedNodes: [$ghostId, $realNodeId],
            reason: $fulfillmentReason,
            metadata: array_merge([
                'ghostId' => $ghostId,
                'realNodeId' => $realNodeId,
            ], $metadata)
        );
    }

    /**
     * Create nodes merged operation
     */
    public static function nodesMerged(
        int $sourceNodeId,
        int $targetNodeId,
        int $position,
        array $mergedProperties = [],
        array $metadata = []
    ): self {
        return new self(
            operationType: self::TYPE_NODES_MERGED,
            position: $position,
            affectedNodes: [$sourceNodeId, $targetNodeId],
            reason: 'Nodes merged after ghost fulfillment',
            metadata: array_merge([
                'sourceNode' => $sourceNodeId,
                'targetNode' => $targetNodeId,
                'mergedProperties' => $mergedProperties,
            ], $metadata)
        );
    }

    /**
     * Create edges relinked operation
     */
    public static function edgesRelinked(
        array $edgeIds,
        int $fromNode,
        int $toNode,
        int $position,
        string $reason = 'Edges redirected after merge',
        array $metadata = []
    ): self {
        return new self(
            operationType: self::TYPE_EDGES_RELINKED,
            position: $position,
            affectedEdges: $edgeIds,
            affectedNodes: [$fromNode, $toNode],
            reason: $reason,
            metadata: array_merge([
                'fromNode' => $fromNode,
                'toNode' => $toNode,
                'edgeCount' => count($edgeIds),
            ], $metadata)
        );
    }

    /**
     * Create alternative reevaluated operation
     */
    public static function alternativeReevaluated(
        int $alternativeId,
        int $position,
        string $outcome,
        string $reason,
        array $metadata = []
    ): self {
        return new self(
            operationType: self::TYPE_ALTERNATIVE_REEVALUATED,
            position: $position,
            affectedAlternatives: [$alternativeId],
            reason: $reason,
            metadata: array_merge([
                'alternativeId' => $alternativeId,
                'outcome' => $outcome,
            ], $metadata)
        );
    }

    /**
     * Create MWE aggregation operation
     */
    public static function mweAggregated(
        array $consumedNodeIds,
        int $aggregateNodeId,
        int $position,
        string $mweName,
        array $metadata = []
    ): self {
        return new self(
            operationType: self::TYPE_MWE_AGGREGATED,
            position: $position,
            affectedNodes: array_merge($consumedNodeIds, [$aggregateNodeId]),
            reason: "MWE '{$mweName}' aggregated",
            metadata: array_merge([
                'consumedNodes' => $consumedNodeIds,
                'aggregateNode' => $aggregateNodeId,
                'mweName' => $mweName,
            ], $metadata)
        );
    }

    /**
     * Create construction completed operation
     */
    public static function constructionCompleted(
        int $alternativeId,
        int $constructionId,
        int $position,
        array $nodeIds,
        array $metadata = []
    ): self {
        return new self(
            operationType: self::TYPE_CONSTRUCTION_COMPLETED,
            position: $position,
            affectedNodes: $nodeIds,
            affectedAlternatives: [$alternativeId],
            reason: 'Construction pattern fully matched',
            metadata: array_merge([
                'alternativeId' => $alternativeId,
                'constructionId' => $constructionId,
            ], $metadata)
        );
    }

    /**
     * Create alternative abandoned operation
     */
    public static function alternativeAbandoned(
        int $alternativeId,
        int $position,
        string $reason,
        array $metadata = []
    ): self {
        return new self(
            operationType: self::TYPE_ALTERNATIVE_ABANDONED,
            position: $position,
            affectedAlternatives: [$alternativeId],
            reason: $reason,
            metadata: array_merge([
                'alternativeId' => $alternativeId,
            ], $metadata)
        );
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'operationType' => $this->operationType,
            'position' => $this->position,
            'affectedNodes' => $this->affectedNodes,
            'affectedEdges' => $this->affectedEdges,
            'affectedAlternatives' => $this->affectedAlternatives,
            'before' => $this->before,
            'after' => $this->after,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            operationType: $data['operationType'],
            position: $data['position'] ?? 0,
            affectedNodes: $data['affectedNodes'] ?? [],
            affectedEdges: $data['affectedEdges'] ?? [],
            affectedAlternatives: $data['affectedAlternatives'] ?? [],
            before: $data['before'] ?? null,
            after: $data['after'] ?? null,
            reason: $data['reason'] ?? null,
            metadata: $data['metadata'] ?? [],
            timestamp: $data['timestamp'] ?? null
        );
    }

    /**
     * Get a human-readable description
     */
    public function getDescription(): string
    {
        $desc = match ($this->operationType) {
            self::TYPE_GHOST_CREATED => "Ghost created: {$this->metadata['ghostType']}",
            self::TYPE_GHOST_FULFILLED => 'Ghost fulfilled by real node',
            self::TYPE_NODES_MERGED => 'Nodes merged',
            self::TYPE_EDGES_RELINKED => count($this->affectedEdges).' edges relinked',
            self::TYPE_ALTERNATIVE_REEVALUATED => "Alternative {$this->metadata['outcome']}",
            self::TYPE_MWE_AGGREGATED => "MWE '{$this->metadata['mweName']}' aggregated",
            self::TYPE_CONSTRUCTION_COMPLETED => 'Construction completed',
            self::TYPE_ALTERNATIVE_ABANDONED => 'Alternative abandoned',
            default => $this->operationType,
        };

        if ($this->reason) {
            $desc .= " - {$this->reason}";
        }

        return $desc;
    }

    /**
     * Check if operation affects a specific node
     */
    public function affectsNode(int $nodeId): bool
    {
        return in_array($nodeId, $this->affectedNodes);
    }

    /**
     * Check if operation affects a specific edge
     */
    public function affectsEdge(int $edgeId): bool
    {
        return in_array($edgeId, $this->affectedEdges);
    }

    /**
     * Check if operation affects a specific alternative
     */
    public function affectsAlternative(int $alternativeId): bool
    {
        return in_array($alternativeId, $this->affectedAlternatives);
    }

    /**
     * Get elapsed time since operation
     */
    public function getElapsedTime(?float $currentTime = null): float
    {
        $currentTime = $currentTime ?? microtime(true);

        return $currentTime - $this->timestamp;
    }
}
