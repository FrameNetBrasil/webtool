<?php

namespace App\Models\SeqGraph;

/**
 * Represents a node in the parse result tree.
 *
 * Each node represents either a pattern completion or a terminal
 * element that matched input. The tree structure shows how patterns
 * combined to parse the sentence.
 */
class ParseTreeNode
{
    /**
     * Unique identifier for this node.
     */
    public string $id;

    /**
     * Pattern name (e.g., 'CLAUSE', 'REF', 'NOUN').
     */
    public string $patternName;

    /**
     * Timestamp when this pattern/element started.
     */
    public int $startTime;

    /**
     * Timestamp when this pattern completed.
     */
    public int $endTime;

    /**
     * Child nodes (sub-patterns or elements).
     *
     * @var array<ParseTreeNode>
     */
    public array $children = [];

    /**
     * Parent node (null for root).
     */
    public ?ParseTreeNode $parent = null;

    /**
     * For terminal nodes: the input value matched.
     */
    public ?string $inputValue = null;

    /**
     * For terminal nodes: the input type (POS tag).
     */
    public ?string $inputType = null;

    /**
     * The node ID from the sequence graph (for tracing).
     */
    public ?string $sourceNodeId = null;

    /**
     * Whether this is a terminal node (matches actual input).
     */
    public bool $isTerminal = false;

    /**
     * Role in parent pattern (e.g., 'subj', 'obj', 'verb').
     */
    public ?string $role = null;

    private static int $idCounter = 0;

    /**
     * Create a new parse tree node.
     */
    public function __construct(
        string $patternName,
        int $startTime,
        int $endTime,
        ?string $sourceNodeId = null
    ) {
        $this->id = 'ptn_'.(++self::$idCounter);
        $this->patternName = $patternName;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->sourceNodeId = $sourceNodeId;
    }

    /**
     * Create a terminal node for an input element.
     */
    public static function terminal(
        string $patternName,
        int $time,
        string $inputType,
        string $inputValue,
        ?string $sourceNodeId = null
    ): self {
        $node = new self($patternName, $time, $time, $sourceNodeId);
        $node->isTerminal = true;
        $node->inputType = $inputType;
        $node->inputValue = $inputValue;

        return $node;
    }

    /**
     * Create a pattern node.
     */
    public static function pattern(
        string $patternName,
        int $startTime,
        int $endTime,
        ?string $sourceNodeId = null
    ): self {
        return new self($patternName, $startTime, $endTime, $sourceNodeId);
    }

    /**
     * Add a child node.
     */
    public function addChild(ParseTreeNode $child): self
    {
        $child->parent = $this;
        $this->children[] = $child;

        return $this;
    }

    /**
     * Get the span as a string (e.g., "1-3" or "2").
     */
    public function getSpanString(): string
    {
        if ($this->startTime === $this->endTime) {
            return (string) $this->startTime;
        }

        return "{$this->startTime}-{$this->endTime}";
    }

    /**
     * Get a label for display.
     */
    public function getLabel(): string
    {
        if ($this->isTerminal) {
            return "{$this->patternName} [{$this->inputType}:\"{$this->inputValue}\"]";
        }

        return $this->patternName;
    }

    /**
     * Get all descendant nodes (depth-first).
     *
     * @return array<ParseTreeNode>
     */
    public function getDescendants(): array
    {
        $descendants = [];
        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Get depth in tree (0 for root).
     */
    public function getDepth(): int
    {
        $depth = 0;
        $node = $this;
        while ($node->parent !== null) {
            $depth++;
            $node = $node->parent;
        }

        return $depth;
    }

    /**
     * Reset the ID counter (useful for testing).
     */
    public static function resetIdCounter(): void
    {
        self::$idCounter = 0;
    }
}
