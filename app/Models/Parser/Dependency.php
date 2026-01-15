<?php

namespace App\Models\Parser;

/**
 * Dependency - Represents a syntactic dependency relationship
 *
 * Models both local dependencies (Stage 2) and long-distance dependencies (Stage 3).
 * Biological analogy: Peptide bonds (local) and disulfide bridges (long-distance).
 */
class Dependency
{
    public function __construct(
        public ClausalCENode $governor,
        public ClausalCENode $dependent,
        public string $relation,
        public float $strength = 1.0,
        public bool $isNonProjective = false,
        public bool $isLongDistance = false,
    ) {}

    /**
     * Create a subject dependency
     */
    public static function subject(ClausalCENode $predicate, ClausalCENode $subject, float $strength = 1.0): self
    {
        return new self(
            governor: $predicate,
            dependent: $subject,
            relation: 'SUBJ',
            strength: $strength,
        );
    }

    /**
     * Create an object dependency
     */
    public static function object(ClausalCENode $predicate, ClausalCENode $object, float $strength = 1.0): self
    {
        return new self(
            governor: $predicate,
            dependent: $object,
            relation: 'OBJ',
            strength: $strength,
        );
    }

    /**
     * Create an adverbial modifier dependency
     */
    public static function adverbial(ClausalCENode $governor, ClausalCENode $modifier, float $strength = 0.8): self
    {
        return new self(
            governor: $governor,
            dependent: $modifier,
            relation: 'ADV',
            strength: $strength,
        );
    }

    /**
     * Create a relative clause dependency (typically non-projective)
     */
    public static function relativeClause(
        ClausalCENode $antecedent,
        ClausalCENode $relativePredicate,
        float $strength = 1.0
    ): self {
        return new self(
            governor: $antecedent,
            dependent: $relativePredicate,
            relation: 'RELCL',
            strength: $strength,
            isNonProjective: true,
            isLongDistance: true,
        );
    }

    /**
     * Create a complement clause dependency
     */
    public static function complementClause(
        ClausalCENode $mainPredicate,
        ClausalCENode $complementPredicate,
        float $strength = 1.0
    ): self {
        return new self(
            governor: $mainPredicate,
            dependent: $complementPredicate,
            relation: 'CCOMP',
            strength: $strength,
            isLongDistance: true,
        );
    }

    /**
     * Create an adverbial clause dependency
     */
    public static function adverbialClause(
        ClausalCENode $mainPredicate,
        ClausalCENode $adverbialPredicate,
        float $strength = 1.0
    ): self {
        return new self(
            governor: $mainPredicate,
            dependent: $adverbialPredicate,
            relation: 'ADVCL',
            strength: $strength,
            isLongDistance: true,
        );
    }

    /**
     * Create a modifier dependency (within phrase)
     */
    public static function modifier(ClausalCENode $head, ClausalCENode $modifier, float $strength = 0.9): self
    {
        return new self(
            governor: $head,
            dependent: $modifier,
            relation: 'MOD',
            strength: $strength,
        );
    }

    /**
     * Calculate the dependency length (distance between governor and dependent)
     */
    public function getLength(): int
    {
        return abs($this->governor->getIndex() - $this->dependent->getIndex());
    }

    /**
     * Check if this dependency crosses another node
     *
     * @param  ClausalCENode  ...$nodes  Nodes to check
     */
    public function crosses(ClausalCENode ...$nodes): bool
    {
        $start = min($this->governor->getIndex(), $this->dependent->getIndex());
        $end = max($this->governor->getIndex(), $this->dependent->getIndex());

        foreach ($nodes as $node) {
            $idx = $node->getIndex();
            if ($idx > $start && $idx < $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this dependency crosses a span of indices
     */
    public function crossesSpan(int $spanStart, int $spanEnd): bool
    {
        $depStart = min($this->governor->getIndex(), $this->dependent->getIndex());
        $depEnd = max($this->governor->getIndex(), $this->dependent->getIndex());

        // Check if spans overlap without one containing the other
        return ($spanStart < $depEnd && $spanEnd > $depStart) &&
               ! ($spanStart >= $depStart && $spanEnd <= $depEnd) &&
               ! ($depStart >= $spanStart && $depEnd <= $spanEnd);
    }

    /**
     * Convert to array for storage/serialization
     */
    public function toArray(): array
    {
        return [
            'governor_index' => $this->governor->getIndex(),
            'governor_word' => $this->governor->getWord(),
            'dependent_index' => $this->dependent->getIndex(),
            'dependent_word' => $this->dependent->getWord(),
            'relation' => $this->relation,
            'strength' => $this->strength,
            'is_non_projective' => $this->isNonProjective,
            'is_long_distance' => $this->isLongDistance,
            'length' => $this->getLength(),
        ];
    }
}
