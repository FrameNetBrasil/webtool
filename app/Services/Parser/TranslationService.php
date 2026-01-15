<?php

namespace App\Services\Parser;

use App\Repositories\Parser\ParseEdge;
use App\Repositories\Parser\ParseNode;

/**
 * Translation Stage: Phrasal Construction
 *
 * Builds local phrase structures using feature-driven linking.
 * This is Stage 2 of the three-stage parsing framework (Transcription → Translation → Folding).
 *
 * Biological Analogy: mRNA → Polypeptide (Translation)
 * - Links lexical units into phrases based on feature compatibility
 * - Agreement checking (Gender, Number) like peptide bond formation
 * - Local dependencies only (distance ≤ 3)
 * - Labels phrase types (Pred, Arg, FPM per Croft)
 */
class TranslationService
{
    private FeatureCompatibilityService $compatibilityService;

    private GrammarGraphService $grammarService;

    public function __construct(
        FeatureCompatibilityService $compatibilityService,
        GrammarGraphService $grammarService
    ) {
        $this->compatibilityService = $compatibilityService;
        $this->grammarService = $grammarService;
    }

    /**
     * Translate lexical units into phrasal constituents
     *
     * @param  int  $idParserGraph  Parse graph ID
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @param  string  $language  Language code (pt, en, etc.)
     * @return array Array of created link IDs
     */
    public function translate(
        int $idParserGraph,
        int $idGrammarGraph,
        string $language = 'pt'
    ): array {
        $createdLinks = [];

        if (config('parser.logging.logStages', false)) {
            logger()->info('Translation Stage: Starting', [
                'idParserGraph' => $idParserGraph,
                'language' => $language,
            ]);
        }

        // Get all transcribed nodes (from Stage 1)
        $transcribedNodes = ParseNode::listByStage($idParserGraph, 'transcription');

        if (empty($transcribedNodes)) {
            logger()->warning('Translation Stage: No transcribed nodes found');

            return [];
        }

        // Build local phrases
        foreach ($transcribedNodes as $sourceNode) {
            // Skip nodes that are already linked as targets in translation stage
            $existingLinks = ParseEdge::countByTargetAndStage($sourceNode->idParserNode, 'translation');
            if ($existingLinks > 0) {
                continue; // Already has a head in this phrase
            }

            // Find local phrase partners (distance ≤ maxPhraseDistance)
            $candidates = $this->findLocalCandidates($sourceNode, $transcribedNodes);

            foreach ($candidates as $targetNode) {
                // Check type compatibility (existing grammar rules)
                if (! $this->grammarService->canLink($sourceNode, $targetNode, $idGrammarGraph)) {
                    continue;
                }

                // Check feature compatibility (NEW: feature-driven linking)
                $compatibility = $this->compatibilityService->calculateCompatibility(
                    $sourceNode,
                    $targetNode,
                    $language
                );

                $minScore = config('parser.features.minCompatibilityScore', 0.5);

                if ($compatibility['score'] >= $minScore) {
                    // Check if target already has a link from this stage
                    if (ParseEdge::exists($sourceNode->idParserNode, $targetNode->idParserNode)) {
                        continue;
                    }

                    // Create translation-stage link
                    $linkData = [
                        'idParserGraph' => $idParserGraph,
                        'idSourceNode' => $sourceNode->idParserNode,
                        'idTargetNode' => $targetNode->idParserNode,
                        'linkType' => 'dependency',
                        'weight' => $compatibility['score'],
                        'stage' => 'translation',
                        'compatibilityScore' => $compatibility['score'],
                        'featureMatch' => json_encode([
                            'matches' => $compatibility['matches'],
                            'mismatches' => $compatibility['mismatches'],
                        ]),
                    ];

                    $idLink = ParseEdge::create($linkData);
                    $createdLinks[] = $idLink;

                    if (config('parser.logging.logStages', false)) {
                        logger()->info('Translation: Created phrase link', [
                            'source' => $sourceNode->label,
                            'target' => $targetNode->label,
                            'score' => $compatibility['score'],
                        ]);
                    }

                    // Break after first successful link (local phrase only)
                    break;
                }
            }
        }

        // Label phrase types
        $this->labelPhraseTypes($idParserGraph, $language);

        if (config('parser.logging.logStages', false)) {
            logger()->info('Translation Stage: Complete', [
                'linksCreated' => count($createdLinks),
            ]);
        }

        return $createdLinks;
    }

    /**
     * Find local candidates for phrase formation
     *
     * Returns nodes within maxPhraseDistance
     */
    private function findLocalCandidates(object $sourceNode, array $allNodes): array
    {
        $maxDistance = config('parser.translation.maxPhraseDistance', 3);
        $candidates = [];

        foreach ($allNodes as $targetNode) {
            // Skip self
            if ($targetNode->idParserNode === $sourceNode->idParserNode) {
                continue;
            }

            // Check distance
            $distance = abs($targetNode->positionInSentence - $sourceNode->positionInSentence);
            if ($distance <= $maxDistance) {
                $candidates[] = $targetNode;
            }
        }

        // Sort by distance (prefer closer nodes)
        usort($candidates, function ($a, $b) use ($sourceNode) {
            $distA = abs($a->positionInSentence - $sourceNode->positionInSentence);
            $distB = abs($b->positionInSentence - $sourceNode->positionInSentence);

            return $distA <=> $distB;
        });

        return $candidates;
    }

    /**
     * Label phrase types using Croft's clausal CE labels
     *
     * Updates derived features with phraseType
     */
    private function labelPhraseTypes(int $idParserGraph, string $language): void
    {
        $nodes = ParseNode::listByStage($idParserGraph, 'transcription');

        foreach ($nodes as $node) {
            // Determine phrase type based on node type and features
            $phraseType = $this->compatibilityService->determinePhraseType($node);

            // Check if node is a phrase head (has dependents in translation stage)
            $dependentCount = ParseEdge::countBySourceAndStage($node->idParserNode, 'translation');
            $isHead = $dependentCount > 0;

            // Update derived features
            ParseNode::updateDerivedFeatures($node->idParserNode, [
                'phraseType' => $phraseType,
                'isPhraseHead' => $isHead,
            ]);

            if (config('parser.logging.logStages', false) && $isHead) {
                logger()->info('Translation: Phrase head identified', [
                    'node' => $node->label,
                    'type' => $phraseType,
                ]);
            }
        }
    }

    /**
     * Get phrase structure for a node
     *
     * Returns all nodes in the same local phrase
     */
    public function getPhraseStructure(int $idParserNode): array
    {
        // Get node
        $node = ParseNode::byId($idParserNode);

        // Get all dependents in translation stage
        $dependents = ParseEdge::listByStageWithNodes(
            $node->idParserGraph,
            'translation'
        );

        $phraseNodes = [$node];

        foreach ($dependents as $edge) {
            if ($edge->idSourceNode === $idParserNode) {
                $dependent = ParseNode::byId($edge->idTargetNode);
                $phraseNodes[] = $dependent;
            }
        }

        return $phraseNodes;
    }

    /**
     * Count phrases created in translation stage
     */
    public function countPhrases(int $idParserGraph): int
    {
        // A phrase is a connected component in the translation subgraph
        // For now, count phrase heads (nodes with outgoing translation links)

        $nodes = ParseNode::listByStage($idParserGraph, 'transcription');
        $phraseCount = 0;

        foreach ($nodes as $node) {
            $outgoing = ParseEdge::countBySourceAndStage($node->idParserNode, 'translation');
            if ($outgoing > 0) {
                $phraseCount++;
            }
        }

        return $phraseCount;
    }

    /**
     * Get translation stage statistics
     */
    public function getStatistics(int $idParserGraph): array
    {
        $translationLinks = ParseEdge::listByStage($idParserGraph, 'translation');

        $totalScore = 0.0;
        $perfectMatches = 0;

        foreach ($translationLinks as $link) {
            $totalScore += $link->compatibilityScore ?? 0;
            if (($link->compatibilityScore ?? 0) >= 0.9) {
                $perfectMatches++;
            }
        }

        $avgScore = count($translationLinks) > 0
            ? $totalScore / count($translationLinks)
            : 0;

        return [
            'linkCount' => count($translationLinks),
            'phraseCount' => $this->countPhrases($idParserGraph),
            'avgCompatibilityScore' => $avgScore,
            'perfectMatches' => $perfectMatches,
        ];
    }
}
