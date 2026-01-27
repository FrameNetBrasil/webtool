<?php

namespace App\Services\CLN;

class UDLexicon
{
    private array $entries = [];

    public function addEntry(string $word, array $posTags): void {
        $this->entries[strtolower($word)] = $posTags;
    }

    public function lookup(string $word): array {
        return $this->entries[strtolower($word)] ?? [];
    }
}
