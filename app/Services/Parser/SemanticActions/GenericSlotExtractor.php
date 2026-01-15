<?php

namespace App\Services\Parser\SemanticActions;

use App\Data\Parser\ConstructionMatch;

/**
 * Generic Slot Extractor
 *
 * Simple semantic action that extracts slot values without transformation.
 * Useful for named entity extraction and simple value capture.
 *
 * Examples:
 * - Extract city name from "cidade de {NAME}"
 * - Extract person name from "{TITLE} {NAME}"
 */
class GenericSlotExtractor implements SemanticAction
{
    public function getName(): string
    {
        return 'slot_extractor';
    }

    public function calculate(ConstructionMatch $match, array $semantics): mixed
    {
        $extract = $semantics['extract'] ?? null;

        if (! $extract) {
            // Return all slots if no specific extraction configured
            return $match->slots;
        }

        // Single slot extraction
        if (is_string($extract)) {
            return $match->slots[$extract] ?? null;
        }

        // Multiple slot extraction
        if (is_array($extract)) {
            $result = [];

            foreach ($extract as $alias => $slotKey) {
                if (is_numeric($alias)) {
                    // Simple list: ['slot1', 'slot2']
                    $result[$slotKey] = $match->slots[$slotKey] ?? null;
                } else {
                    // Aliased: ['name' => 'PROPN', 'title' => 'NOUN']
                    $result[$alias] = $match->slots[$slotKey] ?? null;
                }
            }

            return $result;
        }

        return null;
    }

    public function deriveFeatures(mixed $value): array
    {
        // No additional features derived
        return [];
    }

    public function validateSemantics(array $semantics): array
    {
        // Very permissive - almost any configuration works
        return [
            'valid' => true,
            'errors' => [],
        ];
    }
}
