<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\RuntimeGraph;

/**
 * Stability Checker for CLN v3
 *
 * Tracks activation dynamics over time to detect:
 * 1. Convergence - When the graph has reached a stable state
 * 2. Oscillations - When activations fluctuate without converging
 * 3. Stagnation - When changes are minimal but convergence not achieved
 */
class StabilityChecker
{
    /**
     * History of activation states for each node
     * Format: [node_id => [activation1, activation2, ...]]
     */
    private array $activationHistory = [];

    /**
     * Maximum history size to keep (prevents memory growth)
     */
    private int $maxHistorySize;

    /**
     * Convergence threshold - maximum allowed change per timestep
     */
    private float $convergenceThreshold;

    /**
     * Oscillation window - number of timesteps to check for oscillations
     */
    private int $oscillationWindow;

    /**
     * Minimum stable timesteps required for convergence
     */
    private int $minStableSteps;

    public function __construct(
        float $convergenceThreshold = 0.001,
        int $oscillationWindow = 10,
        int $minStableSteps = 5,
        int $maxHistorySize = 100
    ) {
        $this->convergenceThreshold = $convergenceThreshold;
        $this->oscillationWindow = $oscillationWindow;
        $this->minStableSteps = $minStableSteps;
        $this->maxHistorySize = $maxHistorySize;
    }

    /**
     * Record current activation state of all L2 nodes
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     */
    public function recordState(RuntimeGraph $graph): void
    {
        $l2Nodes = $graph->getNodesByLevel('L2');

        foreach ($l2Nodes as $node) {
            if (! isset($this->activationHistory[$node->id])) {
                $this->activationHistory[$node->id] = [];
            }

            $this->activationHistory[$node->id][] = $node->activation;

            // Limit history size
            if (count($this->activationHistory[$node->id]) > $this->maxHistorySize) {
                array_shift($this->activationHistory[$node->id]);
            }
        }
    }

    /**
     * Check if the graph has converged
     *
     * Convergence criteria:
     * 1. All nodes have activation changes below threshold
     * 2. Changes have been below threshold for minStableSteps timesteps
     *
     * @return bool True if converged
     */
    public function hasConverged(): bool
    {
        if (empty($this->activationHistory)) {
            return false;
        }

        // Need at least minStableSteps + 1 samples
        $minSamples = $this->minStableSteps + 1;

        foreach ($this->activationHistory as $nodeId => $history) {
            if (count($history) < $minSamples) {
                return false; // Not enough data yet
            }

            // Check last minStableSteps changes
            $recentHistory = array_slice($history, -$minSamples);

            for ($i = 1; $i < count($recentHistory); $i++) {
                $change = abs($recentHistory[$i] - $recentHistory[$i - 1]);

                if ($change > $this->convergenceThreshold) {
                    return false; // Still changing too much
                }
            }
        }

        return true;
    }

    /**
     * Detect if any nodes are oscillating
     *
     * Oscillation detection:
     * - Activations alternate between high and low values
     * - Changes exceed threshold but don't settle
     * - Pattern repeats over oscillationWindow timesteps
     *
     * @return array Array of oscillating node IDs with details
     */
    public function detectOscillations(): array
    {
        $oscillating = [];

        foreach ($this->activationHistory as $nodeId => $history) {
            if (count($history) < $this->oscillationWindow) {
                continue; // Not enough data
            }

            $recentHistory = array_slice($history, -$this->oscillationWindow);

            // Check for oscillation pattern
            if ($this->isOscillating($recentHistory)) {
                $oscillating[] = [
                    'node_id' => $nodeId,
                    'amplitude' => $this->calculateOscillationAmplitude($recentHistory),
                    'frequency' => $this->estimateOscillationFrequency($recentHistory),
                ];
            }
        }

        return $oscillating;
    }

    /**
     * Check if a history shows oscillation pattern
     *
     * @param  array  $history  Recent activation history
     * @return bool True if oscillating
     */
    private function isOscillating(array $history): bool
    {
        // Calculate changes between consecutive steps
        $changes = [];
        for ($i = 1; $i < count($history); $i++) {
            $changes[] = $history[$i] - $history[$i - 1];
        }

        // Count sign changes (direction reversals)
        $signChanges = 0;
        for ($i = 1; $i < count($changes); $i++) {
            if (($changes[$i] > 0 && $changes[$i - 1] < 0) ||
                ($changes[$i] < 0 && $changes[$i - 1] > 0)) {
                $signChanges++;
            }
        }

        // Oscillation if many direction reversals and changes exceed threshold
        $avgChange = array_sum(array_map('abs', $changes)) / count($changes);

        return $signChanges >= (count($changes) / 2) &&
               $avgChange > $this->convergenceThreshold;
    }

    /**
     * Calculate oscillation amplitude
     *
     * @param  array  $history  Recent activation history
     * @return float Amplitude (peak-to-trough)
     */
    private function calculateOscillationAmplitude(array $history): float
    {
        return max($history) - min($history);
    }

    /**
     * Estimate oscillation frequency
     *
     * @param  array  $history  Recent activation history
     * @return float Estimated cycles per window
     */
    private function estimateOscillationFrequency(array $history): float
    {
        // Count peaks in the signal
        $peaks = 0;

        for ($i = 1; $i < count($history) - 1; $i++) {
            if ($history[$i] > $history[$i - 1] && $history[$i] > $history[$i + 1]) {
                $peaks++;
            }
        }

        return $peaks;
    }

    /**
     * Get maximum activation change in the last timestep
     *
     * @return float Maximum change across all nodes
     */
    public function getMaxChange(): float
    {
        $maxChange = 0.0;

        foreach ($this->activationHistory as $nodeId => $history) {
            if (count($history) < 2) {
                continue;
            }

            $change = abs($history[count($history) - 1] - $history[count($history) - 2]);
            $maxChange = max($maxChange, $change);
        }

        return $maxChange;
    }

    /**
     * Get average activation change in the last timestep
     *
     * @return float Average change across all nodes
     */
    public function getAverageChange(): float
    {
        $changes = [];

        foreach ($this->activationHistory as $nodeId => $history) {
            if (count($history) < 2) {
                continue;
            }

            $change = abs($history[count($history) - 1] - $history[count($history) - 2]);
            $changes[] = $change;
        }

        if (empty($changes)) {
            return 0.0;
        }

        return array_sum($changes) / count($changes);
    }

    /**
     * Get stability metrics
     *
     * @return array Comprehensive stability information
     */
    public function getMetrics(): array
    {
        return [
            'has_converged' => $this->hasConverged(),
            'max_change' => $this->getMaxChange(),
            'avg_change' => $this->getAverageChange(),
            'oscillating_nodes' => $this->detectOscillations(),
            'timesteps_recorded' => $this->getTimestepsRecorded(),
            'nodes_tracked' => count($this->activationHistory),
            'convergence_threshold' => $this->convergenceThreshold,
        ];
    }

    /**
     * Get number of timesteps recorded
     *
     * @return int Timesteps recorded
     */
    public function getTimestepsRecorded(): int
    {
        if (empty($this->activationHistory)) {
            return 0;
        }

        // Get max history length across all nodes
        $maxLength = 0;
        foreach ($this->activationHistory as $history) {
            $maxLength = max($maxLength, count($history));
        }

        return $maxLength;
    }

    /**
     * Get activation history for a specific node
     *
     * @param  string  $nodeId  Node ID
     * @return array Activation history
     */
    public function getNodeHistory(string $nodeId): array
    {
        return $this->activationHistory[$nodeId] ?? [];
    }

    /**
     * Get nodes with highest activation variance
     *
     * @param  int  $limit  Number of nodes to return
     * @return array Top varying nodes
     */
    public function getMostUnstableNodes(int $limit = 5): array
    {
        $variances = [];

        foreach ($this->activationHistory as $nodeId => $history) {
            if (count($history) < 2) {
                continue;
            }

            $mean = array_sum($history) / count($history);
            $variance = 0.0;

            foreach ($history as $value) {
                $variance += pow($value - $mean, 2);
            }

            $variance /= count($history);
            $variances[$nodeId] = $variance;
        }

        arsort($variances);

        return array_slice($variances, 0, $limit, true);
    }

    /**
     * Reset stability tracking
     */
    public function reset(): void
    {
        $this->activationHistory = [];
    }

    /**
     * Check if stability tracking has sufficient data
     *
     * @return bool True if enough data for meaningful analysis
     */
    public function hasSufficientData(): bool
    {
        return $this->getTimestepsRecorded() >= $this->minStableSteps + 1;
    }
}
