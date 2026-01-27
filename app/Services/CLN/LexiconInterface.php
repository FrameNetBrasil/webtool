<?php

namespace App\Services\CLN;

/**
 * Interface for looking up POS tags for words.
 * You would implement this with your actual lexicon.
 */
interface LexiconInterface {
    /**
     * Returns array of POS tags for a word.
     * A word might have multiple POS (e.g., "run" can be NOUN or VERB).
     */
    public function lookup(string $word): array;
}
