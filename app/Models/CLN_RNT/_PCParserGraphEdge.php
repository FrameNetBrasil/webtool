<?php

namespace App\Models\CLN_RNT;

/**
 * PC Parser Graph Edge
 *
 * Represents an edge (connection) between nodes in the parser graph.
 *
 * Edge types:
 * - MATCH: Direct matching edge (token matches pattern node)
 * - PREDICTION: Edge to waiting node (prediction of what might come next)
 * - COMPLETION: Edge from construction completion (pattern completed to END)
 */
class PCParserGraphEdge
{
    /**
     * Unique identifier
     */
    public string $id;

    /**
     * Source node ID
     */
    public string $fromNodeId;

    /**
     * Target node ID
     */
    public string $toNodeId;

    /**
     * Edge label (from pattern graph)
     */
    public string $label;

    /**
     * Edge type: 'match', 'prediction', 'completion'
     */
    public string $edgeType;

    /**
     * Edge status: 'expected', 'confirmed' (for prediction edges)
     */
    public ?string $status;

    /**
     * Pattern ID (which construction pattern this edge belongs to)
     */
    public ?int $patternId;

    /**
     * Additional metadata
     */
    public array $metadata;

    /**
     * Create a new PC parser graph edge
     *
     * @param  array  $data  Edge data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->fromNodeId = $data['fromNodeId'];
        $this->toNodeId = $data['toNodeId'];
        $this->label = $data['label'];
        $this->edgeType = $data['edgeType'];
        $this->status = $data['status'] ?? null;
        $this->patternId = $data['patternId'] ?? null;
        $this->metadata = $data['metadata'] ?? [];
    }

    /**
     * Check if edge is a match type
     */
    public function isMatch(): bool
    {
        return $this->edgeType === 'match';
    }

    /**
     * Check if edge is a prediction type
     */
    public function isPrediction(): bool
    {
        return $this->edgeType === 'prediction';
    }

    /**
     * Check if edge is a completion type
     */
    public function isCompletion(): bool
    {
        return $this->edgeType === 'completion';
    }

    /**
     * Check if edge status is expected
     */
    public function isExpected(): bool
    {
        return $this->status === 'expected';
    }

    /**
     * Check if edge status is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Confirm this edge (change status from expected to confirmed)
     */
    public function confirm(): void
    {
        $this->status = 'confirmed';
    }

    /**
     * Convert edge to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fromNodeId' => $this->fromNodeId,
            'toNodeId' => $this->toNodeId,
            'label' => $this->label,
            'edgeType' => $this->edgeType,
            'status' => $this->status,
            'patternId' => $this->patternId,
            'metadata' => $this->metadata,
        ];
    }
}
