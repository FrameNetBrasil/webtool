<?php

namespace App\Services\Parser\SemanticActions;

use App\Data\Parser\ConstructionMatch;

/**
 * Portuguese Number Semantic Action
 *
 * Converts Portuguese cardinal number words to numeric values.
 *
 * Examples:
 * - "dois mil e quinhentos" → 2500
 * - "trezentos e quarenta e cinco" → 345
 * - "mil e quinhentos" → 1500
 */
class PortugueseNumberAction implements SemanticAction
{
    private const NUMBER_WORDS = [
        // Units (0-9)
        'zero' => 0,
        'um' => 1, 'uma' => 1,
        'dois' => 2, 'duas' => 2,
        'três' => 3, 'tres' => 3,
        'quatro' => 4,
        'cinco' => 5,
        'seis' => 6,
        'sete' => 7,
        'oito' => 8,
        'nove' => 9,

        // Tens (10-90)
        'dez' => 10,
        'onze' => 11,
        'doze' => 12,
        'treze' => 13,
        'catorze' => 14, 'quatorze' => 14,
        'quinze' => 15,
        'dezesseis' => 16, 'dezasseis' => 16,
        'dezessete' => 17, 'dezassete' => 17,
        'dezoito' => 18,
        'dezenove' => 19, 'dezanove' => 19,
        'vinte' => 20,
        'trinta' => 30,
        'quarenta' => 40,
        'cinquenta' => 50, 'cinquenta' => 50,
        'sessenta' => 60,
        'setenta' => 70,
        'oitenta' => 80,
        'noventa' => 90,

        // Hundreds (100-900)
        'cem' => 100,
        'cento' => 100,
        'duzentos' => 200, 'duzentas' => 200,
        'trezentos' => 300, 'trezentas' => 300,
        'quatrocentos' => 400, 'quatrocentas' => 400,
        'quinhentos' => 500, 'quinhentas' => 500,
        'seiscentos' => 600, 'seiscentas' => 600,
        'setecentos' => 700, 'setecentas' => 700,
        'oitocentos' => 800, 'oitocentas' => 800,
        'novecentos' => 900, 'novecentas' => 900,

        // Multipliers
        'mil' => 1000,
        'milhão' => 1000000, 'milhao' => 1000000,
        'milhões' => 1000000, 'milhoes' => 1000000,
        'bilhão' => 1000000000, 'bilhao' => 1000000000,
        'bilhões' => 1000000000, 'bilhoes' => 1000000000,
    ];

    public function getName(): string
    {
        return 'portuguese_number';
    }

    public function calculate(ConstructionMatch $match, array $semantics): mixed
    {
        $calculation = $semantics['calculation'] ?? [];

        // If calculation method is specified
        if (isset($calculation['method']) && $calculation['method'] === 'portuguese_number') {
            return $this->calculateFromSlots($match, $calculation);
        }

        // Default: parse matched text
        return $this->parseNumber($match->getMatchedText());
    }

    public function deriveFeatures(mixed $value): array
    {
        if (! is_numeric($value)) {
            return [];
        }

        return [
            'NumType' => 'Card',
            'numericValue' => (int) $value,
        ];
    }

    public function validateSemantics(array $semantics): array
    {
        $errors = [];

        // Validation is lenient - we can parse most structures
        if (isset($semantics['calculation'])) {
            $calc = $semantics['calculation'];

            if (isset($calc['slots']) && ! is_array($calc['slots'])) {
                $errors[] = 'calculation.slots must be an array';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Calculate from slot configuration
     */
    private function calculateFromSlots(ConstructionMatch $match, array $calculation): int
    {
        $slots = $calculation['slots'] ?? [];
        $total = 0;

        foreach ($slots as $slotKey => $config) {
            // Get slot value
            $slotValue = $match->slots[$slotKey] ?? null;

            if ($slotValue === null) {
                // Use default if provided
                if (isset($config['default'])) {
                    $slotValue = $config['default'];
                } else {
                    continue;
                }
            }

            // Parse the slot value
            $number = is_numeric($slotValue) ? (int) $slotValue : $this->wordToNumber($slotValue);

            // Apply operation
            if (isset($config['multiply'])) {
                $total += $number * $config['multiply'];
            } elseif (isset($config['add']) && $config['add']) {
                $total += $number;
            } else {
                $total += $number;
            }
        }

        return $total;
    }

    /**
     * Parse full Portuguese number text to integer
     */
    private function parseNumber(string $text): int
    {
        $text = mb_strtolower(trim($text));

        // Handle simple numeric strings
        if (is_numeric($text)) {
            return (int) $text;
        }

        // Split by common separators
        $text = str_replace([',', ' e '], ' ', $text);
        $words = array_filter(explode(' ', $text));

        $total = 0;
        $current = 0;

        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word)) {
                continue;
            }

            $value = $this->wordToNumber($word);

            if ($value === null) {
                continue; // Skip unknown words
            }

            // Multipliers (mil, milhão, etc.)
            if ($value >= 1000) {
                if ($current === 0) {
                    $current = 1; // "mil" alone means "um mil"
                }
                $current *= $value;
                $total += $current;
                $current = 0;
            } else {
                $current += $value;
            }
        }

        $total += $current;

        return $total;
    }

    /**
     * Convert single Portuguese number word to value
     */
    private function wordToNumber(string $word): ?int
    {
        $word = mb_strtolower(trim($word));

        return self::NUMBER_WORDS[$word] ?? null;
    }

    /**
     * Get all number words (for testing/debugging)
     */
    public static function getNumberWords(): array
    {
        return self::NUMBER_WORDS;
    }
}
