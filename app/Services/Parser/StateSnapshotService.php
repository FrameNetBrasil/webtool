<?php

namespace App\Services\Parser;

use App\Data\Parser\V5\ParseStateV5;
use App\Repositories\Parser\StateSnapshotRepository;

/**
 * State Snapshot Service
 *
 * Manages persistence and retrieval of parse state snapshots for debugging
 * and step-by-step evaluation of Parser V5.
 *
 * Features:
 * - Persist snapshots to database
 * - Retrieve snapshots by position or range
 * - Export snapshots for visualization
 * - Replay parsing from snapshots
 * - Compare snapshots across positions
 *
 * Use Cases:
 * - Debugging parse failures
 * - Understanding ghost fulfillment
 * - Visualizing reconfiguration operations
 * - Performance analysis
 * - Educational demonstrations
 */
class StateSnapshotService
{
    public function __construct(
        private StateSnapshotRepository $repository
    ) {}

    /**
     * Save a snapshot to the database
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @param  array  $snapshot  Snapshot data from ParseStateV5::captureSnapshot()
     * @return int Snapshot ID
     */
    public function saveSnapshot(int $idParserGraph, array $snapshot): int
    {
        return $this->repository->create([
            'idParserGraph' => $idParserGraph,
            'position' => $snapshot['position'],
            'tokenData' => $snapshot['tokenData'] ?? null,
            'tokenGraph' => $snapshot['tokenGraph'],
            'activeAlternatives' => $snapshot['activeAlternatives'] ?? null,
            'ghostNodes' => $snapshot['ghostNodes'] ?? [],
            'confirmedNodes' => $snapshot['confirmedNodes'] ?? null,
            'confirmedEdges' => $snapshot['confirmedEdges'] ?? null,
            'reconfigurations' => $snapshot['reconfigurations'] ?? [],
            'statistics' => $this->extractStatistics($snapshot),
            'capturedAt' => now(),
            'processingTime' => $snapshot['timestamp'] ?? null,
        ]);
    }

    /**
     * Save all snapshots from a parse state
     *
     * @param  ParseStateV5  $state  Parse state with snapshots
     * @return array Array of created snapshot IDs
     */
    public function saveAllSnapshots(ParseStateV5 $state): array
    {
        $snapshotIds = [];

        foreach ($state->stateSnapshots as $snapshot) {
            $snapshotIds[] = $this->saveSnapshot($state->idParserGraph, $snapshot);
        }

        return $snapshotIds;
    }

    /**
     * Get a specific snapshot by parser graph and position
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @param  int  $position  Position in sentence
     * @return array|null Snapshot data
     */
    public function getSnapshot(int $idParserGraph, int $position): ?array
    {
        return $this->repository->findByPosition($idParserGraph, $position);
    }

    /**
     * Get all snapshots for a parser graph
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @return array Array of snapshots ordered by position
     */
    public function getAllSnapshots(int $idParserGraph): array
    {
        return $this->repository->findByParserGraph($idParserGraph);
    }

    /**
     * Get snapshots in a position range
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @param  int  $startPosition  Start position (inclusive)
     * @param  int  $endPosition  End position (inclusive)
     * @return array Array of snapshots
     */
    public function getSnapshotRange(int $idParserGraph, int $startPosition, int $endPosition): array
    {
        return $this->repository->findByPositionRange($idParserGraph, $startPosition, $endPosition);
    }

    /**
     * Delete all snapshots for a parser graph
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @return int Number of snapshots deleted
     */
    public function deleteSnapshots(int $idParserGraph): int
    {
        return $this->repository->deleteByParserGraph($idParserGraph);
    }

    /**
     * Export snapshots for visualization
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @return array Formatted data for visualization
     */
    public function exportForVisualization(int $idParserGraph): array
    {
        $snapshots = $this->getAllSnapshots($idParserGraph);

        return [
            'idParserGraph' => $idParserGraph,
            'totalPositions' => count($snapshots),
            'positions' => array_map(function ($snapshot) {
                return [
                    'position' => $snapshot['position'],
                    'token' => $snapshot['tokenData']['word'] ?? $snapshot['tokenData']['form'] ?? null,
                    'tokenGraph' => [
                        'nodeCount' => $snapshot['tokenGraph']['realNodeCount'] ?? 0,
                        'ghostCount' => $snapshot['tokenGraph']['ghostNodeCount'] ?? 0,
                        'edgeCount' => $snapshot['tokenGraph']['edgeCount'] ?? 0,
                    ],
                    'alternatives' => $snapshot['activeAlternatives'] ?? 0,
                    'confirmedNodes' => $snapshot['confirmedNodes'] ?? 0,
                    'reconfigurations' => count($snapshot['reconfigurations'] ?? []),
                    'ghosts' => [
                        'pending' => $this->countGhostsByState($snapshot['ghostNodes'] ?? [], 'pending'),
                        'fulfilled' => $this->countGhostsByState($snapshot['ghostNodes'] ?? [], 'fulfilled'),
                        'expired' => $this->countGhostsByState($snapshot['ghostNodes'] ?? [], 'expired'),
                    ],
                ];
            }, $snapshots),
        ];
    }

    /**
     * Get detailed snapshot for debugging
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @param  int  $position  Position in sentence
     * @return array|null Detailed snapshot with formatted data
     */
    public function getDetailedSnapshot(int $idParserGraph, int $position): ?array
    {
        $snapshot = $this->getSnapshot($idParserGraph, $position);

        if (! $snapshot) {
            return null;
        }

        return [
            'position' => $snapshot['position'],
            'token' => $snapshot['tokenData'],
            'tokenGraph' => $this->formatTokenGraph($snapshot['tokenGraph']),
            'alternatives' => $snapshot['activeAlternatives'],
            'confirmedNodes' => $snapshot['confirmedNodes'],
            'confirmedEdges' => $snapshot['confirmedEdges'],
            'reconfigurations' => $this->formatReconfigurations($snapshot['reconfigurations'] ?? []),
            'ghostNodes' => $this->formatGhostNodes($snapshot['ghostNodes'] ?? []),
            'statistics' => $snapshot['statistics'] ?? [],
            'capturedAt' => $snapshot['capturedAt'],
        ];
    }

    /**
     * Compare two snapshots (useful for understanding changes)
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @param  int  $position1  First position
     * @param  int  $position2  Second position
     * @return array Comparison data
     */
    public function compareSnapshots(int $idParserGraph, int $position1, int $position2): array
    {
        $snapshot1 = $this->getSnapshot($idParserGraph, $position1);
        $snapshot2 = $this->getSnapshot($idParserGraph, $position2);

        if (! $snapshot1 || ! $snapshot2) {
            return [
                'error' => 'One or both snapshots not found',
            ];
        }

        return [
            'position1' => $position1,
            'position2' => $position2,
            'tokenGraph' => [
                'nodeCountDiff' => ($snapshot2['tokenGraph']['realNodeCount'] ?? 0) - ($snapshot1['tokenGraph']['realNodeCount'] ?? 0),
                'ghostCountDiff' => ($snapshot2['tokenGraph']['ghostNodeCount'] ?? 0) - ($snapshot1['tokenGraph']['ghostNodeCount'] ?? 0),
                'edgeCountDiff' => ($snapshot2['tokenGraph']['edgeCount'] ?? 0) - ($snapshot1['tokenGraph']['edgeCount'] ?? 0),
            ],
            'alternativesDiff' => ($snapshot2['activeAlternatives'] ?? 0) - ($snapshot1['activeAlternatives'] ?? 0),
            'confirmedNodesDiff' => ($snapshot2['confirmedNodes'] ?? 0) - ($snapshot1['confirmedNodes'] ?? 0),
            'reconfigurationsBetween' => $this->getReconfigurationsBetween($idParserGraph, $position1, $position2),
        ];
    }

    /**
     * Get reconfigurations that occurred between two positions
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @param  int  $startPosition  Start position
     * @param  int  $endPosition  End position
     * @return array Array of reconfiguration operations
     */
    public function getReconfigurationsBetween(int $idParserGraph, int $startPosition, int $endPosition): array
    {
        $snapshots = $this->getSnapshotRange($idParserGraph, $startPosition, $endPosition);

        $reconfigurations = [];
        foreach ($snapshots as $snapshot) {
            foreach ($snapshot['reconfigurations'] ?? [] as $reconfig) {
                $reconfigurations[] = array_merge($reconfig, [
                    'snapshotPosition' => $snapshot['position'],
                ]);
            }
        }

        return $reconfigurations;
    }

    /**
     * Get statistics summary across all positions
     *
     * @param  int  $idParserGraph  Parser graph ID
     * @return array Aggregated statistics
     */
    public function getStatisticsSummary(int $idParserGraph): array
    {
        $snapshots = $this->getAllSnapshots($idParserGraph);

        if (empty($snapshots)) {
            return [
                'totalPositions' => 0,
                'totalReconfigurations' => 0,
                'totalGhostsCreated' => 0,
                'totalGhostsFulfilled' => 0,
            ];
        }

        $totalReconfigurations = 0;
        $maxAlternatives = 0;
        $maxNodes = 0;
        $maxGhosts = 0;

        foreach ($snapshots as $snapshot) {
            $totalReconfigurations += count($snapshot['reconfigurations'] ?? []);
            $maxAlternatives = max($maxAlternatives, $snapshot['activeAlternatives'] ?? 0);
            $maxNodes = max($maxNodes, $snapshot['tokenGraph']['realNodeCount'] ?? 0);
            $maxGhosts = max($maxGhosts, $snapshot['tokenGraph']['ghostNodeCount'] ?? 0);
        }

        $lastSnapshot = end($snapshots);

        return [
            'totalPositions' => count($snapshots),
            'totalReconfigurations' => $totalReconfigurations,
            'maxAlternatives' => $maxAlternatives,
            'maxNodes' => $maxNodes,
            'maxGhosts' => $maxGhosts,
            'finalNodeCount' => $lastSnapshot['tokenGraph']['realNodeCount'] ?? 0,
            'finalGhostCount' => $lastSnapshot['tokenGraph']['ghostNodeCount'] ?? 0,
            'finalAlternatives' => $lastSnapshot['activeAlternatives'] ?? 0,
        ];
    }

    /**
     * Extract statistics from snapshot
     */
    private function extractStatistics(array $snapshot): array
    {
        return [
            'tokenGraphStats' => [
                'nodes' => $snapshot['tokenGraph']['realNodeCount'] ?? 0,
                'ghosts' => $snapshot['tokenGraph']['ghostNodeCount'] ?? 0,
                'edges' => $snapshot['tokenGraph']['edgeCount'] ?? 0,
            ],
            'activeAlternatives' => $snapshot['activeAlternatives'] ?? 0,
            'confirmedNodes' => $snapshot['confirmedNodes'] ?? 0,
            'confirmedEdges' => $snapshot['confirmedEdges'] ?? 0,
            'reconfigurations' => count($snapshot['reconfigurations'] ?? []),
        ];
    }

    /**
     * Format token graph for display
     */
    private function formatTokenGraph(array $tokenGraph): array
    {
        return [
            'summary' => [
                'realNodes' => $tokenGraph['realNodeCount'] ?? 0,
                'ghostNodes' => $tokenGraph['ghostNodeCount'] ?? 0,
                'totalNodes' => ($tokenGraph['realNodeCount'] ?? 0) + ($tokenGraph['ghostNodeCount'] ?? 0),
                'edges' => $tokenGraph['edgeCount'] ?? 0,
            ],
            'nodes' => $tokenGraph['nodes'] ?? [],
            'edges' => $tokenGraph['edges'] ?? [],
        ];
    }

    /**
     * Format reconfigurations for display
     */
    private function formatReconfigurations(array $reconfigurations): array
    {
        return array_map(function ($reconfig) {
            return [
                'type' => $reconfig['operationType'] ?? 'unknown',
                'position' => $reconfig['position'] ?? null,
                'reason' => $reconfig['reason'] ?? null,
                'affectedNodes' => $reconfig['affectedNodes'] ?? [],
                'affectedEdges' => $reconfig['affectedEdges'] ?? [],
                'metadata' => $reconfig['metadata'] ?? [],
            ];
        }, $reconfigurations);
    }

    /**
     * Format ghost nodes for display
     */
    private function formatGhostNodes(array $ghostNodes): array
    {
        $formatted = [
            'total' => count($ghostNodes),
            'byState' => [
                'pending' => 0,
                'fulfilled' => 0,
                'expired' => 0,
            ],
            'byType' => [],
            'details' => [],
        ];

        foreach ($ghostNodes as $ghost) {
            $state = $ghost['state'] ?? 'unknown';
            $type = $ghost['ghostType'] ?? 'unknown';

            // Count by state
            if (isset($formatted['byState'][$state])) {
                $formatted['byState'][$state]++;
            }

            // Count by type
            if (! isset($formatted['byType'][$type])) {
                $formatted['byType'][$type] = 0;
            }
            $formatted['byType'][$type]++;

            // Add details
            $formatted['details'][] = [
                'id' => $ghost['id'] ?? null,
                'type' => $type,
                'state' => $state,
                'expectedCE' => $ghost['expectedCE'] ?? null,
                'expectedPOS' => $ghost['expectedPOS'] ?? null,
                'fulfilledBy' => $ghost['fulfilledBy'] ?? null,
            ];
        }

        return $formatted;
    }

    /**
     * Count ghosts by state
     */
    private function countGhostsByState(array $ghostNodes, string $state): int
    {
        return count(array_filter($ghostNodes, fn ($ghost) => ($ghost['state'] ?? null) === $state));
    }
}
