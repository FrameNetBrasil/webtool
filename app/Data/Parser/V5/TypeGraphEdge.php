<?php

namespace App\Data\Parser\V5;

use Spatie\LaravelData\Data;

/**
 * Type Graph Edge
 *
 * Represents a relationship between two nodes in the Type Graph.
 *
 * Relationship types:
 * - 'produces': Construction → CE label (e.g., HEAD_NOUN produces Head)
 * - 'requires': Construction → Construction (e.g., ARG_NP requires HEAD_NOUN)
 * - 'inherits': Construction → Construction (e.g., PRED_SIMPLE inherits from PRED)
 * - 'conflicts_with': Construction → Construction (mutually exclusive)
 */
class TypeGraphEdge extends Data
{
    public function __construct(
        /** Unique edge ID */
        public string $id,

        /** Source node ID */
        public string $fromNodeId,

        /** Target node ID */
        public string $toNodeId,

        /** Relationship type */
        public string $relationshipType,

        /** Is this relationship mandatory? */
        public bool $mandatory = false,

        /** Additional edge properties */
        public array $metadata = [],
    ) {}

    /**
     * Create a 'produces' edge (Construction → CE label)
     */
    public static function produces(
        string $fromNodeId,
        string $toNodeId,
        bool $mandatory = false,
        array $metadata = []
    ): self {
        return new self(
            id: "produces_{$fromNodeId}_to_{$toNodeId}",
            fromNodeId: $fromNodeId,
            toNodeId: $toNodeId,
            relationshipType: 'produces',
            mandatory: $mandatory,
            metadata: $metadata
        );
    }

    /**
     * Create a 'requires' edge (Construction → Construction or CE)
     */
    public static function requires(
        string $fromNodeId,
        string $toNodeId,
        bool $mandatory = false,
        array $metadata = []
    ): self {
        return new self(
            id: "requires_{$fromNodeId}_to_{$toNodeId}",
            fromNodeId: $fromNodeId,
            toNodeId: $toNodeId,
            relationshipType: 'requires',
            mandatory: $mandatory,
            metadata: $metadata
        );
    }

    /**
     * Create an 'inherits' edge (Construction → Construction)
     */
    public static function inherits(
        string $fromNodeId,
        string $toNodeId,
        array $metadata = []
    ): self {
        return new self(
            id: "inherits_{$fromNodeId}_to_{$toNodeId}",
            fromNodeId: $fromNodeId,
            toNodeId: $toNodeId,
            relationshipType: 'inherits',
            mandatory: false,
            metadata: $metadata
        );
    }

    /**
     * Create a 'conflicts_with' edge (Construction → Construction)
     */
    public static function conflictsWith(
        string $fromNodeId,
        string $toNodeId,
        array $metadata = []
    ): self {
        return new self(
            id: "conflicts_{$fromNodeId}_with_{$toNodeId}",
            fromNodeId: $fromNodeId,
            toNodeId: $toNodeId,
            relationshipType: 'conflicts_with',
            mandatory: false,
            metadata: $metadata
        );
    }

    /**
     * Check relationship type
     */
    public function isProduces(): bool
    {
        return $this->relationshipType === 'produces';
    }

    public function isRequires(): bool
    {
        return $this->relationshipType === 'requires';
    }

    public function isInherits(): bool
    {
        return $this->relationshipType === 'inherits';
    }

    public function isConflictsWith(): bool
    {
        return $this->relationshipType === 'conflicts_with';
    }
}
