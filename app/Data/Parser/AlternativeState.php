<?php

namespace App\Data\Parser;

use Spatie\LaravelData\Data;

/**
 * Alternative State Data Transfer Object
 *
 * Represents a construction hypothesis in progress during incremental parsing.
 * Each alternative tracks:
 * - Which construction it's matching
 * - Current progress (matched components, expected next)
 * - Activation level (how complete is the match)
 * - Status (pending, progressing, complete, abandoned, etc.)
 *
 * Status values:
 * - pending: Just created, awaiting first match
 * - progressing: Partially matched, expecting more
 * - complete: Pattern fully matched, ready for confirmation
 * - tentative_complete: MWE matched but awaiting lookahead validation
 * - confirmed: Construction confirmed and added to parse
 * - invalidated: MWE invalidated by lookahead
 * - abandoned: Cannot continue matching
 * - aggregated: MWE components aggregated into single node
 */
class AlternativeState extends Data
{
    public function __construct(
        public int $id,
        public string $constructionName,
        public string $constructionType,
        public int $priority,
        public int $startPosition,
        public int $currentPosition,
        public array $matchedComponents,
        public array $expectedNext,
        public float $activation,
        public float $threshold,
        public string $status,
        public array $features,
        public array $pendingConstraints,
        public int $lookaheadCounter = 0,
        public ?string $invalidationReason = null,
    ) {
        // Validate status
        $validStatuses = [
            'pending',
            'progressing',
            'complete',
            'tentative_complete',
            'confirmed',
            'invalidated',
            'abandoned',
            'aggregated',
        ];

        if (! in_array($this->status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$this->status}. Must be one of: ".implode(', ', $validStatuses));
        }
    }

    public static function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'constructionName' => ['required', 'string', 'max:100'],
            'constructionType' => ['required', 'string', 'in:mwe,phrasal,clausal,sentential'],
            'priority' => ['required', 'integer', 'min:1', 'max:199'],
            'startPosition' => ['required', 'integer', 'min:0'],
            'currentPosition' => ['required', 'integer', 'min:0'],
            'matchedComponents' => ['required', 'array'],
            'expectedNext' => ['required', 'array'],
            'activation' => ['required', 'numeric', 'min:0'],
            'threshold' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:pending,progressing,complete,tentative_complete,confirmed,invalidated,abandoned,aggregated'],
            'features' => ['required', 'array'],
            'pendingConstraints' => ['required', 'array'],
            'lookaheadCounter' => ['sometimes', 'integer', 'min:0'],
            'invalidationReason' => ['nullable', 'string'],
        ];
    }

    /**
     * Check if this alternative is complete (activation >= threshold)
     */
    public function isComplete(): bool
    {
        return $this->activation >= $this->threshold;
    }

    /**
     * Check if this alternative is still active (not abandoned or aggregated)
     */
    public function isActive(): bool
    {
        return ! in_array($this->status, ['abandoned', 'aggregated', 'confirmed']);
    }

    /**
     * Check if this is an MWE alternative
     */
    public function isMWE(): bool
    {
        return $this->constructionType === 'mwe';
    }

    /**
     * Check if this alternative is tentatively complete (awaiting lookahead)
     */
    public function isTentativeComplete(): bool
    {
        return $this->status === 'tentative_complete';
    }

    /**
     * Advance this alternative with a new matched token
     * Returns a new AlternativeState with updated progress
     */
    public function advance(mixed $token): self
    {
        $newActivation = $this->activation + 1;
        $newStatus = $newActivation >= $this->threshold ? 'complete' : 'progressing';

        return new self(
            id: $this->id,
            constructionName: $this->constructionName,
            constructionType: $this->constructionType,
            priority: $this->priority,
            startPosition: $this->startPosition,
            currentPosition: $this->currentPosition + 1,
            matchedComponents: [...$this->matchedComponents, $token],
            expectedNext: $this->computeNextExpected($token),
            activation: $newActivation,
            threshold: $this->threshold,
            status: $newStatus,
            features: $this->mergeFeatures($token),
            pendingConstraints: $this->pendingConstraints,
            lookaheadCounter: $this->lookaheadCounter,
            invalidationReason: $this->invalidationReason,
        );
    }

    /**
     * Mark this alternative as abandoned
     */
    public function abandon(string $reason = ''): self
    {
        return new self(
            id: $this->id,
            constructionName: $this->constructionName,
            constructionType: $this->constructionType,
            priority: $this->priority,
            startPosition: $this->startPosition,
            currentPosition: $this->currentPosition,
            matchedComponents: $this->matchedComponents,
            expectedNext: $this->expectedNext,
            activation: $this->activation,
            threshold: $this->threshold,
            status: 'abandoned',
            features: $this->features,
            pendingConstraints: $this->pendingConstraints,
            lookaheadCounter: $this->lookaheadCounter,
            invalidationReason: $reason ?: $this->invalidationReason,
        );
    }

    /**
     * Compute what to expect next based on matched token
     * This is a placeholder - actual implementation will use the construction pattern
     */
    private function computeNextExpected(mixed $token): array
    {
        // TODO: Implement pattern-based next expected computation
        return $this->expectedNext;
    }

    /**
     * Merge features from a newly matched token
     * This is a placeholder - actual implementation will handle feature accumulation
     */
    private function mergeFeatures(mixed $token): array
    {
        // TODO: Implement feature merging logic
        return $this->features;
    }

    /**
     * Get the span of this alternative (start to current position)
     */
    public function getSpan(): array
    {
        return [$this->startPosition, $this->currentPosition];
    }

    /**
     * Get the length of this alternative
     */
    public function getLength(): int
    {
        return $this->currentPosition - $this->startPosition + 1;
    }
}
