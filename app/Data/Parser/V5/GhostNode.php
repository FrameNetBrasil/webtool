<?php

namespace App\Data\Parser\V5;

/**
 * GhostNode Data Structure
 *
 * Represents a mandatory-but-implicit element in Parser V5.
 * Ghost nodes support null instantiation phenomena like dropped subjects,
 * implicit heads, PRO elements, and ellipsis.
 *
 * Lifecycle: CREATED → PENDING → (FULFILLED | EXPIRED)
 */
class GhostNode
{
    /**
     * Ghost node states
     */
    public const STATE_CREATED = 'created';

    public const STATE_PENDING = 'pending';

    public const STATE_FULFILLED = 'fulfilled';

    public const STATE_EXPIRED = 'expired';

    /**
     * Common ghost types
     */
    public const TYPE_IMPLICIT_HEAD = 'implicit_head';

    public const TYPE_SUBJECT_PRO = 'subject_pro';

    public const TYPE_DROPPED_ARGUMENT = 'dropped_argument';

    public const TYPE_IMPLICIT_COPULA = 'implicit_copula';

    public const TYPE_ELIDED_ELEMENT = 'elided_element';

    /**
     * @param  int  $id  Ghost node ID (negative for unfulfilled ghosts)
     * @param  string  $ghostType  Type of ghost (implicit_head, subject_pro, etc.)
     * @param  string  $state  Current lifecycle state
     * @param  int  $createdAtPosition  Sentence position where ghost was created
     * @param  int  $createdByAlternative  Alternative ID that created this ghost
     * @param  int  $createdByConstruction  Construction ID requiring this element
     * @param  string|null  $expectedCE  Expected CE label
     * @param  string|null  $expectedPOS  Expected POS tag
     * @param  array|null  $expectedFeatures  Expected morphological features
     * @param  int|null  $fulfilledBy  ID of real node that fulfilled this ghost
     * @param  int|null  $fulfilledAtPosition  Position where ghost was fulfilled
     * @param  array  $metadata  Additional ghost properties
     */
    public function __construct(
        public int $id,
        public string $ghostType,
        public string $state = self::STATE_CREATED,
        public int $createdAtPosition = 0,
        public int $createdByAlternative = 0,
        public int $createdByConstruction = 0,
        public ?string $expectedCE = null,
        public ?string $expectedPOS = null,
        public ?array $expectedFeatures = null,
        public ?int $fulfilledBy = null,
        public ?int $fulfilledAtPosition = null,
        public array $metadata = []
    ) {}

    /**
     * Check if this ghost can be fulfilled by a real node
     */
    public function canBeFulfilledBy(array $realNode): bool
    {
        // Already fulfilled
        if ($this->state === self::STATE_FULFILLED) {
            return false;
        }

        // Already expired
        if ($this->state === self::STATE_EXPIRED) {
            return false;
        }

        // Check POS compatibility if expected POS is set
        if ($this->expectedPOS !== null) {
            if (! isset($realNode['udpos']) || $realNode['udpos'] !== $this->expectedPOS) {
                return false;
            }
        }

        // Check feature compatibility if expected features are set
        if ($this->expectedFeatures !== null && ! empty($this->expectedFeatures)) {
            if (! $this->featuresMatch($realNode['features'] ?? [], $this->expectedFeatures)) {
                return false;
            }
        }

        // Check CE compatibility if expected CE is set
        if ($this->expectedCE !== null) {
            // Check multi-level CEs
            $phrasalCE = $realNode['phrasalCE'] ?? null;
            $clausalCE = $realNode['clausalCE'] ?? null;
            $sententialCE = $realNode['sententialCE'] ?? null;
            $simpleCE = $realNode['ce'] ?? null;

            $hasMatchingCE = ($phrasalCE === $this->expectedCE)
                || ($clausalCE === $this->expectedCE)
                || ($sententialCE === $this->expectedCE)
                || ($simpleCE === $this->expectedCE);

            if (! $hasMatchingCE) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fulfill this ghost with a real node
     */
    public function fulfill(int $realNodeId, int $position): void
    {
        if ($this->state === self::STATE_FULFILLED) {
            throw new \RuntimeException("Ghost node {$this->id} is already fulfilled");
        }

        if ($this->state === self::STATE_EXPIRED) {
            throw new \RuntimeException("Ghost node {$this->id} has expired and cannot be fulfilled");
        }

        $this->state = self::STATE_FULFILLED;
        $this->fulfilledBy = $realNodeId;
        $this->fulfilledAtPosition = $position;
    }

    /**
     * Mark this ghost as expired (sentence ended, no fulfillment)
     */
    public function expire(): void
    {
        if ($this->state === self::STATE_FULFILLED) {
            throw new \RuntimeException("Ghost node {$this->id} is already fulfilled and cannot expire");
        }

        $this->state = self::STATE_EXPIRED;
    }

    /**
     * Check if ghost is fulfilled
     */
    public function isFulfilled(): bool
    {
        return $this->state === self::STATE_FULFILLED;
    }

    /**
     * Check if ghost is expired
     */
    public function isExpired(): bool
    {
        return $this->state === self::STATE_EXPIRED;
    }

    /**
     * Check if ghost is still pending
     */
    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    /**
     * Check if ghost is unfulfilled (pending or expired)
     */
    public function isUnfulfilled(): bool
    {
        return ! $this->isFulfilled();
    }

    /**
     * Check if morphological features match
     */
    private function featuresMatch(array $actualFeatures, array $expectedFeatures): bool
    {
        foreach ($expectedFeatures as $feature => $expectedValue) {
            if (! isset($actualFeatures[$feature])) {
                return false;
            }

            $actualValue = $actualFeatures[$feature];

            // Handle array values (multi-valued features)
            if (is_array($expectedValue)) {
                if (! in_array($actualValue, $expectedValue)) {
                    return false;
                }
            } else {
                if ($actualValue !== $expectedValue) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Convert ghost node to array representation
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ghostType' => $this->ghostType,
            'state' => $this->state,
            'createdAtPosition' => $this->createdAtPosition,
            'createdByAlternative' => $this->createdByAlternative,
            'createdByConstruction' => $this->createdByConstruction,
            'expectedCE' => $this->expectedCE,
            'expectedPOS' => $this->expectedPOS,
            'expectedFeatures' => $this->expectedFeatures,
            'fulfilledBy' => $this->fulfilledBy,
            'fulfilledAtPosition' => $this->fulfilledAtPosition,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create ghost node from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            ghostType: $data['ghostType'],
            state: $data['state'] ?? self::STATE_CREATED,
            createdAtPosition: $data['createdAtPosition'] ?? 0,
            createdByAlternative: $data['createdByAlternative'] ?? 0,
            createdByConstruction: $data['createdByConstruction'] ?? 0,
            expectedCE: $data['expectedCE'] ?? null,
            expectedPOS: $data['expectedPOS'] ?? null,
            expectedFeatures: $data['expectedFeatures'] ?? null,
            fulfilledBy: $data['fulfilledBy'] ?? null,
            fulfilledAtPosition: $data['fulfilledAtPosition'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Create a new ghost node
     */
    public static function create(
        int $id,
        string $ghostType,
        int $position,
        int $alternativeId,
        int $constructionId,
        ?string $expectedCE = null,
        ?string $expectedPOS = null,
        ?array $expectedFeatures = null,
        array $metadata = []
    ): self {
        return new self(
            id: $id,
            ghostType: $ghostType,
            state: self::STATE_PENDING,
            createdAtPosition: $position,
            createdByAlternative: $alternativeId,
            createdByConstruction: $constructionId,
            expectedCE: $expectedCE,
            expectedPOS: $expectedPOS,
            expectedFeatures: $expectedFeatures,
            metadata: $metadata
        );
    }

    /**
     * Get a human-readable label for this ghost
     */
    public function getLabel(): string
    {
        $label = 'GHOST_';

        if ($this->expectedCE) {
            $label .= strtoupper($this->expectedCE);
        } elseif ($this->expectedPOS) {
            $label .= strtoupper($this->expectedPOS);
        } else {
            $label .= strtoupper($this->ghostType);
        }

        return $label;
    }

    /**
     * Get description of what this ghost represents
     */
    public function getDescription(): string
    {
        $parts = [];

        $parts[] = "Ghost {$this->ghostType}";

        if ($this->expectedCE) {
            $parts[] = "CE: {$this->expectedCE}";
        }

        if ($this->expectedPOS) {
            $parts[] = "POS: {$this->expectedPOS}";
        }

        if ($this->state === self::STATE_FULFILLED) {
            $parts[] = "fulfilled by node {$this->fulfilledBy} at position {$this->fulfilledAtPosition}";
        } elseif ($this->state === self::STATE_EXPIRED) {
            $parts[] = 'expired (unfulfilled)';
        } else {
            $parts[] = 'pending fulfillment';
        }

        return implode(', ', $parts);
    }
}
