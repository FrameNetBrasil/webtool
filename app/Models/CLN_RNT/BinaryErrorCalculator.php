<?php

namespace App\Models\CLN_RNT;

class BinaryErrorCalculator
{
    public function calculateError(mixed $element1, mixed $element2): float
    {
        if ($element1 === null || $element2 === null) {
            return 0.0;
        }

        if ($this->matchElements($element1, $element2)) {
            return 0.0;
        } else {
            return 1.0;
        }
    }

    private function matchElements(mixed $element1, mixed $element2): bool
    {
        if ($this->getType($element1) !== $this->getType($element2)) {
            return false;
        }

        if ($this->getValue($element1) !== $this->getValue($element2)) {
            return false;
        }

        if (! $this->matchFeatures($element1, $element2)) {
            return false;
        }

        return true;
    }

    private function getType(mixed $element): ?string
    {
        if (is_array($element)) {
            return $element['type'] ?? $element['node_type'] ?? null;
        }
        if (is_object($element) && isset($element->type)) {
            return $element->type;
        }
        if (is_object($element) && isset($element->node_type)) {
            return $element->node_type;
        }

        return null;
    }

    private function getValue(mixed $element): ?string
    {
        if (is_array($element)) {
            return $element['value'] ?? $element['element_value'] ?? null;
        }
        if (is_object($element) && isset($element->value)) {
            return $element->value;
        }
        if (is_object($element) && isset($element->element_value)) {
            return $element->element_value;
        }

        return null;
    }

    private function matchFeatures(mixed $element1, mixed $element2): bool
    {
        $features1 = $this->getFeatures($element1);
        $features2 = $this->getFeatures($element2);

        if (empty($features2)) {
            return true;
        }

        foreach ($features2 as $key => $value) {
            if (! isset($features1[$key]) || $features1[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    private function getFeatures(mixed $element): array
    {
        if (is_array($element) && isset($element['features'])) {
            return $element['features'];
        }
        if (is_object($element) && isset($element->features)) {
            return (array) $element->features;
        }

        return [];
    }
}
