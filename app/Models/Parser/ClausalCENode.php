<?php

namespace App\Models\Parser;

use App\Enums\Parser\ClausalCE;

/**
 * Clausal CE Node - Stage 2 (Translation) Output
 *
 * Represents phrase-level elements with clausal CE classification.
 * Biological analogy: Peptides - functional phrases built from words.
 */
class ClausalCENode
{
    public function __construct(
        public PhrasalCENode $phrasalNode,
        public ClausalCE $clausalCE,
        public array $features = [],
        public array $dependencies = [],
        public ?int $phraseId = null,
    ) {}

    /**
     * Create from a PhrasalCENode
     */
    public static function fromPhrasalCE(PhrasalCENode $phrasalNode): self
    {
        $clausalCE = ClausalCE::fromPhrasalCE(
            $phrasalNode->phrasalCE,
            $phrasalNode->pos,
            $phrasalNode->getLexicalFeatures(),
            $phrasalNode->deprel
        );

        return new self(
            phrasalNode: $phrasalNode,
            clausalCE: $clausalCE,
            features: $phrasalNode->features,
        );
    }

    /**
     * Add a dependency to this node
     */
    public function addDependency(Dependency $dependency): void
    {
        $this->dependencies[] = $dependency;
    }

    /**
     * Get all dependencies where this node is the governor
     */
    public function getGoverningDependencies(): array
    {
        return array_filter($this->dependencies, fn (Dependency $dep) => $dep->governor === $this);
    }

    /**
     * Get all dependencies where this node is the dependent
     */
    public function getDependentDependencies(): array
    {
        return array_filter($this->dependencies, fn (Dependency $dep) => $dep->dependent === $this);
    }

    /**
     * Check if this is a predicate
     */
    public function isPredicate(): bool
    {
        return $this->clausalCE === ClausalCE::PRED;
    }

    /**
     * Check if this is an argument
     */
    public function isArgument(): bool
    {
        return $this->clausalCE === ClausalCE::ARG;
    }

    /**
     * Check if this is a finite predicate
     */
    public function isFinitePredicate(): bool
    {
        return $this->isPredicate() && $this->phrasalNode->isFiniteVerb();
    }

    /**
     * Get the word from the underlying phrasal node
     */
    public function getWord(): string
    {
        return $this->phrasalNode->word;
    }

    /**
     * Get the lemma from the underlying phrasal node
     */
    public function getLemma(): string
    {
        return $this->phrasalNode->lemma;
    }

    /**
     * Get the index from the underlying phrasal node
     */
    public function getIndex(): int
    {
        return $this->phrasalNode->index;
    }

    /**
     * Get a specific feature
     */
    public function getFeature(string $name): ?string
    {
        return $this->features['lexical'][$name] ?? $this->features['derived'][$name] ?? null;
    }

    /**
     * Check if node has a specific feature with value
     */
    public function hasFeature(string $name, ?string $value = null): bool
    {
        $featureValue = $this->getFeature($name);

        if ($featureValue === null) {
            return false;
        }

        if ($value === null) {
            return true;
        }

        return $featureValue === $value;
    }

    /**
     * Convert to array for storage/serialization
     */
    public function toArray(): array
    {
        return [
            'phrasal_node' => $this->phrasalNode->toArray(),
            'clausal_ce' => $this->clausalCE->value,
            'features' => $this->features,
            'dependencies' => array_map(fn (Dependency $d) => $d->toArray(), $this->dependencies),
            'phrase_id' => $this->phraseId,
        ];
    }
}
