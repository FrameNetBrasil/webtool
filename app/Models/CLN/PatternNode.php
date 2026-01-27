<?php

namespace App\Models\CLN;

/**
 * A node in your pattern graph (grammar specification).
 */
class PatternNode {
    public string $id;
    public PatternNodeType $type;
    public ?string $value = null;      // For LITERAL: word, for POS: category name
    public array $children = [];        // For OR: array of child ids
    public ?string $leftChildId = null; // For AND: left element
    public ?string $rightChildId = null;// For AND: right element

    public function __construct(
        string $id,
        PatternNodeType $type,
        ?string $value
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->value = $value;
    }

    public static function getPatternNodeType(string $type): PatternNodeType {
        return match ($type) {
            'OR' => PatternNodeType::OR,
            'AND' => PatternNodeType::AND,
            'POS' => PatternNodeType::POS,
            'LITERAL' => PatternNodeType::LITERAL,
        };
    }
}
