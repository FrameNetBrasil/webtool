<?php

namespace App\Services\Parser;

use App\Enums\Parser\ClausalCE;
use App\Enums\Parser\PhrasalCE;
use App\Models\Parser\ClausalCENode;
use App\Models\Parser\PhrasalCENode;

/**
 * Phrase Assembly Service
 *
 * Transforms PhrasalCENodes into ClausalCENodes by:
 * 1. Disambiguating multiple "Head" phrasal CEs
 * 2. Grouping modifiers with heads using feature compatibility
 * 3. Assembling phrases (PPs, NPs, etc.)
 * 4. Classifying into clausal CE types (Pred, Arg, CPP, Gen, FPM, Conj)
 *
 * Implements Stage 2 (Translation) of Croft's flat syntax framework.
 */
class PhraseAssemblyService
{
    public function __construct(
        private FeatureCompatibilityService $compatibilityService
    ) {}

    /**
     * Transform PhrasalCENodes into ClausalCENodes
     *
     * @param  array  $phrasalNodes  Array of PhrasalCENode objects
     * @param  string  $language  Language code
     * @return array Array of ClausalCENode objects
     */
    public function assemble(array $phrasalNodes, string $language = 'pt'): array
    {
        if (empty($phrasalNodes)) {
            return [];
        }

        // Step 1: Disambiguate Head nodes
        $disambiguated = $this->disambiguateHeads($phrasalNodes, $language);

        // Step 2: Group modifiers with their heads
        $grouped = $this->groupModifiersWithHeads($disambiguated, $language);

        // Step 3: Convert groups to ClausalCENodes
        $clausalNodes = [];
        foreach ($grouped as $group) {
            $clausalNodes[] = $this->createClausalCENode($group, $language);
        }

        return $clausalNodes;
    }

    /**
     * CRITICAL: Disambiguate multiple "Head" phrasal CEs
     *
     * Implements the disambiguation flow:
     * 1. VerbForm=Fin → Pred
     * 2. PronType=Rel → Mark as relative clause boundary
     * 3. POS=ADP → FPM (look ahead for nominal)
     * 4. Poss=Yes → Gen
     * 5. POS=NOUN/PRON → Arg (group with modifiers)
     * 6. POS=ADV → CPP or FPM
     * 7. Default → Arg
     *
     * @param  array  $nodes  Array of PhrasalCENode objects
     * @param  string  $language  Language code
     * @return array Array with 'clausalCE' classification added
     */
    private function disambiguateHeads(array $nodes, string $language): array
    {
        $result = [];

        for ($i = 0; $i < count($nodes); $i++) {
            $node = $nodes[$i];

            // Classify all nodes (not just Heads)
            // Mods will be grouped with their heads later
            $classification = $this->classifyNode($node, $nodes, $i, $language);

            $result[] = [
                'node' => $node,
                'clausalCE' => $classification['clausalCE'],
                'isRelativeBoundary' => $classification['isRelativeBoundary'] ?? false,
            ];
        }

        return $result;
    }

    /**
     * Classify any node into its clausal CE type
     *
     * @param  PhrasalCENode  $node  The node to classify
     * @param  array  $allNodes  All nodes (for context)
     * @param  int  $index  Current node index
     * @param  string  $language  Language code
     * @return array ['clausalCE' => ClausalCE, 'isRelativeBoundary' => bool]
     */
    private function classifyNode(PhrasalCENode $node, array $allNodes, int $index, string $language): array
    {
        $features = $node->getLexicalFeatures();

        // Check for possessive feature (can be on Mod or Head)
        if (($features['Poss'] ?? null) === 'Yes') {
            return ['clausalCE' => ClausalCE::GEN];
        }

        // Check for genitive case
        if (($features['Case'] ?? null) === 'Gen') {
            return ['clausalCE' => ClausalCE::GEN];
        }

        // Check phrasal CE
        if ($node->phrasalCE === PhrasalCE::MOD || $node->phrasalCE === PhrasalCE::ADM) {
            // Modifiers become part of Arg phrases
            return ['clausalCE' => ClausalCE::ARG];
        }

        if ($node->phrasalCE === PhrasalCE::ADP) {
            return ['clausalCE' => ClausalCE::FPM];
        }

        if ($node->phrasalCE === PhrasalCE::CONJ) {
            return ['clausalCE' => ClausalCE::CONJ];
        }

        if ($node->phrasalCE === PhrasalCE::LNK) {
            return ['clausalCE' => ClausalCE::CONJ];
        }

        // For Head nodes, apply the detailed disambiguation logic
        if ($node->phrasalCE === PhrasalCE::HEAD) {
            return $this->classifyHeadNode($node, $allNodes, $index, $language);
        }

        // Default
        return ['clausalCE' => ClausalCE::ARG];
    }

    /**
     * Classify a Head node into its clausal CE type
     *
     * @param  PhrasalCENode  $node  The node to classify
     * @param  array  $allNodes  All nodes (for context)
     * @param  int  $index  Current node index
     * @param  string  $language  Language code
     * @return array ['clausalCE' => ClausalCE, 'isRelativeBoundary' => bool]
     */
    private function classifyHeadNode(PhrasalCENode $node, array $allNodes, int $index, string $language): array
    {
        $features = $node->getLexicalFeatures();

        // 1. Check VerbForm=Fin (finite verb) → Pred
        if ($node->pos === 'VERB' && ($features['VerbForm'] ?? null) === 'Fin') {
            return ['clausalCE' => ClausalCE::PRED];
        }

        // Check for auxiliary verbs (also become predicates if finite)
        if ($node->pos === 'AUX' && ($features['VerbForm'] ?? null) === 'Fin') {
            return ['clausalCE' => ClausalCE::PRED];
        }

        // Check for non-finite verb forms → CPP
        if (in_array($node->pos, ['VERB', 'AUX']) && isset($features['VerbForm'])) {
            $verbForm = $features['VerbForm'];
            if (in_array($verbForm, ['Part', 'Inf', 'Ger'])) {
                return ['clausalCE' => ClausalCE::CPP];
            }
        }

        // 2. Check PronType=Rel → Relative pronouns become ARG at clausal level
        // Note: REL is a sentential CE that marks entire relative clauses, not individual pronouns
        // At the clausal level, relative pronouns function as arguments
        if (($features['PronType'] ?? null) === 'Rel') {
            return [
                'clausalCE' => ClausalCE::ARG,
                'isRelativeBoundary' => true,  // Keep this for potential sentential-level processing
            ];
        }

        // 3. Check POS=ADP → FPM (prepositional phrase)
        if ($node->pos === 'ADP' || $node->phrasalCE === PhrasalCE::ADP) {
            return ['clausalCE' => ClausalCE::FPM];
        }

        // 4. Check Poss=Yes → Gen (genitive/possessive)
        if (($features['Poss'] ?? null) === 'Yes') {
            return ['clausalCE' => ClausalCE::GEN];
        }

        // Check for genitive case
        if (($features['Case'] ?? null) === 'Gen') {
            return ['clausalCE' => ClausalCE::GEN];
        }

        // 5. Check POS=NOUN/PRON/PROPN → Arg (argument)
        if (in_array($node->pos, ['NOUN', 'PRON', 'PROPN'])) {
            return ['clausalCE' => ClausalCE::ARG];
        }

        // 6. Check POS=ADV → CPP or FPM
        if ($node->pos === 'ADV') {
            // Adverbs are typically CPP (manner, degree modifying predicate)
            // In Stage 3, we'll use context to distinguish FPM (sentence-level adverbs)
            return ['clausalCE' => ClausalCE::CPP];
        }

        // Handle conjunctions
        if ($node->pos === 'CCONJ' || $node->phrasalCE === PhrasalCE::CONJ) {
            return ['clausalCE' => ClausalCE::CONJ];
        }

        // Handle subordinating conjunctions (linkers)
        if ($node->pos === 'SCONJ' || $node->phrasalCE === PhrasalCE::LNK) {
            return ['clausalCE' => ClausalCE::CONJ];
        }

        // 7. Default: Treat as argument
        return ['clausalCE' => ClausalCE::ARG];
    }

    /**
     * Group modifiers with their heads using feature compatibility
     *
     * @param  array  $disambiguated  Nodes with clausalCE classification
     * @param  string  $language  Language code
     * @return array Array of groups (each group is array of nodes)
     */
    private function groupModifiersWithHeads(array $disambiguated, string $language): array
    {
        $groups = [];
        $used = []; // Track which nodes have been grouped

        // FIRST PASS: Process FPM (prepositional phrases) to group Adp + NP
        // This must happen first because Adp needs to "claim" the following NP
        // Also handles complex prepositions (ADV + ADP like "atrás de")
        for ($i = 0; $i < count($disambiguated); $i++) {
            if (isset($used[$i])) {
                continue;
            }

            $current = $disambiguated[$i];
            $node = $current['node'];

            // For FPM (flagged phrase modifier), look ahead for argument
            if ($current['clausalCE'] === ClausalCE::FPM && $node->phrasalCE === PhrasalCE::ADP) {
                $group = [];

                // Check if there's an ADV immediately before (complex preposition)
                // Examples: "atrás de", "dentro de", "perto de", "longe de"
                if ($i > 0 && ! isset($used[$i - 1])) {
                    $prevCandidate = $disambiguated[$i - 1];
                    $prevNode = $prevCandidate['node'];

                    // If previous node is ADV (classified as CPP), include it
                    if ($prevNode->pos === 'ADV' && $prevCandidate['clausalCE'] === ClausalCE::CPP) {
                        $group[] = $prevCandidate;
                        $used[$i - 1] = true;
                    }
                }

                // Add the current ADP
                $group[] = $current;
                $used[$i] = true;

                // Find the following nominal phrase
                for ($j = $i + 1; $j < count($disambiguated) && $j <= $i + 5; $j++) {
                    if (isset($used[$j])) {
                        continue;
                    }

                    $candidate = $disambiguated[$j];
                    $candidateNode = $candidate['node'];

                    // Add modifiers and the head noun
                    if (in_array($candidateNode->phrasalCE, [PhrasalCE::MOD, PhrasalCE::HEAD])) {
                        $group[] = $candidate;
                        $used[$j] = true;

                        // If we hit a head (noun), we're done
                        if ($candidateNode->phrasalCE === PhrasalCE::HEAD) {
                            break;
                        }
                    } else {
                        break; // Stop if we hit something else
                    }
                }

                $groups[] = $group;
            }
        }

        // SECOND PASS: Process remaining nodes (Args, Preds, etc.)
        for ($i = 0; $i < count($disambiguated); $i++) {
            if (isset($used[$i])) {
                continue; // Already part of a group
            }

            $current = $disambiguated[$i];
            $node = $current['node'];

            // Start a new group with this node
            $group = [$current];
            $used[$i] = true;

            // For HEAD nodes (which can be Arg, Pred, Gen, etc.), look for modifiers
            // This applies to any phrasal HEAD or MOD that hasn't been grouped yet
            if (in_array($node->phrasalCE, [PhrasalCE::HEAD, PhrasalCE::MOD])) {
                // Look backward for modifiers
                for ($j = $i - 1; $j >= 0 && $j >= $i - 3; $j--) {
                    if (isset($used[$j])) {
                        continue;
                    }

                    $candidate = $disambiguated[$j];
                    $candidateNode = $candidate['node'];

                    if ($candidateNode->phrasalCE === PhrasalCE::MOD) {
                        // Check feature compatibility
                        if ($this->canModify($candidateNode, $node, $language)) {
                            array_unshift($group, $candidate); // Add at beginning
                            $used[$j] = true;
                        }
                    }
                }

                // Look forward for modifiers (post-nominal)
                for ($j = $i + 1; $j < count($disambiguated) && $j <= $i + 3; $j++) {
                    if (isset($used[$j])) {
                        continue;
                    }

                    $candidate = $disambiguated[$j];
                    $candidateNode = $candidate['node'];

                    if ($candidateNode->phrasalCE === PhrasalCE::MOD) {
                        // Check feature compatibility
                        if ($this->canModify($candidateNode, $node, $language)) {
                            $group[] = $candidate; // Add at end
                            $used[$j] = true;
                        } else {
                            break; // Stop if modifier doesn't match
                        }
                    } else {
                        break; // Stop if not a modifier
                    }
                }
            }

            // Note: FPM grouping is handled in the first pass above

            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * Check if a modifier can modify a head based on feature compatibility
     *
     * @param  PhrasalCENode  $modifier  The modifier node
     * @param  PhrasalCENode  $head  The head node
     * @param  string  $language  Language code
     * @return bool True if compatible
     */
    private function canModify(PhrasalCENode $modifier, PhrasalCENode $head, string $language): bool
    {
        // Get features
        $modFeatures = $modifier->getLexicalFeatures();
        $headFeatures = $head->getLexicalFeatures();

        // Calculate compatibility score
        $score = $this->calculateCompatibilityScore($modFeatures, $headFeatures, $modifier->index, $head->index);

        // Threshold for binding
        $threshold = 0.3;

        return $score >= $threshold;
    }

    /**
     * Calculate feature compatibility score (H-bonds and ionic bonds)
     *
     * @param  array  $features1  Features of first node
     * @param  array  $features2  Features of second node
     * @param  int  $pos1  Position of first node
     * @param  int  $pos2  Position of second node
     * @return float Compatibility score (0-1+)
     */
    private function calculateCompatibilityScore(array $features1, array $features2, int $pos1, int $pos2): float
    {
        $score = 0.0;

        // Adjacency bonus
        $distance = abs($pos2 - $pos1);
        if ($distance === 1) {
            $score += 0.1;
        }

        // Gender match (H-bond)
        if (isset($features1['Gender']) && isset($features2['Gender'])) {
            if ($features1['Gender'] === $features2['Gender']) {
                $score += 0.3;
            }
        }

        // Number match (H-bond)
        if (isset($features1['Number']) && isset($features2['Number'])) {
            if ($features1['Number'] === $features2['Number']) {
                $score += 0.3;
            }
        }

        // Person match (H-bond)
        if (isset($features1['Person']) && isset($features2['Person'])) {
            if ($features1['Person'] === $features2['Person']) {
                $score += 0.2;
            }
        }

        // Case match (Ionic bond - stronger)
        if (isset($features1['Case']) && isset($features2['Case'])) {
            if ($features1['Case'] === $features2['Case']) {
                $score += 0.5;
            }
        }

        return $score;
    }

    /**
     * Create a ClausalCENode from a group of phrasal nodes
     *
     * @param  array  $group  Group of nodes with clausalCE classification
     * @param  string  $language  Language code
     */
    private function createClausalCENode(array $group, string $language): ClausalCENode
    {
        // Determine the clausal CE type for the group
        // If group contains an ADP, it's an FPM (even if it starts with ADV for complex prepositions)
        $clausalCE = $group[0]['clausalCE'];
        foreach ($group as $item) {
            if ($item['node']->phrasalCE === PhrasalCE::ADP || $item['clausalCE'] === ClausalCE::FPM) {
                $clausalCE = ClausalCE::FPM;
                break;
            }
        }

        // If this is a multi-node group (like FPM with Adp + Mod + Head), create a compound node
        if (count($group) > 1) {
            // Create a compound phrasal node representing the entire group
            $nodes = array_map(fn ($item) => $item['node'], $group);

            // Use fromMWEComponents to create a compound node
            $compoundNode = PhrasalCENode::fromMWEComponents(
                $nodes,
                count($nodes),
                $nodes[0]->pos  // Use POS from first node (e.g., ADP for FPM)
            );

            // Set a readable word representation
            $compoundNode->word = implode(' ', array_map(fn ($n) => $n->word, $nodes));

            return new ClausalCENode(
                phrasalNode: $compoundNode,
                clausalCE: $clausalCE,
                features: $compoundNode->features,
            );
        }

        // Single node group - just use the node directly
        $headNode = $group[0]['node'];

        return new ClausalCENode(
            phrasalNode: $headNode,
            clausalCE: $clausalCE,
            features: $headNode->features,
        );
    }

    /**
     * Find the head node in a group
     *
     * @param  array  $group  Group of nodes
     * @return PhrasalCENode The head node
     */
    private function findHeadNode(array $group): PhrasalCENode
    {
        // Look for the Head phrasal CE
        foreach ($group as $item) {
            $node = $item['node'];
            if ($node->phrasalCE === PhrasalCE::HEAD) {
                return $node;
            }
        }

        // If no explicit head, return the first node
        return $group[0]['node'];
    }
}
