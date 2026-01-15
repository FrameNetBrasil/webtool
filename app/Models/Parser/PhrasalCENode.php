<?php

namespace App\Models\Parser;

use App\Enums\Parser\PhrasalCE;

/**
 * Phrasal CE Node - Stage 1 (Transcription) Output
 *
 * Represents a word-level element classified with its phrasal CE type.
 * Biological analogy: Amino acid with chemical properties (features).
 */
class PhrasalCENode
{
    public function __construct(
        public string $word,
        public string $lemma,
        public string $pos,
        public PhrasalCE $phrasalCE,
        public array $features,
        public int $index,
        public float $activation = 1.0,
        public float $threshold = 1.0,
        public bool $isMWE = false,
        public ?int $idLemma = null,
        public ?int $idParserNode = null,
        public ?string $deprel = null,
        public ?int $head = null,
    ) {}

    /**
     * Create from UD token data
     *
     * Supports both standard UD format (deprel/head) and Trankit format (rel/parent)
     */
    public static function fromUDToken(array $token, ?int $idLemma = null): self
    {
        $pos = $token['pos'] ?? 'X';
        $morphFeatures = $token['morph'] ?? [];

        // Support both Trankit (rel/parent) and standard UD (deprel/head) formats
        $deprel = $token['deprel'] ?? $token['rel'] ?? null;
        $head = $token['head'] ?? $token['parent'] ?? null;

        // Parse morph features if they're a string
        $parsedFeatures = is_string($morphFeatures)
            ? self::parseMorphString($morphFeatures)
            : $morphFeatures;

        return new self(
            word: $token['word'],
            lemma: $token['lemma'] ?? $token['word'],
            pos: $pos,
            phrasalCE: PhrasalCE::fromPOS($pos, $parsedFeatures),
            features: self::buildFeatureBundle($parsedFeatures),
            index: $token['id'],
            idLemma: $idLemma,
            deprel: $deprel,
            head: $head !== null ? (int) $head : null,
        );
    }

    /**
     * Create an MWE node from component nodes
     *
     * @param  array  $components  Array of PhrasalCENode components
     * @param  int  $threshold  Number of components required for complete MWE
     * @param  string|null  $pos  POS tag from lexicon (if null, uses first component's POS)
     */
    public static function fromMWEComponents(array $components, int $threshold, ?string $pos = null): self
    {
        if (empty($components)) {
            throw new \InvalidArgumentException('MWE must have at least one component');
        }

        $firstNode = $components[0];
        $lastNode = $components[count($components) - 1];

        // Combine words with caret separator
        $combinedWord = implode('^', array_map(fn ($n) => $n->word, $components));
        $combinedLemma = implode('^', array_map(fn ($n) => $n->lemma, $components));

        // MWE inherits features from the head (typically first or last component)
        // Using first component as the head for now
        $headFeatures = $firstNode->features;

        // Use POS from lexicon if provided, otherwise fallback to first component
        $mwePos = $pos ?? $firstNode->pos;

        return new self(
            word: $combinedWord,
            lemma: $combinedLemma,
            pos: $mwePos,
            phrasalCE: PhrasalCE::fromPOS($mwePos, $headFeatures['lexical'] ?? []),
            features: $headFeatures,
            index: $firstNode->index,
            activation: count($components),
            threshold: $threshold,
            isMWE: true,
            deprel: $firstNode->deprel,
            head: $firstNode->head,
        );
    }

    /**
     * Build feature bundle from UD morphological features
     */
    private static function buildFeatureBundle(array $morphFeatures): array
    {
        $features = [
            'lexical' => [],
            'derived' => [],
        ];

        // Extract features from morph string if it's a string
        if (is_string($morphFeatures)) {
            $morphFeatures = self::parseMorphString($morphFeatures);
        }

        // Map UD features to our feature categories
        $lexicalFeatures = [
            'Gender', 'Number', 'Case', 'Person', 'Mood', 'Tense',
            'Aspect', 'Voice', 'VerbForm', 'Definite', 'Degree',
            'PronType', 'Poss', 'Reflex', 'NumType', 'Polarity',
        ];

        foreach ($morphFeatures as $featureName => $featureValue) {
            if (in_array($featureName, $lexicalFeatures)) {
                $features['lexical'][$featureName] = $featureValue;
            }
        }

        return $features;
    }

    /**
     * Parse UD morph string format (e.g., "Gender=Masc|Number=Sing")
     */
    private static function parseMorphString(string $morphString): array
    {
        $features = [];

        if (empty($morphString) || $morphString === '_') {
            return $features;
        }

        $pairs = explode('|', $morphString);
        foreach ($pairs as $pair) {
            $parts = explode('=', $pair);
            if (count($parts) === 2) {
                $features[$parts[0]] = $parts[1];
            }
        }

        return $features;
    }

    /**
     * Check if this node has reached its activation threshold
     */
    public function hasReachedThreshold(): bool
    {
        return $this->activation >= $this->threshold;
    }

    /**
     * Get a specific lexical feature
     */
    public function getFeature(string $name): ?string
    {
        return $this->features['lexical'][$name] ?? null;
    }

    /**
     * Check if node has a specific feature with value
     */
    public function hasFeature(string $name, ?string $value = null): bool
    {
        $featureValue = $this->getFeature($name);

        if ($featureValue === null) {
            return false;
        }

        if ($value === null) {
            return true;
        }

        return $featureValue === $value;
    }

    /**
     * Get all lexical features
     */
    public function getLexicalFeatures(): array
    {
        return $this->features['lexical'] ?? [];
    }

    /**
     * Get all derived features
     */
    public function getDerivedFeatures(): array
    {
        return $this->features['derived'] ?? [];
    }

    /**
     * Check if this is a finite verb
     */
    public function isFiniteVerb(): bool
    {
        return $this->pos === 'VERB' && $this->hasFeature('VerbForm', 'Fin');
    }

    /**
     * Check if this is a noun-like element
     */
    public function isNominal(): bool
    {
        return in_array($this->pos, ['NOUN', 'PROPN', 'PRON']);
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'word' => $this->word,
            'lemma' => $this->lemma,
            'pos' => $this->pos,
            'phrasal_ce' => $this->phrasalCE->value,
            'features' => json_encode($this->features),
            'positionInSentence' => $this->index,
            'activation' => $this->activation,
            'threshold' => $this->threshold,
            'idLemma' => $this->idLemma,
            'deprel' => $this->deprel,
            'head' => $this->head,
        ];
    }
}
