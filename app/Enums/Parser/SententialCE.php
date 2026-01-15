<?php

namespace App\Enums\Parser;

/**
 * Sentential Construction Elements (Level 3)
 *
 * Labels for elements within sentences (clause-level components).
 * Biological analogy: Polypeptides - complete clauses that integrate into sentence.
 *
 * @see docs/flat_syntax/ce_labels.md
 */
enum SententialCE: string
{
    /** Main clause: pragmatically asserted events/states; independent clauses */
    case MAIN = 'Main';

    /** Adverbial clause: temporal, causal, conditional subordinate clauses */
    case ADV = 'Adv';

    /** Relative clause: clauses functioning as modifiers of nominal referents */
    case REL = 'Rel';

    /** Complement clause: clauses functioning as arguments of predicates */
    case COMP = 'Comp';

    /** Detached phrase: pragmatically foregrounded topics or foci */
    case DTCH = 'Dtch';

    /** Interactive: interjections, discourse markers, vocatives */
    case INT = 'Int';

    /**
     * Determine sentential CE based on clause properties
     *
     * @param  bool  $isRoot  Whether this is the root clause
     * @param  string|null  $clauseMarker  The subordinating marker (if any)
     * @param  string|null  $deprel  The dependency relation to parent
     * @param  array  $features  Clause-level features
     */
    public static function fromClauseProperties(
        bool $isRoot,
        ?string $clauseMarker = null,
        ?string $deprel = null,
        array $features = []
    ): self {
        // Root clause with no subordination markers is Main
        if ($isRoot && $clauseMarker === null) {
            return self::MAIN;
        }

        // Check for relative clause markers
        $relativeMarkers = ['que', 'quem', 'qual', 'cujo', 'onde', 'who', 'which', 'that', 'whose', 'where'];
        if ($clauseMarker !== null && in_array(strtolower($clauseMarker), $relativeMarkers)) {
            // "that" can be complementizer or relativizer - check deprel
            if (strtolower($clauseMarker) === 'that' && $deprel === 'ccomp') {
                return self::COMP;
            }
            if (in_array($deprel, ['acl', 'acl:relcl', 'relcl'])) {
                return self::REL;
            }
        }

        // Check for adverbial clause markers
        $adverbialMarkers = [
            'quando', 'enquanto', 'porque', 'como', 'se', 'embora', 'embora', // Portuguese
            'when', 'while', 'because', 'since', 'if', 'although', 'unless', // English
        ];
        if ($clauseMarker !== null && in_array(strtolower($clauseMarker), $adverbialMarkers)) {
            return self::ADV;
        }

        // Check dependency relation for clause type
        if ($deprel !== null) {
            // Adverbial clause relations
            if (in_array($deprel, ['advcl', 'advcl:relcl'])) {
                return self::ADV;
            }

            // Complement clause relations
            if (in_array($deprel, ['ccomp', 'xcomp', 'csubj', 'csubj:pass'])) {
                return self::COMP;
            }

            // Relative clause relations
            if (in_array($deprel, ['acl', 'acl:relcl'])) {
                return self::REL;
            }

            // Discourse/interactive
            if (in_array($deprel, ['discourse', 'vocative', 'intj'])) {
                return self::INT;
            }

            // Dislocated/detached
            if (in_array($deprel, ['dislocated', 'parataxis'])) {
                return self::DTCH;
            }
        }

        // Default to Main for independent clauses
        return self::MAIN;
    }

    /**
     * Check if this is a subordinate clause type
     */
    public function isSubordinate(): bool
    {
        return in_array($this, [self::ADV, self::REL, self::COMP]);
    }

    /**
     * Get all sentential CE values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
