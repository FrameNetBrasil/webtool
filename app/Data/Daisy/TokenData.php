<?php

namespace App\Data\Daisy;

/**
 * Represents a token in a tokenized sentence
 */
class TokenData
{
    public function __construct(
        public string $form,
        public array $idLemmas,
        public bool $isMwe = false,
        public ?int $position = null
    ) {}

    /**
     * Check if this token has any associated lemmas
     */
    public function hasLemmas(): bool
    {
        return ! empty($this->idLemmas);
    }

    /**
     * Get the first lemma ID (if any)
     */
    public function getFirstLemma(): ?int
    {
        return $this->idLemmas[0] ?? null;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'form' => $this->form,
            'idLemmas' => $this->idLemmas,
            'isMwe' => $this->isMwe,
            'position' => $this->position,
        ];
    }
}
