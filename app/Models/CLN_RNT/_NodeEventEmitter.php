<?php

namespace App\Models\CLN_RNT;

/**
 * Node Event Emitter Trait
 *
 * Provides event subscription and emission capabilities to nodes (JNode and BNode).
 * Enables node-to-node communication without column orchestration.
 *
 * This trait implements a lightweight pub/sub pattern where:
 * - Nodes can subscribe to specific events from other nodes
 * - Nodes emit events when their state changes
 * - Subscribers receive event data via callbacks
 *
 * Usage:
 * ```php
 * class JNode {
 *     use NodeEventEmitter;
 *
 *     public function someMethod() {
 *         $this->emit(NodeEvent::ACTIVATED, ['node' => $this]);
 *     }
 * }
 *
 * $node->subscribe(NodeEvent::ACTIVATED, function($data) {
 *     // Handle activation
 * });
 * ```
 */
trait NodeEventEmitter
{
    /**
     * Event subscribers
     *
     * Format: [NodeEvent::value => [callable]]
     *
     * @var array<string, array<int, callable>>
     */
    private array $subscribers = [];

    /**
     * Subscribe to an event from this node
     *
     * The callback will be invoked when this node emits the specified event.
     * Multiple callbacks can subscribe to the same event.
     *
     * @param  NodeEvent  $event  The event type to subscribe to
     * @param  callable  $callback  Function to call when event is emitted
     *                              Signature: function(array $data): void
     */
    public function subscribe(NodeEvent $event, callable $callback): void
    {
        if (! isset($this->subscribers[$event->value])) {
            $this->subscribers[$event->value] = [];
        }

        $this->subscribers[$event->value][] = $callback;
    }

    /**
     * Unsubscribe from an event
     *
     * Removes the specified callback from the event's subscriber list.
     * If the callback is not found, does nothing.
     *
     * @param  NodeEvent  $event  The event type to unsubscribe from
     * @param  callable  $callback  The callback to remove
     */
    public function unsubscribe(NodeEvent $event, callable $callback): void
    {
        if (! isset($this->subscribers[$event->value])) {
            return;
        }

        // Find and remove the callback
        $this->subscribers[$event->value] = array_filter(
            $this->subscribers[$event->value],
            fn ($sub) => $sub !== $callback
        );

        // Clean up empty arrays
        if (empty($this->subscribers[$event->value])) {
            unset($this->subscribers[$event->value]);
        }
    }

    /**
     * Emit an event to all subscribers
     *
     * Calls all subscribed callbacks for the specified event type,
     * passing the event data to each callback.
     *
     * If a callback throws an exception, it is caught and logged,
     * but other subscribers will still be notified.
     *
     * @param  NodeEvent  $event  The event type to emit
     * @param  array  $data  Event data to pass to subscribers
     */
    protected function emit(NodeEvent $event, array $data = []): void
    {
        if (! isset($this->subscribers[$event->value])) {
            return;
        }

        // Add emitting node to data if not already present
        if (! isset($data['source_node'])) {
            $data['source_node'] = $this;
        }

        // Call all subscribers
        foreach ($this->subscribers[$event->value] as $callback) {
            try {
                $callback($data);
            } catch (\Throwable $e) {
                // Log error but don't break other subscribers
                \Log::error('Node event subscriber error', [
                    'event' => $event->value,
                    'node_id' => $this->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get count of subscribers for an event
     *
     * Useful for testing and debugging.
     *
     * @param  NodeEvent  $event  The event type
     * @return int Number of subscribers
     */
    public function getSubscriberCount(NodeEvent $event): int
    {
        return count($this->subscribers[$event->value] ?? []);
    }

    /**
     * Clear all subscribers for this node
     *
     * Useful for testing and cleanup.
     */
    public function clearAllSubscribers(): void
    {
        $this->subscribers = [];
    }

    /**
     * Clear subscribers for a specific event
     *
     * @param  NodeEvent  $event  The event type
     */
    public function clearSubscribers(NodeEvent $event): void
    {
        unset($this->subscribers[$event->value]);
    }
}
