<?php

namespace App\Enums\Parser;

use App\Models\Parser\PhrasalCENode;

/**
 * MWE Component Types
 *
 * Defines the types of components that can be used in variable MWE patterns.
 * Each type matches against a different property of the token.
 */
enum MWEComponentType: string
{
    /** Exact word form match (case-insensitive) */
    case WORD = 'W';

    /** Match against token lemma (case-insensitive) */
    case LEMMA = 'L';

    /** Match against Universal Dependencies POS tag */
    case POS = 'P';

    /** Match against phrasal CE label */
    case CE = 'C';

    /** Wildcard - matches any token */
    case WILDCARD = '*';

    /**
     * Check if this component type matches the given token
     */
    public function matchesToken(string $value, PhrasalCENode $token): bool
    {
        return match ($this) {
            self::WORD => strtolower($token->word) === strtolower($value),
            self::LEMMA => strtolower($token->lemma) === strtolower($value),
            self::POS => $token->pos === $value,
            self::CE => $token->phrasalCE->value === $value,
            self::WILDCARD => true,
        };
    }

    /**
     * Check if this component type represents a fixed (indexable) value
     *
     * Only WORD type is considered fixed because it can be used
     * for anchor-based indexing in the database.
     */
    public function isFixed(): bool
    {
        return $this === self::WORD;
    }

    /**
     * Get human-readable description for this component type
     */
    public function description(): string
    {
        return match ($this) {
            self::WORD => 'Exact word form',
            self::LEMMA => 'Word lemma',
            self::POS => 'POS tag',
            self::CE => 'CE label',
            self::WILDCARD => 'Any token',
        };
    }

    /**
     * Get all valid POS tag values
     */
    public static function validPOSTags(): array
    {
        return [
            'ADJ', 'ADP', 'ADV', 'AUX', 'CCONJ', 'DET', 'INTJ',
            'NOUN', 'NUM', 'PART', 'PRON', 'PROPN', 'PUNCT',
            'SCONJ', 'SYM', 'VERB', 'X',
        ];
    }

    /**
     * Get all valid CE label values
     */
    public static function validCELabels(): array
    {
        return PhrasalCE::values();
    }

    /**
     * Validate a component value for this type
     */
    public function validateValue(string $value): bool
    {
        return match ($this) {
            self::WORD, self::LEMMA => strlen($value) > 0,
            self::POS => in_array($value, self::validPOSTags()),
            self::CE => in_array($value, self::validCELabels()),
            self::WILDCARD => true,
        };
    }
}
