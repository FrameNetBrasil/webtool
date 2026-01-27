<?php

namespace App\Data\Parser\V5;

use Spatie\LaravelData\Data;

/**
 * Type Graph Node
 *
 * Represents a node in the Type Graph - either a Construction or CE Label.
 *
 * The Type Graph is a unified ontology connecting all constructions,
 * CE labels, and their relationships.
 */
class TypeGraphNode extends Data
{
    public function __construct(
        /** Unique node ID */
        public string $id,

        /** Node type: 'construction' or 'ce_label' */
        public string $type,

        /** Construction name or CE label */
        public string $name,

        /** Construction ID (NULL for CE labels) */
        public ?int $constructionId = null,

        /** Construction type: 'mwe', 'phrasal', 'clausal', 'sentential' (NULL for CE labels) */
        public ?string $constructionType = null,

        /** Construction priority (NULL for CE labels) */
        public ?int $priority = null,

        /** Additional node properties */
        public array $metadata = [],
    ) {}

    /**
     * Create a construction node
     */
    public static function construction(
        int $constructionId,
        string $name,
        string $constructionType,
        int $priority,
        array $metadata = []
    ): self {
        return new self(
            id: "construction_{$constructionId}",
            type: 'construction',
            name: $name,
            constructionId: $constructionId,
            constructionType: $constructionType,
            priority: $priority,
            metadata: $metadata
        );
    }

    /**
     * Create a CE label node
     */
    public static function ceLabel(
        string $label,
        string $level, // 'phrasal', 'clausal', 'sentential'
        array $metadata = []
    ): self {
        return new self(
            id: "ce_{$level}_{$label}",
            type: 'ce_label',
            name: $label,
            metadata: array_merge(['level' => $level], $metadata)
        );
    }

    /**
     * Check if this node is a construction
     */
    public function isConstruction(): bool
    {
        return $this->type === 'construction';
    }

    /**
     * Check if this node is a CE label
     */
    public function isCELabel(): bool
    {
        return $this->type === 'ce_label';
    }

    /**
     * Get CE level (phrasal, clausal, sentential) for CE label nodes
     */
    public function getCELevel(): ?string
    {
        return $this->metadata['level'] ?? null;
    }
}
