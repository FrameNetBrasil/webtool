<?php

namespace App\Data\CLN;

/**
 * Sequence Result
 *
 * Contains the complete result of processing a token sequence through CLN.
 * Includes all column activations, constructions, partial constructions, and statistics.
 */
class SequenceResult
{
    /**
     * Create a new Sequence Result
     *
     * @param  array  $tokens  Input tokens (UDPipe format)
     * @param  array  $columnResults  ColumnActivationResult for each position
     * @param  array  $confirmedConstructions  Fully confirmed constructions
     * @param  array  $partialConstructions  Active partial constructions
     * @param  array  $parseTree  Hierarchical parse structure
     * @param  float  $totalConfidence  Overall confidence (0-1)
     * @param  array  $statistics  Processing statistics
     */
    public function __construct(
        public array $tokens,
        public array $columnResults,
        public array $confirmedConstructions,
        public array $partialConstructions,
        public array $parseTree,
        public float $totalConfidence,
        public array $statistics
    ) {}

    /**
     * Get sentence text
     *
     * @return string Sentence as string
     */
    public function getSentence(): string
    {
        return implode(' ', array_map(fn ($token) => $token->form ?? '', $this->tokens));
    }

    /**
     * Get MWE constructions only
     *
     * Filters constructions to only MWEs.
     *
     * @return array MWE constructions
     */
    public function getMWEs(): array
    {
        return array_filter(
            $this->confirmedConstructions,
            fn ($c) => str_contains($c['name'] ?? '', 'MWE')
        );
    }

    /**
     * Get phrasal constructions only
     *
     * @return array Phrasal constructions
     */
    public function getPhrasalConstructions(): array
    {
        return array_filter(
            $this->confirmedConstructions,
            fn ($c) => str_contains($c['name'] ?? '', 'PHRASAL')
        );
    }

    /**
     * Get clausal constructions only
     *
     * @return array Clausal constructions
     */
    public function getClausalConstructions(): array
    {
        return array_filter(
            $this->confirmedConstructions,
            fn ($c) => str_contains($c['name'] ?? '', 'CLAUSAL')
        );
    }

    /**
     * Check if sequence has any confirmed constructions
     *
     * @return bool True if constructions found
     */
    public function hasConstructions(): bool
    {
        return count($this->confirmedConstructions) > 0;
    }

    /**
     * Check if sequence has active partial constructions
     *
     * @return bool True if partial constructions active
     */
    public function hasActivePartialConstructions(): bool
    {
        return count($this->partialConstructions) > 0;
    }

    /**
     * Get construction at position
     *
     * Returns construction that starts at given position.
     *
     * @param  int  $position  Token position
     * @return array|null Construction or null
     */
    public function getConstructionAtPosition(int $position): ?array
    {
        foreach ($this->confirmedConstructions as $construction) {
            if ($construction['position'] === $position) {
                return $construction;
            }
        }

        return null;
    }

    /**
     * Get all constructions covering position
     *
     * Returns all constructions whose span includes the position.
     *
     * @param  int  $position  Token position
     * @return array Array of constructions
     */
    public function getConstructionsCoveringPosition(int $position): array
    {
        $covering = [];

        foreach ($this->confirmedConstructions as $construction) {
            $start = $construction['position'];
            $length = count($construction['pattern'] ?? []);
            $end = $start + $length - 1;

            if ($position >= $start && $position <= $end) {
                $covering[] = $construction;
            }
        }

        return $covering;
    }

    /**
     * Convert to array representation
     *
     * @return array Complete result as array
     */
    public function toArray(): array
    {
        return [
            'sentence' => $this->getSentence(),
            'token_count' => count($this->tokens),
            'constructions' => [
                'confirmed' => $this->confirmedConstructions,
                'partial' => $this->partialConstructions,
                'mwes' => $this->getMWEs(),
                'phrasal' => $this->getPhrasalConstructions(),
                'clausal' => $this->getClausalConstructions(),
            ],
            'parse_tree' => $this->parseTree,
            'confidence' => $this->totalConfidence,
            'statistics' => $this->statistics,
            'column_results' => array_map(
                fn ($result) => $result->toArray(),
                $this->columnResults
            ),
        ];
    }

    /**
     * Get summary string
     *
     * @return string Human-readable summary
     */
    public function getSummary(): string
    {
        $parts = [
            sprintf('Sentence: "%s"', $this->getSentence()),
            sprintf('Tokens: %d', count($this->tokens)),
            sprintf('Constructions: %d confirmed, %d partial',
                count($this->confirmedConstructions),
                count($this->partialConstructions)
            ),
        ];

        if ($this->hasConstructions()) {
            $mweCount = count($this->getMWEs());
            if ($mweCount > 0) {
                $parts[] = sprintf('MWEs: %d', $mweCount);
            }
        }

        $parts[] = sprintf('Confidence: %.2f', $this->totalConfidence);

        return implode(' | ', $parts);
    }

    /**
     * Get detailed report
     *
     * @return string Detailed multi-line report
     */
    public function getDetailedReport(): string
    {
        $lines = [];

        $lines[] = '=== CLN Sequence Processing Result ===';
        $lines[] = '';
        $lines[] = sprintf('Sentence: "%s"', $this->getSentence());
        $lines[] = sprintf('Tokens: %d', count($this->tokens));
        $lines[] = '';

        $lines[] = 'Confirmed Constructions:';
        if (empty($this->confirmedConstructions)) {
            $lines[] = '  (none)';
        } else {
            foreach ($this->confirmedConstructions as $i => $c) {
                $lines[] = sprintf(
                    '  %d. %s (id=%d) at position %d',
                    $i + 1,
                    $c['name'] ?? 'unknown',
                    $c['construction_id'] ?? 0,
                    $c['position']
                );
                $lines[] = sprintf('     Pattern: %s', implode(', ', $c['pattern'] ?? []));
            }
        }

        $lines[] = '';
        $lines[] = 'Partial Constructions:';
        if (empty($this->partialConstructions)) {
            $lines[] = '  (none)';
        } else {
            foreach ($this->partialConstructions as $i => $p) {
                $lines[] = sprintf(
                    '  %d. %s (activation=%.2f) at position %d',
                    $i + 1,
                    $p['name'] ?? 'unknown',
                    $p['activation'] ?? 0,
                    $p['position']
                );
            }
        }

        $lines[] = '';
        $lines[] = 'Statistics:';
        foreach ($this->statistics as $key => $value) {
            if (is_float($value)) {
                $lines[] = sprintf('  %s: %.2f', $key, $value);
            } else {
                $lines[] = sprintf('  %s: %s', $key, $value);
            }
        }

        return implode("\n", $lines);
    }
}
