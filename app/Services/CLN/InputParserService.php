<?php

namespace App\Services\CLN;

use App\Services\Trankit\TrankitService;

/**
 * Input Parser Service for CLN v3
 *
 * Parses input sentences using Trankit UD parser and extracts
 * linguistic features for L1 node creation.
 *
 * Features extracted per word:
 * - Word form (surface form)
 * - Lemma
 * - POS (Part-of-Speech)
 * - Morphological features (gender, number, tense, etc.)
 * - Dependency relation
 * - Head/parent information
 */
class InputParserService
{
    private TrankitService $trankitService;

    public function __construct()
    {
        $this->trankitService = new TrankitService;
        $this->trankitService->init(config('udparser.trankit_url'));
    }

    /**
     * Parse sentence and extract word features for L1 nodes
     *
     * @param  string  $sentence  Input sentence
     * @param  int  $idLanguage  Language ID (1=Portuguese, 2=English)
     * @return array Array of word data for L1 node creation
     */
    public function parseForL1Nodes(string $sentence, int $idLanguage = 1): array
    {
        // Parse with Trankit using full text method (preserves contractions)
        $result = $this->trankitService->getUDTrankitText($sentence, $idLanguage);

        if (empty($result->udpipe)) {
            return [];
        }

        $words = [];
        $position = 0;

        foreach ($result->udpipe as $token) {
            $words[] = [
                'position' => $position,
                'word' => $token['word'],
                'lemma' => $token['lemma'],
                'pos' => $token['pos'],
                'morph' => $token['morph'],
                'deprel' => $token['rel'],
                'head' => $token['parent'],
                'children' => $token['children'],
                'features' => $this->extractFeatures($token),
            ];
            $position++;
        }

        return $words;
    }

    /**
     * Extract features from UD parse token for L1 node
     *
     * Creates a feature map suitable for Column features.
     * Includes word form, lemma, POS, and selected morphological features.
     *
     * @param  array  $token  UD parse token
     * @return array Feature map for Column
     */
    private function extractFeatures(array $token): array
    {
        $features = [
            'type' => 'LITERAL',
            'value' => $token['word'],
            'lemma' => $token['lemma'],
            'pos' => $token['pos'],
            'deprel' => $token['rel'],
        ];

        // Add key morphological features if available
        if (! empty($token['morph'])) {
            // Gender (Masc, Fem, Neut)
            if (isset($token['morph']['Gender'])) {
                $features['gender'] = $token['morph']['Gender'];
            }

            // Number (Sing, Plur)
            if (isset($token['morph']['Number'])) {
                $features['number'] = $token['morph']['Number'];
            }

            // Tense (Past, Pres, Fut, etc.)
            if (isset($token['morph']['Tense'])) {
                $features['tense'] = $token['morph']['Tense'];
            }

            // Person (1, 2, 3)
            if (isset($token['morph']['Person'])) {
                $features['person'] = $token['morph']['Person'];
            }

            // VerbForm (Fin, Inf, Part, Ger)
            if (isset($token['morph']['VerbForm'])) {
                $features['verbform'] = $token['morph']['VerbForm'];
            }
        }

        return $features;
    }

    /**
     * Get simple word list from sentence (for backward compatibility)
     *
     * @param  string  $sentence  Input sentence
     * @return array Simple array of words
     */
    public function getWords(string $sentence): array
    {
        $parsed = $this->parseForL1Nodes($sentence);

        return array_map(fn ($item) => $item['word'], $parsed);
    }

    /**
     * Check if parser service is available
     *
     * @return bool True if Trankit service is configured and accessible
     */
    public function isAvailable(): bool
    {
        $url = config('udparser.trankit_url');

        return ! empty($url);
    }
}
