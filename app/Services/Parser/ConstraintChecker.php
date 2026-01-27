<?php

namespace App\Services\Parser;

use App\Data\Parser\AlternativeState;
use App\Data\Parser\ConstructionDefinition;

/**
 * Constraint Checker Service
 *
 * Validates feature constraints for construction patterns.
 * Checks:
 * - Feature equality (e.g., VerbForm == "Fin")
 * - Feature inclusion (e.g., Mood IN ["Ind", "Sub", "Imp"])
 * - Feature agreement (e.g., Number agrees between subject and verb)
 * - POS tag matching
 * - Lemma matching
 *
 * Constraint types:
 * - feature_equals: Feature must equal specific value
 * - feature_in: Feature must be in a list of values
 * - feature_not: Feature must not equal specific value
 * - agrees_with: Feature must agree with another element
 * - pos_is: POS tag must match
 * - pos_in: POS tag must be in list
 * - lemma_is: Lemma must match
 * - lemma_in: Lemma must be in list
 */
class ConstraintChecker
{
    /**
     * Check all constraints for a construction with a token
     *
     * @param  ConstructionDefinition  $construction  The construction being matched
     * @param  AlternativeState  $alternative  Current alternative state
     * @param  object  $token  The token to check
     * @return array ['valid' => bool, 'violations' => array]
     */
    public function checkConstraints(
        ConstructionDefinition $construction,
        AlternativeState $alternative,
        object $token
    ): array {
        $violations = [];

        foreach ($construction->constraints as $constraint) {
            $result = $this->evaluateConstraint($constraint, $alternative, $token);

            if (! $result['valid']) {
                $violations[] = [
                    'type' => $constraint['type'] ?? 'unknown',
                    'reason' => $result['reason'] ?? 'Constraint violated',
                    'constraint' => $constraint,
                    'token' => $token->word ?? $token->form ?? '',
                ];
            }
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Evaluate a single constraint
     *
     * @param  array  $constraint  The constraint definition
     * @param  AlternativeState  $alternative  Current alternative state
     * @param  object  $token  The token to check
     * @return array ['valid' => bool, 'reason' => string]
     */
    private function evaluateConstraint(
        array $constraint,
        AlternativeState $alternative,
        object $token
    ): array {
        $type = $constraint['type'] ?? '';

        return match ($type) {
            'feature_equals' => $this->checkFeatureEquals($constraint, $token),
            'feature_in' => $this->checkFeatureIn($constraint, $token),
            'feature_not' => $this->checkFeatureNot($constraint, $token),
            'agrees_with' => $this->checkAgreement($constraint, $alternative, $token),
            'pos_is' => $this->checkPOS($constraint, $token),
            'pos_in' => $this->checkPOSIn($constraint, $token),
            'lemma_is' => $this->checkLemma($constraint, $token),
            'lemma_in' => $this->checkLemmaIn($constraint, $token),
            default => [
                'valid' => true,
                'reason' => "Unknown constraint type: {$type}",
            ],
        };
    }

    /**
     * Check feature_equals constraint
     * Example: {"type": "feature_equals", "element": 0, "feature": "VerbForm", "value": "Fin"}
     */
    private function checkFeatureEquals(array $constraint, object $token): array
    {
        $feature = $constraint['feature'] ?? '';
        $expectedValue = $constraint['value'] ?? '';

        $tokenFeature = $this->getTokenFeature($token, $feature);

        if ($tokenFeature === null) {
            // Feature not present - may be acceptable depending on context
            return ['valid' => true, 'reason' => "Feature {$feature} not present"];
        }

        $valid = $tokenFeature === $expectedValue;

        return [
            'valid' => $valid,
            'reason' => $valid ? '' : "Feature {$feature}={$tokenFeature}, expected {$expectedValue}",
        ];
    }

    /**
     * Check feature_in constraint
     * Example: {"type": "feature_in", "element": 0, "feature": "Mood", "values": ["Ind", "Sub", "Imp"]}
     */
    private function checkFeatureIn(array $constraint, object $token): array
    {
        $feature = $constraint['feature'] ?? '';
        $allowedValues = $constraint['values'] ?? [];

        $tokenFeature = $this->getTokenFeature($token, $feature);

        if ($tokenFeature === null) {
            return ['valid' => true, 'reason' => "Feature {$feature} not present"];
        }

        $valid = in_array($tokenFeature, $allowedValues);

        return [
            'valid' => $valid,
            'reason' => $valid ? '' : "Feature {$feature}={$tokenFeature}, expected one of: ".implode(', ', $allowedValues),
        ];
    }

    /**
     * Check feature_not constraint
     * Example: {"type": "feature_not", "element": 0, "feature": "VerbForm", "value": "Inf"}
     */
    private function checkFeatureNot(array $constraint, object $token): array
    {
        $feature = $constraint['feature'] ?? '';
        $forbiddenValue = $constraint['value'] ?? '';

        $tokenFeature = $this->getTokenFeature($token, $feature);

        if ($tokenFeature === null) {
            return ['valid' => true, 'reason' => "Feature {$feature} not present"];
        }

        $valid = $tokenFeature !== $forbiddenValue;

        return [
            'valid' => $valid,
            'reason' => $valid ? '' : "Feature {$feature}={$tokenFeature}, must not be {$forbiddenValue}",
        ];
    }

    /**
     * Check agreement constraint
     * Example: {"type": "agrees_with", "element": "HEAD_NOUN", "feature": "Number", "target": "PRED"}
     */
    private function checkAgreement(
        array $constraint,
        AlternativeState $alternative,
        object $token
    ): array {
        $feature = $constraint['feature'] ?? '';
        $targetElement = $constraint['target'] ?? '';

        // Find the target element in matched components
        $agreementTarget = $this->findMatchedComponent($alternative, $targetElement);

        if (! $agreementTarget) {
            // Target not yet matched, defer check
            return ['valid' => true, 'reason' => "Agreement target {$targetElement} not yet matched"];
        }

        $tokenFeature = $this->getTokenFeature($token, $feature);
        $targetFeature = $this->getTokenFeature($agreementTarget, $feature);

        if ($tokenFeature === null || $targetFeature === null) {
            // One or both features missing - can't check agreement
            return ['valid' => true, 'reason' => "Feature {$feature} missing for agreement check"];
        }

        $valid = $tokenFeature === $targetFeature;

        return [
            'valid' => $valid,
            'reason' => $valid ? '' : "Feature {$feature}: token={$tokenFeature}, target={$targetFeature} (no agreement)",
        ];
    }

    /**
     * Check POS constraint
     * Example: {"type": "pos_is", "value": "NOUN"}
     */
    private function checkPOS(array $constraint, object $token): array
    {
        $expectedPOS = $constraint['value'] ?? '';
        $tokenPOS = $token->upos ?? $token->pos ?? '';

        $valid = $tokenPOS === $expectedPOS;

        return [
            'valid' => $valid,
            'reason' => $valid ? '' : "POS={$tokenPOS}, expected {$expectedPOS}",
        ];
    }

    /**
     * Check POS in list constraint
     * Example: {"type": "pos_in", "values": ["NOUN", "PROPN", "PRON"]}
     */
    private function checkPOSIn(array $constraint, object $token): array
    {
        $allowedPOS = $constraint['values'] ?? [];
        $tokenPOS = $token->upos ?? $token->pos ?? '';

        $valid = in_array($tokenPOS, $allowedPOS);

        return [
            'valid' => $valid,
            'reason' => $valid ? '' : "POS={$tokenPOS}, expected one of: ".implode(', ', $allowedPOS),
        ];
    }

    /**
     * Check lemma constraint
     * Example: {"type": "lemma_is", "value": "ser"}
     */
    private function checkLemma(array $constraint, object $token): array
    {
        $expectedLemma = strtolower($constraint['value'] ?? '');
        $tokenLemma = strtolower($token->lemma ?? '');

        $valid = $tokenLemma === $expectedLemma;

        return [
            'valid' => $valid,
            'reason' => $valid ? '' : "Lemma={$tokenLemma}, expected {$expectedLemma}",
        ];
    }

    /**
     * Check lemma in list constraint
     * Example: {"type": "lemma_in", "values": ["ser", "estar", "ficar"]}
     */
    private function checkLemmaIn(array $constraint, object $token): array
    {
        $allowedLemmas = array_map('strtolower', $constraint['values'] ?? []);
        $tokenLemma = strtolower($token->lemma ?? '');

        $valid = in_array($tokenLemma, $allowedLemmas);

        return [
            'valid' => $valid,
            'reason' => $valid ? '' : "Lemma={$tokenLemma}, expected one of: ".implode(', ', $allowedLemmas),
        ];
    }

    /**
     * Get a feature value from a token
     */
    private function getTokenFeature(object $token, string $feature): ?string
    {
        // Check if token has features array
        if (isset($token->features) && is_array($token->features)) {
            return $token->features[$feature] ?? null;
        }

        // Check if token has features as object properties
        if (property_exists($token, $feature)) {
            return $token->$feature;
        }

        // Check common feature locations
        if (isset($token->feats)) {
            if (is_array($token->feats)) {
                return $token->feats[$feature] ?? null;
            }
            if (is_string($token->feats)) {
                // Parse UD format: "Gender=Masc|Number=Sing"
                return $this->parseUDFeatures($token->feats)[$feature] ?? null;
            }
        }

        return null;
    }

    /**
     * Parse UD format features string
     * Example: "Gender=Masc|Number=Sing" => ['Gender' => 'Masc', 'Number' => 'Sing']
     */
    private function parseUDFeatures(string $featsString): array
    {
        $features = [];

        if (empty($featsString) || $featsString === '_') {
            return $features;
        }

        $pairs = explode('|', $featsString);
        foreach ($pairs as $pair) {
            if (str_contains($pair, '=')) {
                [$key, $value] = explode('=', $pair, 2);
                $features[trim($key)] = trim($value);
            }
        }

        return $features;
    }

    /**
     * Find a matched component in the alternative
     */
    private function findMatchedComponent(AlternativeState $alternative, string $elementName): ?object
    {
        // For now, return null - actual implementation would need pattern analysis
        // to know which component corresponds to which element name
        // TODO: Implement element name to component matching
        return null;
    }

    /**
     * Quick check if a token can potentially match a construction
     * Used for early filtering before full constraint checking
     */
    public function canTokenMatch(ConstructionDefinition $construction, object $token): bool
    {
        // Check if pattern contains token's POS
        $pos = $token->upos ?? $token->pos ?? '';
        if (str_contains($construction->pattern, "{{$pos}}")) {
            return true;
        }

        // Check if pattern contains token's word or lemma
        $word = strtolower($token->word ?? $token->form ?? '');
        $lemma = strtolower($token->lemma ?? '');

        if (str_contains($construction->pattern, "\"{$word}\"") ||
            str_contains($construction->pattern, "\"{$lemma}\"")) {
            return true;
        }

        // For higher-level constructions (referencing other constructions), allow
        if (preg_match('/^[A-Z_]+/', $construction->pattern)) {
            return true;
        }

        return false;
    }
}
