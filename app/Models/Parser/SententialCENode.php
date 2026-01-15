<?php

namespace App\Models\Parser;

use App\Enums\Parser\SententialCE;

/**
 * Sentential CE Node - Stage 3 (Folding) Output
 *
 * Represents clause-level elements with sentential CE classification.
 * Biological analogy: Polypeptides - complete clauses that integrate into sentence.
 */
class SententialCENode
{
    public function __construct(
        /** @var ClausalCENode[] */
        public array $clausalCEs,
        public SententialCE $sententialCE,
        public bool $isMain = false,
        public ?string $clauseMarker = null,
        public ?int $clauseIndex = null,
    ) {}

    /**
     * Create from a Clause object
     */
    public static function fromClause(Clause $clause, int $index = 0): self
    {
        $sententialCE = SententialCE::fromClauseProperties(
            isRoot: $clause->isRoot(),
            clauseMarker: $clause->getClauseMarker(),
            deprel: $clause->getPredicate()?->phrasalNode->deprel,
            features: []
        );

        return new self(
            clausalCEs: $clause->getNodes(),
            sententialCE: $sententialCE,
            isMain: $sententialCE === SententialCE::MAIN,
            clauseMarker: $clause->getClauseMarker(),
            clauseIndex: $index,
        );
    }

    /**
     * Get the predicate node of this clause
     */
    public function getPredicate(): ?ClausalCENode
    {
        foreach ($this->clausalCEs as $node) {
            if ($node->isPredicate()) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Get all argument nodes
     */
    public function getArguments(): array
    {
        return array_filter($this->clausalCEs, fn (ClausalCENode $n) => $n->isArgument());
    }

    /**
     * Get the first node (by index)
     */
    public function getFirstNode(): ?ClausalCENode
    {
        if (empty($this->clausalCEs)) {
            return null;
        }

        $sorted = $this->clausalCEs;
        usort($sorted, fn ($a, $b) => $a->getIndex() <=> $b->getIndex());

        return $sorted[0];
    }

    /**
     * Get the last node (by index)
     */
    public function getLastNode(): ?ClausalCENode
    {
        if (empty($this->clausalCEs)) {
            return null;
        }

        $sorted = $this->clausalCEs;
        usort($sorted, fn ($a, $b) => $a->getIndex() <=> $b->getIndex());

        return end($sorted);
    }

    /**
     * Check if this is a subordinate clause
     */
    public function isSubordinate(): bool
    {
        return $this->sententialCE->isSubordinate();
    }

    /**
     * Get all words in this clause as a string
     */
    public function getText(): string
    {
        $sorted = $this->clausalCEs;
        usort($sorted, fn ($a, $b) => $a->getIndex() <=> $b->getIndex());

        return implode(' ', array_map(fn ($n) => $n->getWord(), $sorted));
    }

    /**
     * Check if this clause contains a specific node
     */
    public function containsNode(ClausalCENode $node): bool
    {
        foreach ($this->clausalCEs as $clausalNode) {
            if ($clausalNode === $node) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this clause has a finite predicate
     */
    public function hasFinitePredicate(): bool
    {
        $pred = $this->getPredicate();

        return $pred !== null && $pred->isFinitePredicate();
    }

    /**
     * Check if this clause has a subordinating conjunction
     */
    public function hasSubordinator(): bool
    {
        return $this->clauseMarker !== null;
    }

    /**
     * Convert to array for storage/serialization
     */
    public function toArray(): array
    {
        return [
            'clausal_ces' => array_map(fn (ClausalCENode $n) => $n->toArray(), $this->clausalCEs),
            'sentential_ce' => $this->sententialCE->value,
            'is_main' => $this->isMain,
            'clause_marker' => $this->clauseMarker,
            'clause_index' => $this->clauseIndex,
            'text' => $this->getText(),
        ];
    }
}
