<?php

namespace App\Services\Parser\V4;

/**
 * Agreement Checker for Parser V4
 *
 * Validates feature agreement between parse nodes.
 * Supports checking agreement on morphological features like:
 * - Gender (Masc, Fem, Neut)
 * - Number (Sing, Plur)
 * - Person (1, 2, 3)
 * - Case (Nom, Acc, Dat, Gen, etc.)
 *
 * Used by LinkBuilder to ensure grammatical correctness when
 * creating dependency links between nodes.
 */
class AgreementChecker
{
    /**
     * Check if two nodes agree on specified features
     *
     * @param  array  $sourceFeatures  Features of the source node
     * @param  array  $targetFeatures  Features of the target node
     * @param  array  $requiredFeatures  List of features that must agree
     * @return array Result with 'agrees' boolean and 'mismatches' array
     */
    public function checkAgreement(
        array $sourceFeatures,
        array $targetFeatures,
        array $requiredFeatures
    ): array {
        $mismatches = [];

        foreach ($requiredFeatures as $feature) {
            $sourceValue = $sourceFeatures[$feature] ?? null;
            $targetValue = $targetFeatures[$feature] ?? null;

            // If either value is missing, assume agreement
            // (features might not be specified for all tokens)
            if ($sourceValue === null || $targetValue === null) {
                continue;
            }

            // Check if values match
            if (! $this->valuesMatch($sourceValue, $targetValue, $feature)) {
                $mismatches[] = [
                    'feature' => $feature,
                    'sourceValue' => $sourceValue,
                    'targetValue' => $targetValue,
                ];
            }
        }

        return [
            'agrees' => empty($mismatches),
            'mismatches' => $mismatches,
        ];
    }

    /**
     * Check if two feature values match
     *
     * Handles special cases:
     * - Multi-valued features (e.g., "Masc,Fem")
     * - Underspecified features
     *
     * @param  mixed  $value1  First feature value
     * @param  mixed  $value2  Second feature value
     * @param  string  $feature  The feature name
     * @return bool Whether the values match
     */
    private function valuesMatch($value1, $value2, string $feature): bool
    {
        // Exact match
        if ($value1 === $value2) {
            return true;
        }

        // Handle multi-valued features (e.g., "Masc,Fem" matches "Masc")
        if (is_string($value1) && str_contains($value1, ',')) {
            $values1 = explode(',', $value1);
            if (in_array($value2, $values1)) {
                return true;
            }
        }

        if (is_string($value2) && str_contains($value2, ',')) {
            $values2 = explode(',', $value2);
            if (in_array($value1, $values2)) {
                return true;
            }
        }

        // Check for compatible gender values
        if ($feature === 'Gender') {
            return $this->gendersCompatible($value1, $value2);
        }

        // Check for compatible number values
        if ($feature === 'Number') {
            return $this->numbersCompatible($value1, $value2);
        }

        // No match
        return false;
    }

    /**
     * Check if two gender values are compatible
     *
     * In some languages, certain genders can be compatible
     * (e.g., common gender with masculine/feminine)
     */
    private function gendersCompatible($gender1, $gender2): bool
    {
        // Common gender is compatible with Masc or Fem
        if ($gender1 === 'Com' && in_array($gender2, ['Masc', 'Fem'])) {
            return true;
        }

        if ($gender2 === 'Com' && in_array($gender1, ['Masc', 'Fem'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if two number values are compatible
     *
     * In some cases, plural can be compatible with singular
     * (e.g., collective nouns)
     */
    private function numbersCompatible($number1, $number2): bool
    {
        // For now, require exact match
        // TODO: Add support for collective nouns if needed
        return false;
    }

    /**
     * Check agreement between multiple nodes
     *
     * Useful for checking agreement across a chain of nodes
     * (e.g., determiner -> adjective -> noun)
     *
     * @param  array  $nodes  Array of nodes to check
     * @param  array  $requiredFeatures  Features that must agree across all nodes
     * @return array Result with 'agrees' boolean and 'violations' array
     */
    public function checkMultiNodeAgreement(array $nodes, array $requiredFeatures): array
    {
        if (count($nodes) < 2) {
            return ['agrees' => true, 'violations' => []];
        }

        $violations = [];
        $referenceFeatures = $nodes[0]['features'] ?? [];

        for ($i = 1; $i < count($nodes); $i++) {
            $nodeFeatures = $nodes[$i]['features'] ?? [];
            $result = $this->checkAgreement($referenceFeatures, $nodeFeatures, $requiredFeatures);

            if (! $result['agrees']) {
                $violations[] = [
                    'nodeIndex' => $i,
                    'mismatches' => $result['mismatches'],
                ];
            }
        }

        return [
            'agrees' => empty($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Get required agreement features for a specific construction pattern
     *
     * Different construction types require different feature agreement:
     * - NP: Gender, Number agreement between Det/Adj/Noun
     * - Subject-Verb: Number, Person agreement
     * - etc.
     *
     * @param  string  $constructionType  The construction type
     * @return array List of features required to agree
     */
    public function getRequiredFeaturesForConstruction(string $constructionType): array
    {
        return match ($constructionType) {
            'NP' => ['Gender', 'Number'],
            'subject_verb' => ['Number', 'Person'],
            'adj_noun' => ['Gender', 'Number'],
            'det_noun' => ['Gender', 'Number'],
            default => [],
        };
    }

    /**
     * Validate that a feature value is well-formed
     *
     * @param  string  $feature  The feature name
     * @param  mixed  $value  The feature value
     * @return bool Whether the value is valid for this feature
     */
    public function isValidFeatureValue(string $feature, $value): bool
    {
        if ($value === null) {
            return true; // Null is always valid (feature not specified)
        }

        $validValues = match ($feature) {
            'Gender' => ['Masc', 'Fem', 'Neut', 'Com'],
            'Number' => ['Sing', 'Plur', 'Dual', 'Ptan', 'Coll'],
            'Person' => ['1', '2', '3'],
            'Case' => ['Nom', 'Acc', 'Dat', 'Gen', 'Loc', 'Ins', 'Abl', 'Voc'],
            'Tense' => ['Past', 'Pres', 'Fut', 'Imp', 'Pqp'],
            'VerbForm' => ['Fin', 'Inf', 'Ger', 'Part', 'Sup', 'Conv'],
            'Mood' => ['Ind', 'Sub', 'Imp', 'Cnd'],
            'Voice' => ['Act', 'Pass', 'Mid'],
            'Aspect' => ['Perf', 'Imp', 'Prog'],
            'Polarity' => ['Pos', 'Neg'],
            'Definite' => ['Def', 'Ind'],
            'PronType' => ['Prs', 'Dem', 'Int', 'Rel', 'Ind', 'Tot', 'Neg'],
            'Poss' => ['Yes'],
            default => null, // Unknown feature - allow any value
        };

        if ($validValues === null) {
            return true; // Unknown feature - don't validate
        }

        // Handle multi-valued features
        if (is_string($value) && str_contains($value, ',')) {
            $values = explode(',', $value);
            foreach ($values as $v) {
                if (! in_array($v, $validValues)) {
                    return false;
                }
            }

            return true;
        }

        return in_array($value, $validValues);
    }

    /**
     * Extract features from a token object
     *
     * @param  object  $token  The token object (from Trankit)
     * @return array Associative array of feature names to values
     */
    public function extractFeaturesFromToken(object $token): array
    {
        $features = [];

        if (isset($token->feats) && is_string($token->feats)) {
            // Parse features string (e.g., "Gender=Masc|Number=Sing")
            $pairs = explode('|', $token->feats);
            foreach ($pairs as $pair) {
                if (str_contains($pair, '=')) {
                    [$feature, $value] = explode('=', $pair, 2);
                    $features[$feature] = $value;
                }
            }
        }

        return $features;
    }
}
