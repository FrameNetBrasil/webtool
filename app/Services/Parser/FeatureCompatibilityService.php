<?php

namespace App\Services\Parser;

/**
 * Feature Compatibility Service
 *
 * Calculates compatibility scores between nodes based on morphological features.
 * Implements feature-as-chemical-bonds analogy:
 * - Agreement features (Gender, Number) = Hydrogen bonds
 * - Case features = Ionic bonds
 * - Definiteness = Hydrophobic effect
 *
 * Language-specific profiles weight features differently:
 * - Portuguese: Strong agreement (Gender, Number)
 * - English: Position + weak agreement
 * - Russian: Strong case system
 */
class FeatureCompatibilityService
{
    /**
     * Calculate compatibility score between two nodes
     *
     * @param  object  $sourceNode  Node initiating the link
     * @param  object  $targetNode  Node receiving the link
     * @param  string  $language  Language code (pt, en, etc.)
     * @return array ['score' => float, 'matches' => array, 'mismatches' => array]
     */
    public function calculateCompatibility(
        object $sourceNode,
        object $targetNode,
        string $language = 'pt'
    ): array {
        // Get language profile
        $profile = $this->getLanguageProfile($language);

        // Extract features from both nodes
        $sourceFeatures = $this->extractFeatures($sourceNode);
        $targetFeatures = $this->extractFeatures($targetNode);

        $matches = [];
        $mismatches = [];
        $totalScore = 0.0;
        $totalWeight = 0.0;

        // Check agreement features (Gender, Number, Person)
        foreach ($profile['agreementFeatures'] as $feature => $weight) {
            $result = $this->compareFeature($feature, $sourceFeatures, $targetFeatures);

            if ($result['status'] === 'match') {
                $matches[$feature] = $result['value'];
                $totalScore += $weight;
            } elseif ($result['status'] === 'mismatch') {
                $mismatches[$feature] = [
                    'source' => $result['source'],
                    'target' => $result['target'],
                ];
                // Mismatches reduce score
                $totalScore -= $weight * 0.5;
            }
            // 'not_applicable' doesn't affect score

            $totalWeight += $weight;
        }

        // Check case features
        foreach ($profile['caseFeatures'] as $feature => $weight) {
            $result = $this->compareFeature($feature, $sourceFeatures, $targetFeatures);

            if ($result['status'] === 'match') {
                $matches[$feature] = $result['value'];
                $totalScore += $weight;
            } elseif ($result['status'] === 'mismatch') {
                $mismatches[$feature] = [
                    'source' => $result['source'],
                    'target' => $result['target'],
                ];
                $totalScore -= $weight * 0.3;
            }

            $totalWeight += $weight;
        }

        // Normalize score (0 to 1)
        $normalizedScore = $totalWeight > 0 ? max(0, $totalScore / $totalWeight) : 0.5;

        // Apply type compatibility bonus
        $typeBonus = $this->getTypeCompatibilityBonus($sourceNode->type, $targetNode->type);
        $finalScore = min(1.0, $normalizedScore + $typeBonus);

        if (config('parser.logging.logFeatures', false)) {
            logger()->info('Feature Compatibility', [
                'source' => $sourceNode->label,
                'target' => $targetNode->label,
                'score' => $finalScore,
                'matches' => count($matches),
                'mismatches' => count($mismatches),
            ]);
        }

        return [
            'score' => $finalScore,
            'matches' => $matches,
            'mismatches' => $mismatches,
        ];
    }

    /**
     * Compare a specific feature between two nodes
     */
    private function compareFeature(string $feature, array $sourceFeatures, array $targetFeatures): array
    {
        $sourceValue = $sourceFeatures[$feature] ?? null;
        $targetValue = $targetFeatures[$feature] ?? null;

        // Both have the feature
        if ($sourceValue !== null && $targetValue !== null) {
            if ($sourceValue === $targetValue) {
                return ['status' => 'match', 'value' => $sourceValue];
            } else {
                return ['status' => 'mismatch', 'source' => $sourceValue, 'target' => $targetValue];
            }
        }

        // Feature not present in one or both nodes
        return ['status' => 'not_applicable'];
    }

    /**
     * Check if specific features agree between two nodes
     */
    public function checkAgreement(object $node1, object $node2, array $featuresToCheck): bool
    {
        $features1 = $this->extractFeatures($node1);
        $features2 = $this->extractFeatures($node2);

        foreach ($featuresToCheck as $feature) {
            $value1 = $features1[$feature] ?? null;
            $value2 = $features2[$feature] ?? null;

            // If both have the feature and they don't match, return false
            if ($value1 !== null && $value2 !== null && $value1 !== $value2) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get language profile with feature weights
     */
    public function getLanguageProfile(string $language): array
    {
        $profiles = config('parser.languageProfiles', []);

        return $profiles[$language] ?? [
            'name' => 'Unknown',
            'agreementFeatures' => ['Gender' => 0.5, 'Number' => 0.5],
            'caseFeatures' => ['Case' => 0.5],
            'positionWeight' => 0.5,
            'emphasis' => 'neutral',
        ];
    }

    /**
     * Extract lexical features from node
     */
    private function extractFeatures(object $node): array
    {
        if (empty($node->features)) {
            return [];
        }

        $features = json_decode($node->features, true);

        return $features['lexical'] ?? [];
    }

    /**
     * Get type compatibility bonus
     *
     * Certain type combinations naturally work well together
     */
    private function getTypeCompatibilityBonus(string $sourceType, string $targetType): float
    {
        // Relational (R) → Entity (E): Natural predicate-argument structure
        if ($sourceType === 'R' && $targetType === 'E') {
            return 0.1;
        }

        // Entity (E) → Relational (R): Natural subject-predicate
        if ($sourceType === 'E' && $targetType === 'R') {
            return 0.1;
        }

        // Attribute (A) → Entity (E): Natural modification
        if ($sourceType === 'A' && $targetType === 'E') {
            return 0.1;
        }

        // Entity (E) → Attribute (A): Entity with attribute
        if ($sourceType === 'E' && $targetType === 'A') {
            return 0.05;
        }

        return 0.0;
    }

    /**
     * Calculate distance penalty
     *
     * Closer words are more likely to form phrases
     */
    public function calculateDistancePenalty(int $position1, int $position2, int $maxDistance = 3): float
    {
        $distance = abs($position2 - $position1);

        if ($distance > $maxDistance) {
            return 0.0; // Too far, no link
        }

        // Linear decay: score = 1.0 at distance 1, decreases to 0.0 at maxDistance
        return 1.0 - ($distance - 1) / $maxDistance;
    }

    /**
     * Determine phrase type based on node types and features
     *
     * Returns Croft's phrasal CE labels: Pred, Arg, Mod, FPM, etc.
     */
    public function determinePhraseType(object $headNode): string
    {
        $type = $headNode->type;
        $features = $this->extractFeatures($headNode);

        // Relational types are typically Predicates
        if ($type === 'R') {
            // Check if it's a finite verb (main predicate)
            if (($features['VerbForm'] ?? null) === 'Fin') {
                return 'Pred';
            }

            return 'Rel'; // Other relational elements
        }

        // Entity types are typically Arguments
        if ($type === 'E') {
            return 'Arg';
        }

        // Attribute types are typically Modifiers or Flagged Phrase Modifiers
        if ($type === 'A') {
            return 'FPM'; // Flagged Phrase Modifier (adverbs, adjectives)
        }

        // Function words
        if ($type === 'F') {
            return 'Func';
        }

        return 'Unknown';
    }

    /**
     * Check if link would create valid phrase structure
     *
     * Validates against linguistic constraints
     */
    public function isValidPhraseLink(
        object $sourceNode,
        object $targetNode,
        string $language = 'pt'
    ): bool {
        // Don't link to self
        if ($sourceNode->idParserNode === $targetNode->idParserNode) {
            return false;
        }

        // Check distance constraint
        $maxDistance = config('parser.translation.maxPhraseDistance', 3);
        $distance = abs($targetNode->positionInSentence - $sourceNode->positionInSentence);

        if ($distance > $maxDistance) {
            return false;
        }

        // Check if agreement is required and satisfied
        if (config('parser.translation.requireAgreement', true)) {
            $profile = $this->getLanguageProfile($language);

            // For agreement-heavy languages, require agreement on key features
            if ($profile['emphasis'] === 'agreement') {
                $compatibility = $this->calculateCompatibility($sourceNode, $targetNode, $language);

                // Must meet minimum compatibility score
                $minScore = config('parser.features.minCompatibilityScore', 0.5);
                if ($compatibility['score'] < $minScore) {
                    return false;
                }
            }
        }

        return true;
    }
}
