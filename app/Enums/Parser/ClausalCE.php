<?php

namespace App\Enums\Parser;

/**
 * Clausal Construction Elements (Level 2)
 *
 * Labels for elements within clauses (phrase-level and some word-level components).
 * Biological analogy: Peptides - functional phrases built from words.
 *
 * @see docs/flat_syntax/ce_labels.md
 */
enum ClausalCE: string
{
    /** The primary predicate of the clause; main verb expressing the event/state */
    case PRED = 'Pred';

    /** Referring phrases denoting participants in the event (subjects, objects, obliques) */
    case ARG = 'Arg';

    /** Complex Predicate Part: auxiliaries, manner adverbs, tense-aspect-modality elements */
    case CPP = 'CPP';

    /** Genitive Phrase: possessive or genitive phrases modifying another nominal */
    case GEN = 'Gen';

    /** Flagged Phrase Modifier: phrases marked with adpositions/case that modify nominals */
    case FPM = 'FPM';

    /** Coordinators or linking elements joining clauses */
    case CONJ = 'Conj';

    /**
     * Transform phrasal CE to clausal CE based on context and features
     *
     * @param  PhrasalCE  $phrasalCE  The phrasal CE to transform
     * @param  string  $pos  The POS tag
     * @param  array  $features  Universal Dependencies features
     * @param  string|null  $deprel  The dependency relation (if available)
     */
    public static function fromPhrasalCE(
        PhrasalCE $phrasalCE,
        string $pos,
        array $features = [],
        ?string $deprel = null
    ): self {
        // Head transformations depend on POS and features
        if ($phrasalCE === PhrasalCE::HEAD) {
            // Finite verb becomes Predicate
            if ($pos === 'VERB' && isset($features['VerbForm']) && $features['VerbForm'] === 'Fin') {
                return self::PRED;
            }

            // Auxiliary becomes CPP (Complex Predicate Part)
            if ($pos === 'AUX') {
                return self::CPP;
            }

            // Nouns/Pronouns become Arguments
            if (in_array($pos, ['NOUN', 'PROPN', 'PRON'])) {
                // Check for genitive case
                if (isset($features['Case']) && $features['Case'] === 'Gen') {
                    return self::GEN;
                }

                return self::ARG;
            }

            // Adverbs become CPP (manner adverbs) or FPM (sentence adverbs)
            if ($pos === 'ADV') {
                // Sentence-level adverbs are FPM, manner adverbs are CPP
                if ($deprel === 'advmod' || $deprel === 'obl') {
                    return self::FPM;
                }

                return self::CPP;
            }

            // Default for other Heads
            return self::ARG;
        }

        // Modifiers stay as part of the phrase they modify (no separate clausal CE)
        if ($phrasalCE === PhrasalCE::MOD) {
            return self::ARG; // Part of argument phrase
        }

        // Adpositions mark flagged phrase modifiers
        if ($phrasalCE === PhrasalCE::ADP) {
            return self::FPM;
        }

        // Linkers - all become CONJ at clausal level
        // (Note: REL is a sentential CE, not clausal - it marks entire relative clauses)
        if ($phrasalCE === PhrasalCE::LNK) {
            return self::CONJ;
        }

        // Conjunctions
        if ($phrasalCE === PhrasalCE::CONJ) {
            return self::CONJ;
        }

        // Default
        return self::ARG;
    }

    /**
     * Get all clausal CE values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
