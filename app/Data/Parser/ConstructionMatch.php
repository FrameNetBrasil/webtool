<?php

namespace App\Data\Parser;

/**
 * Construction Match Result
 *
 * Represents a successful match of a BNF construction against tokens.
 */
class ConstructionMatch
{
    public function __construct(
        public int $idConstruction,
        public string $name,
        public int $startPosition,
        public int $endPosition,
        public array $matchedTokens,
        public array $slots = [],
        public mixed $semanticValue = null,
        public array $features = [],
        public string $semanticType = 'Head',
    ) {}

    /**
     * Get span length (number of tokens matched)
     */
    public function getLength(): int
    {
        return $this->endPosition - $this->startPosition;
    }

    /**
     * Get matched text (concatenated tokens)
     */
    public function getMatchedText(): string
    {
        return implode(' ', array_map(fn ($t) => is_string($t) ? $t : ($t->word ?? ''), $this->matchedTokens));
    }

    /**
     * Check if this match overlaps with another
     */
    public function overlapsWith(self $other): bool
    {
        return ! (
            $this->endPosition <= $other->startPosition ||
            $this->startPosition >= $other->endPosition
        );
    }

    /**
     * Check if this match contains a position
     */
    public function containsPosition(int $position): bool
    {
        return $position >= $this->startPosition && $position < $this->endPosition;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'idConstruction' => $this->idConstruction,
            'name' => $this->name,
            'startPosition' => $this->startPosition,
            'endPosition' => $this->endPosition,
            'length' => $this->getLength(),
            'matchedTokens' => $this->matchedTokens,
            'matchedText' => $this->getMatchedText(),
            'slots' => $this->slots,
            'semanticValue' => $this->semanticValue,
            'features' => $this->features,
            'semanticType' => $this->semanticType,
        ];
    }

    /**
     * Create from BNFMatcher result
     */
    public static function fromMatcherResult(
        int $idConstruction,
        string $name,
        array $matchResult,
        int $startPosition,
        string $semanticType = 'Head'
    ): self {
        return new self(
            idConstruction: $idConstruction,
            name: $name,
            startPosition: $startPosition,
            endPosition: $matchResult['endPosition'],
            matchedTokens: $matchResult['matchedTokens'] ?? [],
            slots: $matchResult['slots'] ?? [],
            semanticValue: null,
            features: [],
            semanticType: $semanticType
        );
    }
}
