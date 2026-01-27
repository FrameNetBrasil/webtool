<?php

namespace App\Models\SeqGraph;

/**
 * Represents a node in a sequence graph with activation state.
 *
 * Nodes can be either element nodes (representing input elements) or
 * routing nodes (graph structural nodes like start/end). Element nodes
 * track activation state and timestamps for building incremental structures.
 */
class SeqNode
{
    public const TYPE_ELEMENT = 'element';

    public const TYPE_START = 'start';

    public const TYPE_END = 'end';

    public const TYPE_PATTERN = 'pattern';

    public const TYPE_INTERMEDIATE = 'intermediate';

    /**
     * Unique identifier for this node.
     */
    public string $id;

    /**
     * Node type: 'element', 'start', 'end', or 'intermediate'.
     */
    public string $type;

    /**
     * Element type this node matches (e.g., 'NOUN', 'VERB').
     *
     * Only relevant for element nodes. Null for routing nodes.
     */
    public ?string $elementType;

    /**
     * Specific element value this node matches (optional).
     *
     * If set, the node only matches elements with this exact value.
     * If null, any element of the specified type matches.
     */
    public ?string $elementValue;

    /**
     * Activation timestamps.
     *
     * Keyed by timestamp value to avoid duplicates. Use array_keys() to
     * get the list of timestamps. Multiple activations can occur for
     * patterns with repetition.
     *
     * @var array<int, int>
     */
    public array $timestamps;

    /**
     * Pattern name this node belongs to (for unified graph).
     *
     * Used to track which pattern a node was originally from when nodes
     * from multiple patterns are combined into a unified graph.
     */
    public ?string $patternName = null;

    /**
     * Whether this node is currently active (listening for successors).
     */
    public bool $active;

    /**
     * Create a new sequence node.
     *
     * @param  string  $id  Unique node identifier
     * @param  string  $type  Node type (element, start, end, or intermediate)
     * @param  string|null  $elementType  Element type for matching (null for routing nodes)
     * @param  string|null  $elementValue  Specific value for matching (optional)
     * @param  array<int, int>  $timestamps  Activation timestamps (keyed by timestamp)
     * @param  bool  $active  Whether node is currently active
     */
    public function __construct(
        string $id,
        string $type,
        ?string $elementType = null,
        ?string $elementValue = null,
        array $timestamps = [],
        bool $active = false
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->elementType = $elementType;
        $this->elementValue = $elementValue;
        $this->timestamps = $timestamps;
        $this->active = $active;
    }

    /**
     * Check if this is an element node.
     *
     * @return bool True if this node represents an input element
     */
    public function isElement(): bool
    {
        return $this->type === self::TYPE_ELEMENT;
    }

    /**
     * Check if this is a routing node (start, end, pattern, or intermediate).
     *
     * @return bool True if this is a structural routing node
     */
    public function isRouting(): bool
    {
        return in_array($this->type, [self::TYPE_START, self::TYPE_END, self::TYPE_PATTERN, self::TYPE_INTERMEDIATE]);
    }

    /**
     * Activate this node, marking it as listening for successors.
     */
    public function activate(): void
    {
        $this->active = true;
    }

    /**
     * Deactivate this node, stopping it from listening for successors.
     */
    public function deactivate(): void
    {
        $this->active = false;
    }

    /**
     * Fire this node with a timestamp, recording the activation.
     *
     * Element nodes record when they match input. Routing nodes
     * can fire without timestamps to propagate activation. Uses
     * timestamp as key to avoid duplicate entries.
     *
     * @param  int|null  $timestamp  Time when this node was triggered
     */
    public function fire(?int $timestamp = null): void
    {
        if ($timestamp !== null) {
            $this->timestamps[$timestamp] = $timestamp;
        }
    }

    /**
     * Reset this node to initial state.
     *
     * Clears timestamps and deactivates the node.
     */
    public function reset(): void
    {
        $this->timestamps = [];
        $this->active = false;
    }
}
