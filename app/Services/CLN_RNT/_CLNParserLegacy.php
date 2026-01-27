<?php

namespace App\Services\CLN_RNT;

use App\Data\CLN\SequenceResult;
use App\Repositories\CLN\ConstructionRepository;
use App\Services\Trankit\TrankitService;

/**
 * CLN Parser Legacy (v1/v2)
 *
 * Main entry point for parsing sentences using the CLN
 * (Cortical Language Network) architecture.
 *
 * Responsibilities:
 * - Load constructions from database
 * - Preprocess UD tokens from Trankit parser
 * - Process sequences through column manager
 * - Return complete parse results
 *
 * @deprecated Use CLNParser (v3) instead
 */
class CLNParserLegacy
{
    /**
     * Create a new CLN Parser
     */
    public function __construct(
        private ColumnSequenceManager $sequenceManager,
        private ConstructionRepository $constructionRepo,
        private PatternCompiler $compiler,
        private NodeFactory $factory,
        private ?TrankitService $trankitService = null
    ) {
        // Initialize Trankit service if not provided
        if ($this->trankitService === null) {
            $this->trankitService = new TrankitService;
            $this->trankitService->init(config('udparser.trankit_url'));
        }
    }

    /**
     * Parse a sequence of Trankit tokens
     *
     * Main parsing interface.
     *
     * @param  array  $tokens  Trankit tokens (from Trankit service)
     * @param  array  $options  Parsing options (idGrammarGraph, constructionType, etc.)
     * @return SequenceResult Complete parse result
     */
    public function parse(array $tokens, array $options = []): SequenceResult
    {
        // Preprocess tokens
        $preprocessed = $this->preprocessTokens($tokens);

        // Process through column sequence
        $result = $this->sequenceManager->processSequence($preprocessed);

        return $result;
    }

    /**
     * Parse a sentence string using Trankit UD parser
     *
     * Uses the Trankit service to parse the sentence with full linguistic annotations
     * including lemmas, POS tags, and morphological features.
     *
     * Uses getUDTrankitText to preserve contractions while getting proper lemmas.
     *
     * @param  string  $sentence  Raw sentence text
     * @param  array  $options  Parsing options (idGrammarGraph, idLanguage, etc.)
     * @return SequenceResult Complete parse result
     */
    public function parseFromString(string $sentence, array $options = []): SequenceResult
    {
        $sentence = trim($sentence);

        if (empty($sentence)) {
            return $this->parse([], $options);
        }

        // Get language ID from options or use default (Portuguese = 1)
        $idLanguage = $options['idLanguage'] ?? config('udparser.default_language', 1);

        // Parse with Trankit using full text method (preserves contractions, generates lemmas)
        $result = $this->trankitService->getUDTrankitText($sentence, $idLanguage);

        // Convert Trankit format to expected token format
        $tokens = $this->convertTrankitTokens($result->udpipe ?? []);

        // Parse through CLN
        return $this->parse($tokens, $options);
    }

    /**
     * Load constructions from database
     *
     * Loads enabled constructions and compiles their patterns.
     *
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @param  string|null  $type  Construction type filter (MWE, PHRASAL, etc.)
     * @return array Compiled constructions
     */
    public function loadConstructions(int $idGrammarGraph, ?string $type = null): array
    {
        $constructions = $type
            ? $this->constructionRepo->listByGrammar($idGrammarGraph, $type)
            : $this->constructionRepo->listByGrammar($idGrammarGraph);

        $compiled = [];

        foreach ($constructions as $construction) {
            if (empty($construction->pattern)) {
                continue;
            }

            try {
                $graph = $this->compiler->compile($construction->pattern);

                // Inject constraints from database into compiled pattern nodes
                $graph = $this->injectConstraints($graph, $construction->constraints);

                $compiled[] = [
                    'id' => $construction->idConstruction,
                    'name' => $construction->name,
                    'type' => $construction->constructionType,
                    'pattern' => $construction->pattern,
                    'graph' => $graph,
                    'priority' => $construction->priority ?? 0,
                    'enabled' => $construction->enabled ?? true,
                ];
            } catch (\Exception $e) {
                // Skip constructions with invalid patterns
                continue;
            }
        }

        // Sort by priority (higher first)
        usort($compiled, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        return $compiled;
    }

    /**
     * Inject constraints from database into compiled pattern graph
     *
     * Takes constraints from the construction's constraints column and injects them
     * into the corresponding SLOT nodes in the compiled pattern graph.
     *
     * Constraint format from database:
     * [{"type":"feature_equals","element":0,"feature":"VerbForm","value":"Part"}]
     *
     * This gets converted to SLOT node constraint: "VerbForm=Part"
     *
     * @param  array  $graph  Compiled pattern graph
     * @param  string|null  $constraintsJson  JSON string of constraints from database
     * @return array Modified graph with constraints injected
     */
    private function injectConstraints(array $graph, ?string $constraintsJson): array
    {
        if (empty($constraintsJson) || $constraintsJson === '[]') {
            return $graph;
        }

        try {
            $constraints = json_decode($constraintsJson, true);
            if (! is_array($constraints)) {
                return $graph;
            }

            // Get pattern nodes (excluding START/END)
            $patternNodes = [];
            foreach ($graph['nodes'] ?? [] as $nodeId => $node) {
                if (! in_array($node['type'] ?? '', ['START', 'END', 'INTERMEDIATE', 'REP_CHECK'])) {
                    $patternNodes[] = ['id' => $nodeId, 'node' => $node];
                }
            }

            // Apply constraints to corresponding nodes
            foreach ($constraints as $constraint) {
                $elementIndex = $constraint['element'] ?? null;
                $type = $constraint['type'] ?? null;

                if ($elementIndex === null || ! isset($patternNodes[$elementIndex])) {
                    continue;
                }

                $nodeId = $patternNodes[$elementIndex]['id'];
                $constraintString = $this->buildConstraintString($constraint);

                if ($constraintString !== null) {
                    // Inject constraint into the node
                    $graph['nodes'][$nodeId]['constraint'] = $constraintString;
                }
            }

            return $graph;
        } catch (\Exception $e) {
            // If constraint parsing fails, return original graph
            return $graph;
        }
    }

    /**
     * Build constraint string from constraint definition
     *
     * Converts constraint object to PatternMatcher-compatible string.
     *
     * Examples:
     * - {"type":"feature_equals","feature":"VerbForm","value":"Part"} → "VerbForm=Part"
     * - {"type":"feature_in","feature":"PronType","values":["Prs","Dem"]} → "PronType=Prs" (uses first value)
     *
     * @param  array  $constraint  Constraint definition from database
     * @return string|null Constraint string or null if cannot build
     */
    private function buildConstraintString(array $constraint): ?string
    {
        $type = $constraint['type'] ?? null;

        switch ($type) {
            case 'feature_equals':
                $feature = $constraint['feature'] ?? null;
                $value = $constraint['value'] ?? null;

                if ($feature && $value) {
                    return "{$feature}={$value}";
                }
                break;

            case 'feature_in':
                // For feature_in, use the first value as constraint
                // PatternMatcher will need to be enhanced to support multiple values
                $feature = $constraint['feature'] ?? null;
                $values = $constraint['values'] ?? [];

                if ($feature && ! empty($values)) {
                    // For now, just use first value
                    // TODO: Enhance PatternMatcher to support OR constraints
                    return "{$feature}={$values[0]}";
                }
                break;
        }

        return null;
    }

    /**
     * Convert Trankit UD tokens to expected format
     *
     * Trankit returns tokens with: word, lemma, pos, morph (array), etc.
     * We need to convert to: form, lemma, upos, feats (string)
     *
     * @param  array  $trankitTokens  Tokens from Trankit service
     * @return array Converted tokens in expected format
     */
    private function convertTrankitTokens(array $trankitTokens): array
    {
        $converted = [];

        foreach ($trankitTokens as $token) {
            // Convert morph array to feats string (e.g., "Number=Sing|Gender=Masc")
            $feats = '_';
            if (! empty($token['morph']) && is_array($token['morph'])) {
                $featPairs = [];
                foreach ($token['morph'] as $key => $value) {
                    $featPairs[] = "$key=$value";
                }
                $feats = implode('|', $featPairs);
            }

            $converted[] = (object) [
                'form' => $token['word'] ?? '',
                'lemma' => $token['lemma'] ?? strtolower($token['word'] ?? ''),
                'upos' => $token['pos'] ?? '_',
                'feats' => $feats,
            ];
        }

        return $converted;
    }

    /**
     * Preprocess Trankit tokens
     *
     * Extracts and normalizes token information.
     *
     * @param  array  $udpipeTokens  Raw Trankit tokens
     * @return array Preprocessed tokens
     */
    private function preprocessTokens(array $udpipeTokens): array
    {
        $preprocessed = [];

        foreach ($udpipeTokens as $token) {
            $preprocessed[] = (object) [
                'form' => $token->form ?? '',
                'lemma' => $token->lemma ?? strtolower($token->form ?? ''),
                'upos' => $token->upos ?? '_',
                'feats' => $token->feats ?? '_',
                'features' => $this->extractFeatures($token),
            ];
        }

        return $preprocessed;
    }

    /**
     * Extract features from Trankit token
     *
     * @param  object  $token  Trankit token
     * @return array Feature key-value pairs
     */
    private function extractFeatures(object $token): array
    {
        if (empty($token->feats) || $token->feats === '_') {
            return [];
        }

        $features = [];
        $parts = explode('|', $token->feats);

        foreach ($parts as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $features[$key] = $value;
            }
        }

        return $features;
    }

    /**
     * Get parser statistics
     *
     * @return array Statistics about loaded constructions
     */
    public function getStatistics(): array
    {
        return [
            'columns_active' => count($this->sequenceManager->getAllColumns()),
        ];
    }

    /**
     * Reset parser state
     *
     * Clears all column state and resets sequence manager.
     */
    public function reset(): void
    {
        $this->sequenceManager->reset();
    }
}
