<?php

namespace App\Enums\Parser;

/**
 * Phrasal Construction Elements (Level 1)
 *
 * Labels for elements within phrases (word-level components).
 * Biological analogy: Amino acids - individual words with chemical properties (features).
 *
 * @see docs/flat_syntax/ce_labels.md
 */
enum PhrasalCE: string
{
    /** Core element of a phrase that determines its referential or semantic properties */
    case HEAD = 'Head';

    /** Elements that specify, describe, or delimit the head referent (articles, demonstratives, numerals, adjectives) */
    case MOD = 'Mod';

    /** Modifiers of modifiers; degree words, intensifiers, downtoners */
    case ADM = 'Adm';

    /** Prepositions or postpositions; mark spatial, temporal, or abstract relations */
    case ADP = 'Adp';

    /** Grammatical elements connecting modifiers to heads; linking particles */
    case LNK = 'Lnk';

    /** Numeral or noun classifiers categorizing referents by shape, animacy, or other properties */
    case CLF = 'Clf';

    /** Agreement markers; person/number/gender indexes (when realized as separate words) */
    case IDX = 'Idx';

    /** Coordinators linking modifiers or phrases within a larger phrase structure */
    case CONJ = 'Conj';

    /** Punctuation characters used to organize writing, indicate pauses, and eliminate ambiguities */
    case PUNCT = 'Punct';

    /**
     * Classify phrasal CE from Universal Dependencies POS tag and features
     *
     * Based on Croft's flat syntax CE labels (see docs/flat_syntax/ce_labels.md):
     * - Head: Core element determining referential/semantic properties
     * - Mod: Articles, demonstratives, numerals, adjectives that modify heads
     * - Adm: Modifiers of modifiers (degree words, intensifiers)
     * - Adp: Prepositions/postpositions marking relations
     * - Lnk: Linking particles, subordinators
     * - Clf: Numeral/noun classifiers
     * - Idx: Agreement markers (rare as separate words)
     * - Conj: Coordinators
     * - Punct: Punctuation marks
     *
     * Note: v2 uses only POS-based classification. UD deprel is intentionally
     * not used because it becomes unreliable with null instantiation (ellipsis).
     */
    public static function fromPOS(string $pos, array $features = []): self
    {
        // Classification based purely on POS tag and morphological features
        return match ($pos) {
            // Content words that are heads of phrases
            'NOUN', 'PROPN', 'PRON' => self::HEAD,
            'VERB' => self::HEAD,
            'ADV' => self::HEAD,  // Adverbs are heads of AdvP; at clausal level become CPP

            // Adjectives: participles are heads, attributive adjectives are modifiers
            'ADJ' => isset($features['VerbForm']) ? self::HEAD : self::MOD,

            // Determiners and numerals modify noun heads
            'DET' => self::MOD,
            'NUM' => self::MOD,

            // Adpositions mark relations between phrases
            'ADP' => self::ADP,

            // Conjunctions
            'CCONJ' => self::CONJ,  // Coordinating conjunctions
            'SCONJ' => self::LNK,   // Subordinating conjunctions act as linkers

            // Particles typically link or mark grammatical relations
            'PART' => self::LNK,

            // Auxiliaries are heads at phrasal level, CPP at clausal level
            'AUX' => self::HEAD,

            // Interjections are standalone heads
            'INTJ' => self::HEAD,

            // Punctuation marks
            'PUNCT' => self::PUNCT,

            // Symbols and unknown - treated as heads for completeness
            'SYM', 'X' => self::HEAD,

            default => self::HEAD,
        };
    }

    /**
     * Get all phrasal CE values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
