<?php

namespace App\Rules;

use App\Enums\Parser\MWEComponentType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates MWE component arrays for both simple and extended formats.
 *
 * Simple format: Array of strings (word forms)
 * Extended format: Array of objects with type and value properties
 */
class ValidMWEComponents implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('The :attribute must be an array.');

            return;
        }

        if (count($value) < 2) {
            $fail('MWE must have at least 2 components.');

            return;
        }

        // Detect format from first element
        $firstComponent = $value[0];
        $isExtended = is_array($firstComponent);

        foreach ($value as $index => $component) {
            // Simple format: string
            if (! $isExtended) {
                if (! is_string($component)) {
                    $fail("Component {$index} must be a string in simple format.");

                    return;
                }
                if (strlen($component) === 0) {
                    $fail("Component {$index} cannot be empty.");

                    return;
                }

                continue;
            }

            // Extended format: array with type and value
            if (! is_array($component)) {
                $fail("Component {$index} must be an array in extended format.");

                return;
            }

            if (! isset($component['type'])) {
                $fail("Component {$index} is missing 'type' field.");

                return;
            }

            $validTypes = ['W', 'L', 'P', 'C', '*'];
            if (! in_array($component['type'], $validTypes)) {
                $fail("Component {$index} has invalid type '{$component['type']}'. Valid types: ".implode(', ', $validTypes));

                return;
            }

            // Wildcard doesn't need value
            if ($component['type'] === '*') {
                continue;
            }

            if (! isset($component['value']) || empty($component['value'])) {
                $fail("Component {$index} is missing 'value' field.");

                return;
            }

            // Validate type-specific values
            $type = MWEComponentType::from($component['type']);
            if (! $type->validateValue($component['value'])) {
                $errorMsg = match ($component['type']) {
                    'P' => "Component {$index} has invalid POS '{$component['value']}'. Valid POS: ".implode(', ', MWEComponentType::validPOSTags()),
                    'C' => "Component {$index} has invalid CE '{$component['value']}'. Valid CE: ".implode(', ', MWEComponentType::validCELabels()),
                    default => "Component {$index} has invalid value.",
                };
                $fail($errorMsg);

                return;
            }
        }
    }
}
