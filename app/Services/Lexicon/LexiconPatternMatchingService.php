<?php

namespace App\Services\Lexicon;

use App\Services\Trankit\TrankitService;
use Illuminate\Support\Facades\DB;

/**
 * Lexicon Pattern Matching Service
 *
 * Finds lemma occurrences (SWE and MWE) in sentences by:
 * - Parsing sentence with Trankit (UD parser)
 * - Matching MWE patterns (subtree matching against lexicon_pattern)
 * - Matching SWE lemmas (word form + POS matching via view_lexicon)
 *
 * Used by both CLI commands and web controllers.
 */
class LexiconPatternMatchingService
{
    protected TrankitService $trankit;

    // Cache for database lookups
    protected array $lexiconCache = [];
    protected array $patternCache = [];

    public function __construct(TrankitService $trankit)
    {
        $this->trankit = $trankit;
        $this->trankit->init(config('udparser.trankit_url'));
    }

    // =====================================================
    // PUBLIC API
    // =====================================================

    /**
     * Find all lemmas (SWE and MWE) in a sentence
     *
     * @param string $sentence Text to analyze
     * @param int $idLanguage Language ID
     * @return array Array of lemma occurrences with token indices and confidence
     */
    public function findLemmasInSentence(string $sentence, int $idLanguage): array
    {
        // 1. Parse sentence with Trankit
        $udTree = $this->parseSentence($sentence, $idLanguage);

        if (empty($udTree)) {
            return [];
        }

        // 2. Find MWE lemmas (subtree matching)
        $mweMatches = $this->matchMWEPatterns($udTree, $idLanguage);

        // 3. Find SWE lemmas (word matching)
        $sweMatches = $this->matchSWELemmas($udTree, $idLanguage);

        // 4. Merge results (MWE takes precedence over SWE for same tokens)
        $allMatches = $this->mergeMatches($mweMatches, $sweMatches);

        return $allMatches;
    }

    /**
     * Clear internal caches (useful for batch processing)
     */
    public function clearCaches(): void
    {
        $this->lexiconCache = [];
        $this->patternCache = [];
    }

    // =====================================================
    // PROTECTED METHODS - UD PARSING
    // =====================================================

    /**
     * Parse sentence using Trankit and return UD tree
     *
     * @param string $text Sentence text
     * @param int $idLanguage Language ID
     * @return array UD tree structure with tokens and dependencies
     */
    protected function parseSentence(string $text, int $idLanguage): array
    {
        try {
            return $this->trankit->parseSentenceRawTokens($text, $idLanguage);
        } catch (\Exception $e) {
            \Log::error("Trankit parsing failed: {$e->getMessage()}");
            return [];
        }
    }

    // =====================================================
    // PROTECTED METHODS - SWE MATCHING
    // =====================================================

    /**
     * Match single-word expressions (SWE) by word form and POS
     *
     * @param array $udTree UD parse tree
     * @param int $idLanguage Language ID
     * @return array Array of SWE matches
     */
    protected function matchSWELemmas(array $udTree, int $idLanguage): array
    {
        $matches = [];

        foreach ($udTree as $token) {
            $wordForm = strtolower($token['word']);
            $pos = $token['pos'];

            // Query view_lexicon for matching lemmas
            $lemmas = $this->findSWEByWordForm($wordForm, $pos, $idLanguage);

            foreach ($lemmas as $lemma) {
                $matches[] = [
                    'lemma_id' => $lemma->idLemma,
                    'lemma_text' => $lemma->lemmaName,
                    'lemma_type' => 'SWE',
                    'token_indices' => [$token['id']],
                    'confidence' => 1.0,
                    'matched_words' => [$token['word']],
                ];
            }
        }

        return $matches;
    }

    /**
     * Find SWE lemmas by word form and POS tag
     *
     * @param string $wordForm Word form (lowercased)
     * @param string $pos POS tag from Trankit
     * @param int $idLanguage Language ID
     * @return array Matching lemmas
     */
    protected function findSWEByWordForm(string $wordForm, string $pos, int $idLanguage): array
    {
        $cacheKey = "{$wordForm}_{$pos}_{$idLanguage}";

        if (!isset($this->lexiconCache[$cacheKey])) {
            // Query view_lexicon for SWE lemmas only (exclude MWE lemmas that have patterns)
            $this->lexiconCache[$cacheKey] = DB::table('view_lexicon')
                ->where('form', $wordForm)
                ->where('udPOS', $pos)
                ->where('idLanguage', $idLanguage)
                ->where('position', 1) // Single-word only
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('lexicon_pattern')
                        ->whereColumn('lexicon_pattern.idLemma', 'view_lexicon.idLemma');
                })
                ->select('idLemma', 'lemma as lemmaName')
                ->groupBy('idLemma', 'lemma')
                ->get()
                ->toArray();
        }

        return $this->lexiconCache[$cacheKey];
    }

    // =====================================================
    // PROTECTED METHODS - MWE MATCHING
    // =====================================================

    /**
     * Match multi-word expressions (MWE) using subtree pattern matching
     *
     * @param array $udTree UD parse tree
     * @param int $idLanguage Language ID
     * @return array Array of MWE matches
     */
    protected function matchMWEPatterns(array $udTree, int $idLanguage): array
    {
        $matches = [];

        // Load all MWE patterns for this language
        $patterns = $this->loadMWEPatterns($idLanguage);

        // Try to match each pattern against the UD tree
        foreach ($patterns as $pattern) {
            $patternMatches = $this->matchPattern($pattern, $udTree);

            foreach ($patternMatches as $match) {
                $matches[] = [
                    'lemma_id' => $pattern['idLemma'],
                    'lemma_text' => $pattern['lemmaName'],
                    'lemma_type' => 'MWE',
                    'token_indices' => $match['token_indices'],
                    'confidence' => $match['confidence'],
                    'matched_words' => $match['matched_words'],
                ];
            }
        }

        return $matches;
    }

    /**
     * Load MWE patterns for a language (with caching)
     *
     * @param int $idLanguage Language ID
     * @return array Array of patterns with nodes and edges
     */
    protected function loadMWEPatterns(int $idLanguage): array
    {
        if (isset($this->patternCache[$idLanguage])) {
            return $this->patternCache[$idLanguage];
        }

        // Query patterns for MWE lemmas in this language
        $patterns = DB::table('lexicon_pattern as lp')
            ->join('view_lemma as vl', 'lp.idLemma', '=', 'vl.idLemma')
            ->where('vl.idLanguage', $idLanguage)
            ->where('vl.name', 'like', '% %') // MWE only
            ->select('lp.idLexiconPattern', 'lp.idLemma', 'vl.name as lemmaName')
            ->get();

        $result = [];

        foreach ($patterns as $pattern) {
            // Load nodes for this pattern
            $nodes = DB::table('lexicon_pattern_node')
                ->where('idLexiconPattern', $pattern->idLexiconPattern)
                ->orderBy('position')
                ->get()
                ->toArray();

            // Load edges for this pattern
            $edges = DB::table('lexicon_pattern_edge')
                ->where('idLexiconPattern', $pattern->idLexiconPattern)
                ->get()
                ->toArray();

            $result[] = [
                'idLexiconPattern' => $pattern->idLexiconPattern,
                'idLemma' => $pattern->idLemma,
                'lemmaName' => $pattern->lemmaName,
                'nodes' => $nodes,
                'edges' => $edges,
            ];
        }

        $this->patternCache[$idLanguage] = $result;

        return $result;
    }

    /**
     * Match a single pattern against the UD tree
     *
     * @param array $pattern Pattern with nodes and edges
     * @param array $udTree UD parse tree
     * @return array Array of matches (can be multiple if pattern occurs multiple times)
     */
    protected function matchPattern(array $pattern, array $udTree): array
    {
        $matches = [];

        // Find root node of pattern
        $rootNode = collect($pattern['nodes'])->firstWhere('isRoot', 1);

        if (!$rootNode) {
            return [];
        }

        // Try to match pattern starting from each token in the tree
        foreach ($udTree as $token) {
            $match = $this->matchSubtree($pattern, $rootNode, $token, $udTree);

            if ($match) {
                $matches[] = $match;
            }
        }

        return $matches;
    }

    /**
     * Match pattern subtree starting from a root token
     *
     * @param array $pattern Complete pattern
     * @param object $patternRootNode Root node of pattern
     * @param array $treeToken Candidate root token from UD tree
     * @param array $udTree Complete UD tree
     * @return array|null Match details or null if no match
     */
    protected function matchSubtree(array $pattern, object $patternRootNode, array $treeToken, array $udTree): ?array
    {
        // Check if root token matches pattern root node
        if (!$this->matchNode($patternRootNode, $treeToken)) {
            return null;
        }

        // Build mapping: pattern node ID => tree token ID
        $nodeMapping = [
            $patternRootNode->idLexiconPatternNode => $treeToken['id']
        ];

        // Match all edges
        foreach ($pattern['edges'] as $edge) {
            $headNodeId = $edge->idNodeHead;
            $depNodeId = $edge->idNodeDependent;
            $requiredRelation = $edge->idUDRelation;

            // Get pattern nodes
            $depPatternNode = collect($pattern['nodes'])->firstWhere('idLexiconPatternNode', $depNodeId);

            if (!$depPatternNode) {
                continue;
            }

            // Find matching dependent in tree
            if (!isset($nodeMapping[$headNodeId])) {
                return null; // Head not yet mapped
            }

            $headTokenId = $nodeMapping[$headNodeId];
            $matchedDepToken = $this->findMatchingDependent($depPatternNode, $requiredRelation, $headTokenId, $udTree);

            if (!$matchedDepToken) {
                return null; // Required dependent not found
            }

            $nodeMapping[$depNodeId] = $matchedDepToken['id'];
        }

        // Verify ALL pattern nodes have been matched (complete pattern match only)
        if (count($nodeMapping) !== count($pattern['nodes'])) {
            return null; // Incomplete match - not all nodes were mapped
        }

        // All nodes and edges matched - extract match details
        return $this->buildMatchResult($nodeMapping, $udTree);
    }

    /**
     * Check if a tree token matches a pattern node
     *
     * @param object $patternNode Node from pattern
     * @param array $treeToken Token from UD tree
     * @return bool True if matches
     */
    protected function matchNode(object $patternNode, array $treeToken): bool
    {
        // Match word form (via lexicon lookup)
        if ($patternNode->idLexicon) {
            $lexiconForm = $this->getLexiconForm($patternNode->idLexicon);
            if (strtolower($treeToken['word']) !== strtolower($lexiconForm)) {
                return false;
            }
        }

        // Match POS tag (if specified in pattern)
        if ($patternNode->idUDPOS) {
            $patternPOS = $this->getUDPOSTag($patternNode->idUDPOS);
            if ($treeToken['pos'] !== $patternPOS) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find matching dependent token in tree
     *
     * @param object $depPatternNode Dependent node from pattern
     * @param int $requiredRelationId UD relation ID
     * @param int $headTokenId Head token ID in tree
     * @param array $udTree Complete UD tree
     * @return array|null Matching token or null
     */
    protected function findMatchingDependent(object $depPatternNode, int $requiredRelationId, int $headTokenId, array $udTree): ?array
    {
        $requiredRelation = $this->getUDRelationName($requiredRelationId);

        foreach ($udTree as $token) {
            // Check if this token depends on the head
            if ($token['parent'] != $headTokenId) {
                continue;
            }

            // Check if relation matches
            if ($token['rel'] !== $requiredRelation) {
                continue;
            }

            // Check if token matches pattern node
            if ($this->matchNode($depPatternNode, $token)) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Build match result from node mapping
     *
     * @param array $nodeMapping Map of pattern node ID => tree token ID
     * @param array $udTree Complete UD tree
     * @return array Match details
     */
    protected function buildMatchResult(array $nodeMapping, array $udTree): array
    {
        $tokenIds = array_values($nodeMapping);
        sort($tokenIds);

        $matchedWords = [];
        foreach ($tokenIds as $tokenId) {
            $token = collect($udTree)->firstWhere('id', $tokenId);
            if ($token) {
                $matchedWords[] = $token['word'];
            }
        }

        return [
            'token_indices' => $tokenIds,
            'confidence' => 1.0, // Full structural match
            'matched_words' => $matchedWords,
        ];
    }

    // =====================================================
    // PROTECTED METHODS - MATCH MERGING
    // =====================================================

    /**
     * Merge MWE and SWE matches, prioritizing MWE for overlapping tokens
     *
     * @param array $mweMatches MWE matches
     * @param array $sweMatches SWE matches
     * @return array Merged matches
     */
    protected function mergeMatches(array $mweMatches, array $sweMatches): array
    {
        // Collect all token indices used by MWE
        $mweTokens = [];
        foreach ($mweMatches as $match) {
            foreach ($match['token_indices'] as $tokenId) {
                $mweTokens[$tokenId] = true;
            }
        }

        // Filter SWE matches that don't overlap with MWE
        $filteredSWE = array_filter($sweMatches, function ($match) use ($mweTokens) {
            foreach ($match['token_indices'] as $tokenId) {
                if (isset($mweTokens[$tokenId])) {
                    return false; // Overlaps with MWE
                }
            }
            return true;
        });

        // Merge and sort by first token index
        $allMatches = array_merge($mweMatches, $filteredSWE);
        usort($allMatches, fn($a, $b) => $a['token_indices'][0] <=> $b['token_indices'][0]);

        return $allMatches;
    }

    // =====================================================
    // PROTECTED METHODS - DATABASE HELPERS
    // =====================================================

    /**
     * Get word form from lexicon table
     */
    protected function getLexiconForm(int $idLexicon): string
    {
        $lexicon = DB::table('lexicon')
            ->where('idLexicon', $idLexicon)
            ->first();

        return $lexicon->form ?? '';
    }

    /**
     * Get UDPOS tag from ID
     */
    protected function getUDPOSTag(int $idUDPOS): string
    {
        $udpos = DB::table('udpos')
            ->where('idUDPOS', $idUDPOS)
            ->first();

        return $udpos->POS ?? '';
    }

    /**
     * Get UD relation name from ID
     */
    protected function getUDRelationName(int $idUDRelation): string
    {
        $relation = DB::table('udrelation')
            ->where('idUDRelation', $idUDRelation)
            ->first();

        return $relation->relation ?? '';
    }
}
