<?php

namespace App\Services\Parser;

class FocusQueueService
{
    private array $queue = [];

    private string $strategy;

    public function __construct(?string $strategy = null)
    {
        $this->strategy = $strategy ?? config('parser.queueStrategy', 'fifo');
    }

    /**
     * Add node to focus queue
     */
    public function enqueue(object $node): void
    {
        if ($this->strategy === 'fifo') {
            $this->queue[] = $node;
        } else {
            // LIFO: add to beginning
            array_unshift($this->queue, $node);
        }

        if (config('parser.logging.logQueue', false)) {
            logger()->info('FocusQueue: Enqueued node', [
                'label' => $node->label,
                'type' => $node->type,
                'position' => $node->positionInSentence,
                'strategy' => $this->strategy,
                'queueSize' => count($this->queue),
            ]);
        }
    }

    /**
     * Remove and return next node from queue
     */
    public function dequeue(): ?object
    {
        if ($this->isEmpty()) {
            return null;
        }

        $node = array_shift($this->queue);

        if (config('parser.logging.logQueue', false)) {
            logger()->info('FocusQueue: Dequeued node', [
                'label' => $node->label,
                'type' => $node->type,
                'queueSize' => count($this->queue),
            ]);
        }

        return $node;
    }

    /**
     * Get all active foci without removing them
     */
    public function getActiveFoci(): array
    {
        return $this->queue;
    }

    /**
     * Remove specific node from queue
     */
    public function removeFromQueue(object $node): void
    {
        $this->queue = array_filter($this->queue, function ($queueNode) use ($node) {
            return $queueNode->idParserNode !== $node->idParserNode;
        });

        // Re-index array
        $this->queue = array_values($this->queue);
    }

    /**
     * Check if queue is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->queue);
    }

    /**
     * Get queue size
     */
    public function size(): int
    {
        return count($this->queue);
    }

    /**
     * Peek at next node without removing
     */
    public function peek(): ?object
    {
        return $this->queue[0] ?? null;
    }

    /**
     * Clear the queue
     */
    public function clear(): void
    {
        $this->queue = [];
    }

    /**
     * Get nodes by type
     */
    public function getByType(string $type): array
    {
        return array_filter($this->queue, function ($node) use ($type) {
            return $node->type === $type;
        });
    }

    /**
     * Check if node is in queue
     */
    public function contains(object $node): bool
    {
        foreach ($this->queue as $queueNode) {
            if ($queueNode->idParserNode === $node->idParserNode) {
                return true;
            }
        }

        return false;
    }
}
