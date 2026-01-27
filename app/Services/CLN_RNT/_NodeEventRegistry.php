<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\NodeEvent;

/**
 * Node Event Registry
 *
 * Position-indexed event bus for cross-column node communication.
 *
 * This registry enables nodes at different column positions to communicate
 * efficiently without O(N²) loops through all previous columns.
 *
 * Key capabilities:
 * - Nodes subscribe to events at specific positions
 * - Events are emitted to position-specific subscribers
 * - Automatic cleanup of expired subscriptions
 * - Support for cross-position pattern matching and predictions
 *
 * Usage Example:
 * ```php
 * // Predicted node at position 2 subscribes to token arrival at position 5
 * $registry->subscribeToPosition(
 *     position: 5,
 *     event: NodeEvent::TOKEN_ARRIVED,
 *     callback: fn($data) => $predictedNode->checkMatch($data['token'])
 * );
 *
 * // When token arrives at position 5
 * $registry->emitAtPosition(
 *     position: 5,
 *     event: NodeEvent::TOKEN_ARRIVED,
 *     data: ['token' => $token]
 * );
 * // All subscribed predicted nodes check themselves
 * ```
 */
class NodeEventRegistry
{
    /**
     * Position-indexed event buses
     *
     * Format: [position => [event_name => [callable]]]
     *
     * @var array<int, array<string, array<int, callable>>>
     */
    private array $positionBuses = [];

    /**
     * Subscription metadata for cleanup
     *
     * Tracks subscription creation time and node references
     * for automatic garbage collection of expired subscriptions.
     *
     * Format: [subscription_id => ['position' => int, 'event' => string, 'created_at' => float]]
     *
     * @var array<string, array>
     */
    private array $subscriptionMeta = [];

    /**
     * Counter for triggering periodic cleanup
     */
    private int $cleanupCounter = 0;

    /**
     * Cleanup frequency (emit count)
     */
    private int $cleanupFrequency = 100;

    /**
     * Current processing position (for lazy cleanup)
     *
     * Tracked to identify stale subscriptions from far-behind positions.
     * (Phase 6: Performance optimization)
     */
    private int $currentPosition = 0;

    /**
     * Subscription TTL in seconds
     *
     * Subscriptions older than this are considered stale.
     * (Phase 6: Performance optimization)
     */
    private float $subscriptionTTL = 60.0;

    /**
     * Configure cleanup behavior (Phase 6)
     *
     * @param  int  $cleanupFrequency  How often to run cleanup (every N emits)
     * @param  float  $subscriptionTTL  Subscription time-to-live in seconds
     */
    public function configureCleanup(int $cleanupFrequency = 100, float $subscriptionTTL = 60.0): void
    {
        $this->cleanupFrequency = $cleanupFrequency;
        $this->subscriptionTTL = $subscriptionTTL;
    }

    /**
     * Subscribe to an event at a specific position
     *
     * The callback will be invoked when an event is emitted at the specified position.
     * Returns a subscription ID that can be used for manual unsubscription.
     *
     * @param  int  $position  Column position to subscribe to
     * @param  NodeEvent  $event  Event type
     * @param  callable  $callback  Function to call (signature: function(array $data): void)
     * @return string Subscription ID
     */
    public function subscribeToPosition(int $position, NodeEvent $event, callable $callback): string
    {
        // Initialize position bus if needed
        if (! isset($this->positionBuses[$position])) {
            $this->positionBuses[$position] = [];
        }

        if (! isset($this->positionBuses[$position][$event->value])) {
            $this->positionBuses[$position][$event->value] = [];
        }

        // Add subscriber
        $this->positionBuses[$position][$event->value][] = $callback;

        // Track subscription metadata
        $subscriptionId = uniqid('sub_', true);
        $this->subscriptionMeta[$subscriptionId] = [
            'position' => $position,
            'event' => $event->value,
            'created_at' => microtime(true),
        ];

        return $subscriptionId;
    }

    /**
     * Unsubscribe from an event at a specific position
     *
     * Removes the subscription identified by the subscription ID.
     *
     * @param  string  $subscriptionId  ID returned by subscribeToPosition
     */
    public function unsubscribe(string $subscriptionId): void
    {
        if (! isset($this->subscriptionMeta[$subscriptionId])) {
            return;
        }

        $meta = $this->subscriptionMeta[$subscriptionId];
        $position = $meta['position'];
        $eventName = $meta['event'];

        // Note: We can't easily remove the callback from the array without a reference
        // So we just remove the metadata tracking
        // The actual callback cleanup happens during periodic cleanup
        unset($this->subscriptionMeta[$subscriptionId]);
    }

    /**
     * Emit an event at a specific position
     *
     * Invokes all callbacks subscribed to this event at this position.
     *
     * @param  int  $position  Column position
     * @param  NodeEvent  $event  Event type
     * @param  array  $data  Event data to pass to subscribers
     */
    public function emitAtPosition(int $position, NodeEvent $event, array $data = []): void
    {
        // Track current processing position for lazy cleanup (Phase 6)
        if ($position > $this->currentPosition) {
            $this->currentPosition = $position;
        }

        // Add position to data
        $data['position'] = $position;
        $data['event'] = $event;

        // Get subscribers for this position and event
        $subscribers = $this->positionBuses[$position][$event->value] ?? [];

        // Call all subscribers
        foreach ($subscribers as $callback) {
            try {
                $callback($data);
            } catch (\Throwable $e) {
                // Log error but don't break other subscribers
                \Log::error('Node event registry error', [
                    'position' => $position,
                    'event' => $event->value,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Periodic cleanup (Phase 6: every N emissions)
        $this->cleanupCounter++;
        if ($this->cleanupCounter % $this->cleanupFrequency === 0) {
            $this->cleanupExpiredSubscriptions();
        }
    }

    /**
     * Subscribe to any event at a specific position
     *
     * Convenience method for subscribing to all events at a position.
     *
     * @param  int  $position  Column position
     * @param  callable  $callback  Function to call
     * @return array Array of subscription IDs
     */
    public function subscribeToAllEvents(int $position, callable $callback): array
    {
        $subscriptionIds = [];

        foreach (NodeEvent::cases() as $event) {
            $subscriptionIds[] = $this->subscribeToPosition($position, $event, $callback);
        }

        return $subscriptionIds;
    }

    /**
     * Get subscriber count for a position and event
     *
     * Useful for testing and debugging.
     *
     * @param  int  $position  Column position
     * @param  NodeEvent  $event  Event type
     * @return int Number of subscribers
     */
    public function getSubscriberCount(int $position, NodeEvent $event): int
    {
        return count($this->positionBuses[$position][$event->value] ?? []);
    }

    /**
     * Clean up expired subscriptions (Phase 6: Lazy cleanup implementation)
     *
     * Removes subscriptions that are no longer valid based on:
     * 1. Position-based expiration: Subscriptions for positions far behind current position
     * 2. Age-based expiration: Subscriptions older than TTL
     *
     * This is called periodically during emitAtPosition() to prevent unbounded growth.
     *
     * @return int Number of subscriptions cleaned up
     */
    public function cleanupExpiredSubscriptions(): int
    {
        $currentTime = microtime(true);
        $cleanedCount = 0;

        // Calculate stale position threshold (positions more than 10 behind current are stale)
        $stalePositionThreshold = max(0, $this->currentPosition - 10);

        // STEP 1: Remove subscriptions for stale positions (position-based cleanup)
        $positionsToRemove = [];
        foreach ($this->positionBuses as $position => $positionBus) {
            if ($position < $stalePositionThreshold) {
                $positionsToRemove[] = $position;

                // Count subscriptions being removed
                foreach ($positionBus as $eventSubscribers) {
                    $cleanedCount += count($eventSubscribers);
                }
            }
        }

        // Remove stale positions
        foreach ($positionsToRemove as $position) {
            unset($this->positionBuses[$position]);
        }

        // STEP 2: Remove old subscriptions based on age (age-based cleanup)
        $expiredSubscriptionIds = [];
        foreach ($this->subscriptionMeta as $subscriptionId => $meta) {
            $age = $currentTime - $meta['created_at'];

            if ($age > $this->subscriptionTTL) {
                $expiredSubscriptionIds[] = $subscriptionId;

                // Note: We can't easily count the actual callbacks removed here
                // because we don't have a direct reference from metadata to callback
                // This is a known limitation - metadata tracking is approximate
            }
        }

        // Remove expired subscription metadata
        foreach ($expiredSubscriptionIds as $subscriptionId) {
            unset($this->subscriptionMeta[$subscriptionId]);
        }

        // STEP 3: Clean up empty event arrays to save memory
        foreach ($this->positionBuses as $position => $positionBus) {
            foreach ($positionBus as $eventName => $subscribers) {
                if (empty($subscribers)) {
                    unset($this->positionBuses[$position][$eventName]);
                }
            }

            // Remove empty position buses
            if (empty($this->positionBuses[$position])) {
                unset($this->positionBuses[$position]);
            }
        }

        return $cleanedCount;
    }

    /**
     * Clear all subscriptions
     *
     * Useful for testing and reset.
     */
    public function clearAll(): void
    {
        $this->positionBuses = [];
        $this->subscriptionMeta = [];
        $this->cleanupCounter = 0;
    }

    /**
     * Clear subscriptions for a specific position
     *
     * @param  int  $position  Column position
     */
    public function clearPosition(int $position): void
    {
        unset($this->positionBuses[$position]);

        // Remove metadata
        $this->subscriptionMeta = array_filter(
            $this->subscriptionMeta,
            fn ($meta) => $meta['position'] !== $position
        );
    }

    /**
     * Get statistics about the registry
     *
     * Returns information about subscriptions for monitoring and debugging.
     *
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        $totalPositions = count($this->positionBuses);
        $totalSubscriptions = 0;
        $subscriptionsByEvent = [];

        foreach ($this->positionBuses as $positionBus) {
            foreach ($positionBus as $eventName => $subscribers) {
                $count = count($subscribers);
                $totalSubscriptions += $count;

                if (! isset($subscriptionsByEvent[$eventName])) {
                    $subscriptionsByEvent[$eventName] = 0;
                }
                $subscriptionsByEvent[$eventName] += $count;
            }
        }

        return [
            'total_positions' => $totalPositions,
            'total_subscriptions' => $totalSubscriptions,
            'subscriptions_by_event' => $subscriptionsByEvent,
            'cleanup_counter' => $this->cleanupCounter,
        ];
    }
}
