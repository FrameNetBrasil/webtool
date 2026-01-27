<?php

namespace App\Models\CLN_RNT;

/**
 * PC Parser Graph Node
 *
 * Represents a node in the parser graph created during Predictive Coding parsing.
 * Nodes can be:
 * - TOKEN: Input tokens from the sequence (word/POS pairs)
 * - CONSTRUCTION: Recognized construction patterns
 *
 * Node states:
 * - ACTIVE: Node has received direct input or was activated from END node
 * - WAITING: Node was predicted but not yet activated
 * - COMPLETED: Node successfully led to construction completion
 *
 * Note: The position field indicates where the node was created in the sequence
 * (for visualization purposes), but waiting nodes can match tokens/constructions
 * appearing at ANY position in the sequence.
 */
class PCParserGraphNode
{
    /**
     * Unique identifier
     */
    public string $id;

    /**
     * Position where node was created/appears (for visualization)
     */
    public int $position;

    /**
     * Node status: 'active', 'waiting', 'completed'
     */
    public string $status;

    /**
     * Node type: 'token', 'construction'
     */
    public string $nodeType;

    /**
     * Node value: "POS/word" for tokens, construction name for constructions
     */
    public string $value;

    /**
     * Reference to pattern graph node ID (if matched)
     */
    public ?int $patternNodeId;

    /**
     * How the node was created: 'input', 'end_node', 'waiting_activated'
     */
    public ?string $createdFrom;

    /**
     * Whether this node's predictions have been confirmed
     */
    public bool $confirmed;

    /**
     * Positions where this node is being actively used/reused
     * Used to track temporal locality for reuse rules
     */
    public array $usagePositions;

    /**
     * Additional metadata
     */
    public array $metadata;

    /**
     * Create a new PC parser graph node
     *
     * @param  array  $data  Node data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->position = $data['position'];
        $this->status = $data['status'] ?? 'active';
        $this->nodeType = $data['nodeType'];
        $this->value = $data['value'];
        $this->patternNodeId = $data['patternNodeId'] ?? null;
        $this->createdFrom = $data['createdFrom'] ?? null;
        $this->confirmed = $data['confirmed'] ?? false;
        $this->usagePositions = $data['usagePositions'] ?? [$data['position']]; // Initially used at creation position
        $this->metadata = $data['metadata'] ?? [];
    }

    /**
     * Activate this node (transition from waiting to active)
     */
    public function activate(): void
    {
        $this->status = 'active';
        if ($this->createdFrom === null) {
            $this->createdFrom = 'waiting_activated';
        }
    }

    /**
     * Mark this node as completed
     */
    public function complete(): void
    {
        $this->status = 'completed';
    }

    /**
     * Check if node is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if node is waiting
     */
    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    /**
     * Check if node is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if node is a token
     */
    public function isToken(): bool
    {
        return $this->nodeType === 'token';
    }

    /**
     * Check if node is a construction
     */
    public function isConstruction(): bool
    {
        return $this->nodeType === 'construction';
    }

    /**
     * Check if node's predictions have been confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    /**
     * Mark this node as confirmed (its predictions were validated)
     */
    public function confirm(): void
    {
        $this->confirmed = true;
    }

    /**
     * Add a position where this node is being used/reused
     */
    public function addUsagePosition(int $position): void
    {
        if (! in_array($position, $this->usagePositions)) {
            $this->usagePositions[] = $position;
        }
    }

    /**
     * Check if this node is being used at a specific position
     */
    public function isUsedAtPosition(int $position): bool
    {
        return in_array($position, $this->usagePositions);
    }

    /**
     * Get the highest position where this node is being used
     */
    public function getMaxUsagePosition(): int
    {
        return empty($this->usagePositions) ? $this->position : max($this->usagePositions);
    }

    /**
     * Convert node to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'status' => $this->status,
            'nodeType' => $this->nodeType,
            'value' => $this->value,
            'patternNodeId' => $this->patternNodeId,
            'createdFrom' => $this->createdFrom,
            'confirmed' => $this->confirmed,
            'usagePositions' => $this->usagePositions,
            'metadata' => $this->metadata,
        ];
    }
}
