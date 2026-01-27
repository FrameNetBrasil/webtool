<?php

namespace App\Services\Parser;

use App\Data\Parser\V5\GhostNode;

/**
 * Ghost Node Manager
 *
 * Manages the lifecycle of ghost nodes in Parser V5.
 * Handles creation, fulfillment, expiration, and querying of ghost nodes.
 *
 * Responsibilities:
 * - Create ghost nodes for missing mandatory elements
 * - Check if real nodes can fulfill ghosts
 * - Fulfill ghosts when matching nodes arrive
 * - Expire stale ghosts at sentence boundaries
 * - Track unfulfilled ghosts for reporting
 */
class GhostNodeManager
{
    /**
     * Registry of ghost nodes (nodeId => GhostNode)
     *
     * @var array<int, GhostNode>
     */
    private array $ghosts = [];

    /**
     * Counter for generating ghost node IDs (negative numbers)
     */
    private int $ghostIdCounter = -1;

    /**
     * Create a new ghost node
     *
     * @param  string  $ghostType  Type of ghost (implicit_head, subject_pro, etc.)
     * @param  int  $position  Sentence position where ghost is needed
     * @param  int  $alternativeId  Alternative ID requiring this ghost
     * @param  int  $constructionId  Construction ID requiring this element
     * @param  string|null  $expectedCE  Expected CE label
     * @param  string|null  $expectedPOS  Expected POS tag
     * @param  array|null  $expectedFeatures  Expected morphological features
     * @param  array  $metadata  Additional metadata
     * @return GhostNode The created ghost node
     */
    public function createGhost(
        string $ghostType,
        int $position,
        int $alternativeId,
        int $constructionId,
        ?string $expectedCE = null,
        ?string $expectedPOS = null,
        ?array $expectedFeatures = null,
        array $metadata = []
    ): GhostNode {
        $ghostId = $this->generateGhostId();

        $ghost = GhostNode::create(
            id: $ghostId,
            ghostType: $ghostType,
            position: $position,
            alternativeId: $alternativeId,
            constructionId: $constructionId,
            expectedCE: $expectedCE,
            expectedPOS: $expectedPOS,
            expectedFeatures: $expectedFeatures,
            metadata: $metadata
        );

        $this->ghosts[$ghostId] = $ghost;

        return $ghost;
    }

    /**
     * Check if a real node can fulfill any pending ghost
     *
     * @param  array  $realNode  Real node data
     * @return GhostNode|null The ghost that can be fulfilled, or null
     */
    public function findFulfillableGhost(array $realNode): ?GhostNode
    {
        foreach ($this->ghosts as $ghost) {
            if ($ghost->isPending() && $ghost->canBeFulfilledBy($realNode)) {
                return $ghost;
            }
        }

        return null;
    }

    /**
     * Check if a specific real node can fulfill a specific ghost
     *
     * @param  int  $ghostId  Ghost node ID
     * @param  array  $realNode  Real node data
     * @return bool True if ghost can be fulfilled by this node
     */
    public function canFulfillGhost(int $ghostId, array $realNode): bool
    {
        if (! isset($this->ghosts[$ghostId])) {
            return false;
        }

        $ghost = $this->ghosts[$ghostId];

        return $ghost->canBeFulfilledBy($realNode);
    }

    /**
     * Fulfill a ghost with a real node
     *
     * @param  int  $ghostId  Ghost node ID
     * @param  int  $realNodeId  Real node ID
     * @param  int  $position  Position where fulfillment occurred
     * @return bool True if fulfillment succeeded
     */
    public function fulfillGhost(int $ghostId, int $realNodeId, int $position): bool
    {
        if (! isset($this->ghosts[$ghostId])) {
            return false;
        }

        $ghost = $this->ghosts[$ghostId];

        try {
            $ghost->fulfill($realNodeId, $position);

            return true;
        } catch (\RuntimeException $e) {
            // Ghost already fulfilled or expired
            return false;
        }
    }

    /**
     * Get all unfulfilled ghosts (pending or expired)
     *
     * @return array<GhostNode>
     */
    public function getUnfulfilledGhosts(): array
    {
        return array_filter(
            $this->ghosts,
            fn (GhostNode $ghost) => $ghost->isUnfulfilled()
        );
    }

    /**
     * Get all pending ghosts (not fulfilled, not expired)
     *
     * @return array<GhostNode>
     */
    public function getPendingGhosts(): array
    {
        return array_filter(
            $this->ghosts,
            fn (GhostNode $ghost) => $ghost->isPending()
        );
    }

    /**
     * Get all fulfilled ghosts
     *
     * @return array<GhostNode>
     */
    public function getFulfilledGhosts(): array
    {
        return array_filter(
            $this->ghosts,
            fn (GhostNode $ghost) => $ghost->isFulfilled()
        );
    }

    /**
     * Get all expired ghosts
     *
     * @return array<GhostNode>
     */
    public function getExpiredGhosts(): array
    {
        return array_filter(
            $this->ghosts,
            fn (GhostNode $ghost) => $ghost->isExpired()
        );
    }

    /**
     * Get a specific ghost by ID
     *
     * @param  int  $ghostId  Ghost node ID
     * @return GhostNode|null The ghost node, or null if not found
     */
    public function getGhost(int $ghostId): ?GhostNode
    {
        return $this->ghosts[$ghostId] ?? null;
    }

    /**
     * Expire all pending ghosts (call at sentence end)
     *
     * @return int Number of ghosts expired
     */
    public function expirePendingGhosts(): int
    {
        $count = 0;

        foreach ($this->ghosts as $ghost) {
            if ($ghost->isPending()) {
                try {
                    $ghost->expire();
                    $count++;
                } catch (\RuntimeException $e) {
                    // Ghost already fulfilled, skip
                }
            }
        }

        return $count;
    }

    /**
     * Expire stale ghosts created before a given position
     *
     * @param  int  $positionThreshold  Expire ghosts created before this position
     * @return int Number of ghosts expired
     */
    public function expireStaleGhosts(int $positionThreshold): int
    {
        $count = 0;

        foreach ($this->ghosts as $ghost) {
            if ($ghost->isPending() && $ghost->createdAtPosition < $positionThreshold) {
                try {
                    $ghost->expire();
                    $count++;
                } catch (\RuntimeException $e) {
                    // Ghost already fulfilled, skip
                }
            }
        }

        return $count;
    }

    /**
     * Get all ghosts for a specific construction
     *
     * @param  int  $constructionId  Construction ID
     * @return array<GhostNode>
     */
    public function getGhostsForConstruction(int $constructionId): array
    {
        return array_filter(
            $this->ghosts,
            fn (GhostNode $ghost) => $ghost->createdByConstruction === $constructionId
        );
    }

    /**
     * Get all ghosts for a specific alternative
     *
     * @param  int  $alternativeId  Alternative ID
     * @return array<GhostNode>
     */
    public function getGhostsForAlternative(int $alternativeId): array
    {
        return array_filter(
            $this->ghosts,
            fn (GhostNode $ghost) => $ghost->createdByAlternative === $alternativeId
        );
    }

    /**
     * Get all ghosts of a specific type
     *
     * @param  string  $ghostType  Ghost type
     * @return array<GhostNode>
     */
    public function getGhostsByType(string $ghostType): array
    {
        return array_filter(
            $this->ghosts,
            fn (GhostNode $ghost) => $ghost->ghostType === $ghostType
        );
    }

    /**
     * Check if there are any unfulfilled ghosts
     *
     * @return bool True if there are unfulfilled ghosts
     */
    public function hasUnfulfilledGhosts(): bool
    {
        foreach ($this->ghosts as $ghost) {
            if ($ghost->isUnfulfilled()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if there are any pending ghosts
     *
     * @return bool True if there are pending ghosts
     */
    public function hasPendingGhosts(): bool
    {
        foreach ($this->ghosts as $ghost) {
            if ($ghost->isPending()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get count of ghosts by state
     *
     * @return array ['pending' => count, 'fulfilled' => count, 'expired' => count]
     */
    public function getGhostCounts(): array
    {
        $counts = [
            'pending' => 0,
            'fulfilled' => 0,
            'expired' => 0,
            'total' => count($this->ghosts),
        ];

        foreach ($this->ghosts as $ghost) {
            if ($ghost->isPending()) {
                $counts['pending']++;
            } elseif ($ghost->isFulfilled()) {
                $counts['fulfilled']++;
            } elseif ($ghost->isExpired()) {
                $counts['expired']++;
            }
        }

        return $counts;
    }

    /**
     * Add an existing ghost node to the manager
     *
     * Useful for testing or when ghosts are created outside the manager
     *
     * @param  GhostNode  $ghost  Ghost node to add
     */
    public function addGhost(GhostNode $ghost): void
    {
        $this->ghosts[$ghost->id] = $ghost;

        // Update counter to ensure no ID collisions
        if ($ghost->id < $this->ghostIdCounter) {
            $this->ghostIdCounter = $ghost->id - 1;
        }
    }

    /**
     * Clear all ghosts (for new sentence)
     */
    public function clear(): void
    {
        $this->ghosts = [];
        $this->ghostIdCounter = -1;
    }

    /**
     * Get all ghosts as array
     */
    public function toArray(): array
    {
        return array_map(
            fn (GhostNode $ghost) => $ghost->toArray(),
            array_values($this->ghosts)
        );
    }

    /**
     * Load ghosts from array
     *
     * @param  array  $ghostsData  Array of ghost node data
     */
    public function loadFromArray(array $ghostsData): void
    {
        $this->ghosts = [];

        foreach ($ghostsData as $ghostData) {
            $ghost = GhostNode::fromArray($ghostData);
            $this->ghosts[$ghost->id] = $ghost;

            // Update counter to ensure no ID collisions
            if ($ghost->id < $this->ghostIdCounter) {
                $this->ghostIdCounter = $ghost->id - 1;
            }
        }
    }

    /**
     * Generate a new unique ghost ID (negative)
     *
     * @return int Ghost node ID (negative)
     */
    private function generateGhostId(): int
    {
        return $this->ghostIdCounter--;
    }

    /**
     * Get statistics about ghost nodes
     *
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'total' => count($this->ghosts),
            'pending' => 0,
            'fulfilled' => 0,
            'expired' => 0,
            'by_type' => [],
            'by_construction' => [],
            'fulfillment_rate' => 0.0,
        ];

        foreach ($this->ghosts as $ghost) {
            // Count by state
            if ($ghost->isPending()) {
                $stats['pending']++;
            } elseif ($ghost->isFulfilled()) {
                $stats['fulfilled']++;
            } elseif ($ghost->isExpired()) {
                $stats['expired']++;
            }

            // Count by type
            if (! isset($stats['by_type'][$ghost->ghostType])) {
                $stats['by_type'][$ghost->ghostType] = 0;
            }
            $stats['by_type'][$ghost->ghostType]++;

            // Count by construction
            $constructionId = $ghost->createdByConstruction;
            if (! isset($stats['by_construction'][$constructionId])) {
                $stats['by_construction'][$constructionId] = 0;
            }
            $stats['by_construction'][$constructionId]++;
        }

        // Calculate fulfillment rate
        if ($stats['total'] > 0) {
            $stats['fulfillment_rate'] = $stats['fulfilled'] / $stats['total'];
        }

        return $stats;
    }
}
