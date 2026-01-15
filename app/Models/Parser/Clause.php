<?php

namespace App\Models\Parser;

use App\Enums\Parser\ClausalCE;

/**
 * Clause - Represents a clause as a collection of ClausalCENodes
 *
 * Used during Stage 3 (Folding) to group clausal CEs into coherent clause units
 * before assigning sentential CE labels.
 */
class Clause
{
    /** @var ClausalCENode[] */
    private array $nodes = [];

    private bool $isRoot = false;

    private ?string $clauseMarker = null;

    private ?ClausalCENode $rootPredicate = null;

    public function __construct(array $nodes = [])
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }
    }

    /**
     * Add a node to this clause
     */
    public function addNode(ClausalCENode $node): void
    {
        $this->nodes[] = $node;

        // Track if we have a predicate
        if ($node->isPredicate() && $this->rootPredicate === null) {
            $this->rootPredicate = $node;
        }
    }

    /**
     * Get all nodes in this clause
     *
     * @return ClausalCENode[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * Get the predicate of this clause
     */
    public function getPredicate(): ?ClausalCENode
    {
        return $this->rootPredicate;
    }

    /**
     * Get all predicates (for complex predicates)
     *
     * @return ClausalCENode[]
     */
    public function getPredicates(): array
    {
        return array_filter($this->nodes, fn (ClausalCENode $n) => $n->isPredicate());
    }

    /**
     * Get all arguments
     *
     * @return ClausalCENode[]
     */
    public function getArguments(): array
    {
        return array_filter($this->nodes, fn (ClausalCENode $n) => $n->isArgument());
    }

    /**
     * Get all nodes of a specific clausal CE type
     *
     * @return ClausalCENode[]
     */
    public function getNodesByType(ClausalCE $type): array
    {
        return array_filter($this->nodes, fn (ClausalCENode $n) => $n->clausalCE === $type);
    }

    /**
     * Check if this clause has a finite predicate
     */
    public function hasFinitePredicate(): bool
    {
        foreach ($this->nodes as $node) {
            if ($node->isFinitePredicate()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this clause has a subordinator (SCONJ marker)
     */
    public function hasSubordinator(): bool
    {
        return $this->clauseMarker !== null;
    }

    /**
     * Check if this clause has a coordinator (CCONJ marker)
     */
    public function hasCoordinator(): bool
    {
        foreach ($this->nodes as $node) {
            if ($node->phrasalNode->pos === 'CCONJ') {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the clause marker (subordinating conjunction)
     */
    public function setClauseMarker(string $marker): void
    {
        $this->clauseMarker = $marker;
    }

    /**
     * Get the clause marker
     */
    public function getClauseMarker(): ?string
    {
        return $this->clauseMarker;
    }

    /**
     * Mark this as the root clause
     */
    public function setAsRoot(bool $isRoot = true): void
    {
        $this->isRoot = $isRoot;
    }

    /**
     * Check if this is the root clause
     */
    public function isRoot(): bool
    {
        return $this->isRoot;
    }

    /**
     * Get the span (start and end indices) of this clause
     *
     * @return array{start: int, end: int}
     */
    public function getSpan(): array
    {
        if (empty($this->nodes)) {
            return ['start' => 0, 'end' => 0];
        }

        $indices = array_map(fn (ClausalCENode $n) => $n->getIndex(), $this->nodes);

        return [
            'start' => min($indices),
            'end' => max($indices),
        ];
    }

    /**
     * Check if this clause contains a specific index
     */
    public function containsIndex(int $index): bool
    {
        foreach ($this->nodes as $node) {
            if ($node->getIndex() === $index) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this clause contains a specific node
     */
    public function containsNode(ClausalCENode $target): bool
    {
        foreach ($this->nodes as $node) {
            if ($node === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the text representation of this clause
     */
    public function getText(): string
    {
        $sorted = $this->nodes;
        usort($sorted, fn ($a, $b) => $a->getIndex() <=> $b->getIndex());

        return implode(' ', array_map(fn ($n) => $n->getWord(), $sorted));
    }

    /**
     * Get the count of nodes
     */
    public function count(): int
    {
        return count($this->nodes);
    }

    /**
     * Check if the clause is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->nodes);
    }

    /**
     * Convert to array for storage/serialization
     */
    public function toArray(): array
    {
        return [
            'nodes' => array_map(fn (ClausalCENode $n) => $n->toArray(), $this->nodes),
            'is_root' => $this->isRoot,
            'clause_marker' => $this->clauseMarker,
            'has_finite_predicate' => $this->hasFinitePredicate(),
            'has_subordinator' => $this->hasSubordinator(),
            'has_coordinator' => $this->hasCoordinator(),
            'span' => $this->getSpan(),
            'text' => $this->getText(),
        ];
    }
}
