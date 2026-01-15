<?php

namespace App\Services\Parser\SemanticActions;

use App\Data\Parser\ConstructionMatch;

/**
 * Date Parser Semantic Action
 *
 * Parses Portuguese date expressions to structured date values.
 *
 * Examples:
 * - "25 de dezembro de 2024" → ['day' => 25, 'month' => 12, 'year' => 2024]
 * - "15 de março" → ['day' => 15, 'month' => 3]
 */
class DateParserAction implements SemanticAction
{
    private const MONTHS = [
        'janeiro' => 1,
        'fevereiro' => 2,
        'março' => 3, 'marco' => 3,
        'abril' => 4,
        'maio' => 5,
        'junho' => 6,
        'julho' => 7,
        'agosto' => 8,
        'setembro' => 9,
        'outubro' => 10,
        'novembro' => 11,
        'dezembro' => 12,
    ];

    public function getName(): string
    {
        return 'date';
    }

    public function calculate(ConstructionMatch $match, array $semantics): mixed
    {
        $slots = $semantics['slots'] ?? [];
        $result = [];

        // Extract day
        if (isset($slots['day'])) {
            $dayKey = $this->findSlotKey($match, $slots['day']);
            if ($dayKey && isset($match->slots[$dayKey])) {
                $result['day'] = (int) $match->slots[$dayKey];
            }
        }

        // Extract month
        if (isset($slots['month'])) {
            $monthKey = $this->findSlotKey($match, $slots['month']);
            if ($monthKey && isset($match->slots[$monthKey])) {
                $monthValue = $match->slots[$monthKey];

                // Convert month name to number if needed
                if (isset($slots['month']['lookup']) && $slots['month']['lookup'] === 'month_to_number') {
                    $result['month'] = $this->monthToNumber($monthValue);
                } else {
                    $result['month'] = is_numeric($monthValue) ? (int) $monthValue : $this->monthToNumber($monthValue);
                }
            }
        }

        // Extract year (optional)
        if (isset($slots['year'])) {
            $yearKey = $this->findSlotKey($match, $slots['year']);
            if ($yearKey && isset($match->slots[$yearKey])) {
                $result['year'] = (int) $match->slots[$yearKey];
            }
        }

        // Format output based on configuration
        $output = $semantics['output'] ?? 'array';

        return match ($output) {
            'iso' => $this->formatISO($result),
            'timestamp' => $this->formatTimestamp($result),
            default => $result,
        };
    }

    public function deriveFeatures(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $features = [];

        if (isset($value['day'])) {
            $features['Day'] = $value['day'];
        }

        if (isset($value['month'])) {
            $features['Month'] = $value['month'];
        }

        if (isset($value['year'])) {
            $features['Year'] = $value['year'];
        }

        return $features;
    }

    public function validateSemantics(array $semantics): array
    {
        $errors = [];

        if (! isset($semantics['slots'])) {
            $errors[] = 'Date semantics must specify "slots" configuration';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Find slot key from configuration
     */
    private function findSlotKey(ConstructionMatch $match, array $config): ?string
    {
        // If config specifies extract index
        if (isset($config['extract'])) {
            // Find slot by pattern position
            $index = $config['extract'];
            $keys = array_keys($match->slots);

            return $keys[$index] ?? null;
        }

        // Otherwise try to match by slot name
        foreach ($match->slots as $key => $value) {
            if (str_contains($key, 'month') || str_contains($key, 'day') || str_contains($key, 'year')) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Convert Portuguese month name to number
     */
    private function monthToNumber(string $month): ?int
    {
        $month = mb_strtolower(trim($month));

        return self::MONTHS[$month] ?? null;
    }

    /**
     * Format as ISO date string
     */
    private function formatISO(array $date): string
    {
        $year = $date['year'] ?? date('Y');
        $month = str_pad($date['month'] ?? 1, 2, '0', STR_PAD_LEFT);
        $day = str_pad($date['day'] ?? 1, 2, '0', STR_PAD_LEFT);

        return "$year-$month-$day";
    }

    /**
     * Format as Unix timestamp
     */
    private function formatTimestamp(array $date): int
    {
        $year = $date['year'] ?? date('Y');
        $month = $date['month'] ?? 1;
        $day = $date['day'] ?? 1;

        return mktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * Get all month names (for testing/debugging)
     */
    public static function getMonthNames(): array
    {
        return self::MONTHS;
    }
}
