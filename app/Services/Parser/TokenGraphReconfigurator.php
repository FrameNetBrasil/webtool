<?php

namespace App\Services\Parser;

use App\Data\Parser\V5\ParseStateV5;
use App\Data\Parser\V5\ReconfigurationOperation;

/**
 * Token Graph Reconfigurator
 *
 * Handles dynamic Token Graph transformations in Parser V5:
 * - Ghost node fulfillment (merge ghost + real node)
 * - Edge re-linking after node merges
 * - Alternative re-evaluation after graph changes
 * - MWE aggregation
 *
 * Implements the reconfiguration operations described in:
 * docs/parser/v5/TOKEN_GRAPH_RECONFIGURATION.md
 *
 * Key Operations:
 * 1. Ghost Fulfillment: When real node arrives matching ghost
 * 2. Node Merge: Combine ghost and real node properties
 * 3. Edge Re-linking: Update edges after merge
 * 4. Alternative Re-evaluation: Check constraints after changes
 */
class TokenGraphReconfigurator
{
    public function __construct(
        private GhostNodeManager $ghostManager
    ) {}

    /**
     * Reconfigure Token Graph after ghost fulfillment
     *
     * Steps:
     * 1. Find fulfillable ghost for the real node
     * 2. Verify compatibility
     * 3. Merge nodes
     * 4. Re-link edges
     * 5. Update ghost state
     * 6. Log operations
     * 7. Re-evaluate affected alternatives
     */
    public function reconfigureAfterFulfillment(
        ParseStateV5 $state,
        array $realNode,
        int $position
    ): ?array {
        // Find ghost that can be fulfilled by this real node
        $ghost = $this->ghostManager->findFulfillableGhost($realNode);

        if (! $ghost) {
            return null;
        }

        // Verify compatibility
        if (! $ghost->canBeFulfilledBy($realNode)) {
            return null;
        }

        $ghostId = $ghost->id;
        $realNodeId = $realNode['idNode'] ?? $realNode['id'];

        // Merge nodes in Token Graph
        $merged = $state->tokenGraph->mergeNodes($ghostId, $realNodeId);

        // Re-link edges from ghost to real
        $relinkedEdges = $state->tokenGraph->relinkEdges($ghostId, $realNodeId);

        // Update ghost state in manager
        $this->ghostManager->fulfillGhost($ghostId, $realNodeId, $position);

        // Log ghost fulfillment operation
        $state->logReconfiguration(
            ReconfigurationOperation::ghostFulfilled(
                ghostId: $ghostId,
                realNodeId: $realNodeId,
                position: $position,
                fulfillmentReason: "Real node {$realNodeId} fulfills ghost {$ghostId}",
                metadata: [
                    'ghostType' => $ghost->ghostType,
                    'realNodeLabel' => $realNode['label'] ?? null,
                ]
            )
        );

        // Log edges relinked operation
        if (! empty($relinkedEdges)) {
            $state->logReconfiguration(
                ReconfigurationOperation::edgesRelinked(
                    edgeIds: $relinkedEdges,
                    fromNode: $ghostId,
                    toNode: $realNodeId,
                    position: $position,
                    reason: 'Edges redirected after ghost fulfillment'
                )
            );
        }

        // Re-evaluate alternatives affected by this merge
        $this->reevaluateAffectedAlternatives($state, [$ghostId, $realNodeId], $position);

        return [
            'merged' => $merged,
            'relinkedEdges' => $relinkedEdges,
            'ghostId' => $ghostId,
            'realNodeId' => $realNodeId,
        ];
    }

    /**
     * Merge two nodes (used for ghost fulfillment)
     *
     * Steps:
     * 1. Combine node properties (real takes precedence)
     * 2. Mark ghost as fulfilled
     * 3. Update Token Graph
     * 4. Log operation
     */
    public function mergeNodes(
        ParseStateV5 $state,
        int $sourceNodeId,
        int $targetNodeId,
        int $position,
        array $metadata = []
    ): array {
        // Merge in Token Graph
        $merged = $state->tokenGraph->mergeNodes($sourceNodeId, $targetNodeId);

        // Log operation
        $state->logReconfiguration(
            ReconfigurationOperation::nodesMerged(
                sourceNodeId: $sourceNodeId,
                targetNodeId: $targetNodeId,
                position: $position,
                mergedProperties: array_keys($merged),
                metadata: $metadata
            )
        );

        return $merged;
    }

    /**
     * Re-link edges after node merge
     *
     * Updates all edges pointing to/from the old node to reference the new node
     */
    public function relinkEdges(
        ParseStateV5 $state,
        int $fromNodeId,
        int $toNodeId,
        int $position,
        string $reason = 'Edges redirected after merge'
    ): array {
        // Re-link in Token Graph
        $relinkedEdges = $state->tokenGraph->relinkEdges($fromNodeId, $toNodeId);

        // Log operation
        if (! empty($relinkedEdges)) {
            $state->logReconfiguration(
                ReconfigurationOperation::edgesRelinked(
                    edgeIds: $relinkedEdges,
                    fromNode: $fromNodeId,
                    toNode: $toNodeId,
                    position: $position,
                    reason: $reason
                )
            );
        }

        return $relinkedEdges;
    }

    /**
     * Re-evaluate alternatives affected by graph changes
     *
     * Steps:
     * 1. Find alternatives affected by the changed nodes
     * 2. Re-check constraints for each alternative
     * 3. Abandon if constraints fail
     * 4. Log outcomes
     */
    public function reevaluateAffectedAlternatives(
        ParseStateV5 $state,
        array $changedNodeIds,
        int $position
    ): array {
        $outcomes = [];

        // Find affected alternatives
        $affectedAlternatives = $this->findAffectedAlternatives($state, $changedNodeIds);

        foreach ($affectedAlternatives as $alternative) {
            $outcome = $this->reevaluateAlternative($state, $alternative, $position);
            $outcomes[$alternative['id']] = $outcome;

            // Log re-evaluation
            $state->logReconfiguration(
                ReconfigurationOperation::alternativeReevaluated(
                    alternativeId: $alternative['id'],
                    position: $position,
                    outcome: $outcome['status'],
                    reason: $outcome['reason'],
                    metadata: [
                        'changedNodes' => $changedNodeIds,
                        'constraintStatus' => $outcome['constraintStatus'] ?? null,
                    ]
                )
            );
        }

        return $outcomes;
    }

    /**
     * Find alternatives affected by node changes
     *
     * An alternative is affected if:
     * - It uses any of the changed nodes
     * - It has edges connecting to changed nodes
     * - It has pending ghosts at the same position
     */
    private function findAffectedAlternatives(ParseStateV5 $state, array $changedNodeIds): array
    {
        $affected = [];

        foreach ($state->alternatives as $alternative) {
            // Check if alternative uses any changed nodes
            $alternativeNodeIds = array_column($alternative['nodes'] ?? [], 'id');
            if (! empty(array_intersect($alternativeNodeIds, $changedNodeIds))) {
                $affected[] = $alternative;

                continue;
            }

            // Check if alternative has edges to/from changed nodes
            $alternativeEdges = $alternative['edges'] ?? [];
            foreach ($alternativeEdges as $edge) {
                if (in_array($edge['sourceNode'] ?? null, $changedNodeIds) ||
                    in_array($edge['targetNode'] ?? null, $changedNodeIds)) {
                    $affected[] = $alternative;
                    break;
                }
            }
        }

        return $affected;
    }

    /**
     * Re-evaluate a single alternative
     *
     * Checks if the alternative is still valid after graph changes
     */
    private function reevaluateAlternative(
        ParseStateV5 $state,
        array $alternative,
        int $position
    ): array {
        // Check if alternative should be abandoned
        $constraintStatus = $this->checkAlternativeConstraints($state, $alternative);

        if (! $constraintStatus['valid']) {
            return [
                'status' => 'abandoned',
                'reason' => $constraintStatus['reason'],
                'constraintStatus' => $constraintStatus,
            ];
        }

        return [
            'status' => 'maintained',
            'reason' => 'Constraints still satisfied',
            'constraintStatus' => $constraintStatus,
        ];
    }

    /**
     * Check if alternative constraints are satisfied
     *
     * Verifies:
     * - Mandatory elements are present or have ghosts
     * - Slot constraints are satisfied
     * - CE labels are valid
     */
    private function checkAlternativeConstraints(ParseStateV5 $state, array $alternative): array
    {
        // Basic validation
        if (empty($alternative['construction'])) {
            return [
                'valid' => false,
                'reason' => 'No construction specified',
            ];
        }

        // Check mandatory elements
        $mandatoryElements = $alternative['construction']['mandatoryElements'] ?? [];
        $presentElements = array_column($alternative['nodes'] ?? [], 'ce');
        $ghostElements = array_column(
            array_filter(
                $alternative['nodes'] ?? [],
                fn ($node) => ($node['isGhost'] ?? false) && ! ($node['isFulfilled'] ?? false)
            ),
            'expectedCE'
        );

        $allElements = array_merge($presentElements, $ghostElements);

        foreach ($mandatoryElements as $mandatoryElement) {
            if (! in_array($mandatoryElement, $allElements)) {
                return [
                    'valid' => false,
                    'reason' => "Mandatory element '{$mandatoryElement}' missing",
                    'missingElement' => $mandatoryElement,
                ];
            }
        }

        // All constraints satisfied
        return [
            'valid' => true,
            'reason' => 'All constraints satisfied',
        ];
    }

    /**
     * Aggregate MWE nodes into single node
     *
     * Steps:
     * 1. Create aggregate node
     * 2. Mark component nodes as consumed
     * 3. Remove component nodes from graph (optional)
     * 4. Update position index
     * 5. Log operation
     */
    public function aggregateMWE(
        ParseStateV5 $state,
        array $componentNodeIds,
        array $aggregateNodeData,
        int $position,
        string $mweName
    ): array {
        // Create aggregate node ID
        $aggregateNodeId = $aggregateNodeData['idNode'] ?? $aggregateNodeData['id'];

        // Add aggregate node to Token Graph
        $state->tokenGraph->addRealNode($aggregateNodeData);

        // Mark components as consumed (remove from graph)
        $removedNodes = [];
        foreach ($componentNodeIds as $componentId) {
            if ($state->tokenGraph->hasNode($componentId)) {
                $state->tokenGraph->removeNode($componentId);
                $removedNodes[] = $componentId;
            }
        }

        // Log aggregation operation
        $state->logReconfiguration(
            ReconfigurationOperation::mweAggregated(
                consumedNodeIds: $componentNodeIds,
                aggregateNodeId: $aggregateNodeId,
                position: $position,
                mweName: $mweName,
                metadata: [
                    'removedNodes' => $removedNodes,
                    'aggregateLabel' => $aggregateNodeData['label'] ?? null,
                ]
            )
        );

        return [
            'aggregateNodeId' => $aggregateNodeId,
            'consumedNodeIds' => $componentNodeIds,
            'removedNodes' => $removedNodes,
        ];
    }

    /**
     * Complete construction and confirm nodes
     *
     * Steps:
     * 1. Mark construction as completed
     * 2. Confirm nodes in the construction
     * 3. Prune competing alternatives
     * 4. Log operation
     */
    public function completeConstruction(
        ParseStateV5 $state,
        int $alternativeId,
        int $constructionId,
        array $nodeIds,
        int $position
    ): void {
        // Mark nodes as confirmed
        foreach ($nodeIds as $nodeId) {
            if (! in_array($nodeId, $state->confirmedNodes)) {
                $state->confirmedNodes[] = $nodeId;
            }
        }

        // Log construction completion
        $state->logReconfiguration(
            ReconfigurationOperation::constructionCompleted(
                alternativeId: $alternativeId,
                constructionId: $constructionId,
                position: $position,
                nodeIds: $nodeIds
            )
        );
    }

    /**
     * Abandon alternative and remove its nodes
     *
     * Steps:
     * 1. Remove alternative's nodes from Token Graph
     * 2. Remove alternative's edges
     * 3. Log operation
     */
    public function abandonAlternative(
        ParseStateV5 $state,
        int $alternativeId,
        int $position,
        string $reason
    ): void {
        // Find alternative
        $alternative = null;
        foreach ($state->alternatives as $alt) {
            if ($alt['id'] === $alternativeId) {
                $alternative = $alt;
                break;
            }
        }

        if (! $alternative) {
            return;
        }

        // Remove alternative's nodes (only if not confirmed)
        $removedNodes = [];
        foreach ($alternative['nodes'] ?? [] as $node) {
            $nodeId = $node['id'] ?? $node['idNode'];
            if (! in_array($nodeId, $state->confirmedNodes) && $state->tokenGraph->hasNode($nodeId)) {
                $state->tokenGraph->removeNode($nodeId);
                $removedNodes[] = $nodeId;
            }
        }

        // Log abandonment
        $state->logReconfiguration(
            ReconfigurationOperation::alternativeAbandoned(
                alternativeId: $alternativeId,
                position: $position,
                reason: $reason,
                metadata: [
                    'removedNodes' => $removedNodes,
                ]
            )
        );
    }

    /**
     * Get reconfiguration statistics
     */
    public function getStatistics(ParseStateV5 $state): array
    {
        $operations = $state->reconfigurationLog;

        $stats = [
            'totalReconfigurations' => count($operations),
            'byType' => [],
            'byPosition' => [],
            'ghostFulfillments' => 0,
            'edgesRelinked' => 0,
            'alternativesReevaluated' => 0,
            'mwesAggregated' => 0,
            'constructionsCompleted' => 0,
            'alternativesAbandoned' => 0,
        ];

        foreach ($operations as $op) {
            // Count by type
            if (! isset($stats['byType'][$op->operationType])) {
                $stats['byType'][$op->operationType] = 0;
            }
            $stats['byType'][$op->operationType]++;

            // Count by position
            if (! isset($stats['byPosition'][$op->position])) {
                $stats['byPosition'][$op->position] = 0;
            }
            $stats['byPosition'][$op->position]++;

            // Count specific operations
            match ($op->operationType) {
                ReconfigurationOperation::TYPE_GHOST_FULFILLED => $stats['ghostFulfillments']++,
                ReconfigurationOperation::TYPE_EDGES_RELINKED => $stats['edgesRelinked'] += count($op->affectedEdges),
                ReconfigurationOperation::TYPE_ALTERNATIVE_REEVALUATED => $stats['alternativesReevaluated']++,
                ReconfigurationOperation::TYPE_MWE_AGGREGATED => $stats['mwesAggregated']++,
                ReconfigurationOperation::TYPE_CONSTRUCTION_COMPLETED => $stats['constructionsCompleted']++,
                ReconfigurationOperation::TYPE_ALTERNATIVE_ABANDONED => $stats['alternativesAbandoned']++,
                default => null,
            };
        }

        return $stats;
    }
}
