<?php

namespace App\Services\Daisy;

/**
 * SpreadingActivationService - Energy Spreading Algorithm
 *
 * Responsible for:
 * - Implementing the spreading activation algorithm
 * - Calculating energy contributions from pool objects
 * - Applying energy bonuses (MWE, MKNOB, Qualia)
 * - Updating final energy values for all frame candidates
 */
class SpreadingActivationService
{
    private array $energyBonus;

    public function __construct()
    {
        $this->energyBonus = config('daisy.energyBonus');
    }

    /**
     * Process spreading activation across all windows
     *
     * @param  array  $windows  Windows with frame candidates and their pools
     * @return array Windows with updated energy values
     */
    public function processSpreadingActivation(array $windows): array
    {
        debug('=== SPREADING ACTIVATION START ===');
        foreach ($windows as $idWindow => $words) {
            foreach ($words as $word => $frames) {
                foreach ($frames as $frameEntry => $frame) {
                    $initialEnergy = $frame->energy;
                    debug("  Frame {$frameEntry} (word '{$word}'): initial energy = {$initialEnergy}");

                    // Calculate energy from spreading activation
                    $spreadEnergy = $this->calculateSpreadEnergy($frame, $word, $idWindow);
                    debug("    Spread energy from pool: {$spreadEnergy}");

                    // Update frame energy
                    $windows[$idWindow][$word][$frameEntry]->energy += $spreadEnergy;

                    // Apply bonuses
                    $bonuses = $this->calculateBonuses($frame);
                    debug("    Bonuses (MWE/MKNOB): {$bonuses}");
                    $windows[$idWindow][$word][$frameEntry]->energy += $bonuses;

                    $finalEnergy = $windows[$idWindow][$word][$frameEntry]->energy;
                    debug("    Final energy after spreading: {$finalEnergy}");
                }
            }
        }
        debug('=== SPREADING ACTIVATION END ===');

        return $windows;
    }

    /**
     * Calculate energy spread from pool objects
     */
    private function calculateSpreadEnergy(object $frame, string $currentWord, int $currentWindowId): float
    {
        $totalEnergy = 0.0;
        $poolSize = count($frame->pool);
        debug("      Pool has {$poolSize} entries");

        if (empty($frame->pool)) {
            debug('      WARNING: Empty pool!');

            return 0.0;
        }

        foreach ($frame->pool as $poolFrameName => $poolObject) {
            $setSize = isset($poolObject->set) && is_countable($poolObject->set) ? count($poolObject->set) : 0;
            debug("        Pool frame '{$poolFrameName}': set size = {$setSize}");

            if (! isset($poolObject->set) || empty($poolObject->set)) {
                debug('          No \'set\' data in pool object');

                continue;
            }

            foreach ($poolObject->set as $contributingWord => $element) {
                // Don't self-activate
                if ($currentWord === $contributingWord) {
                    debug("          Word '{$contributingWord}': SKIPPED (self)");

                    continue;
                }

                // Check if can use this energy:
                // - Same window OR
                // - Qualia relation
                $canUse = $element['isQualia'] || ($element['idWindow'] === $currentWindowId);
                debug("          Word '{$contributingWord}': isQualia={$element['isQualia']}, idWindow={$element['idWindow']} vs current={$currentWindowId}, canUse={$canUse}");

                if ($canUse) {
                    $energy = $element['energy'];
                    debug("            Adding energy: {$energy}");
                    $totalEnergy += $energy;
                }
            }
        }

        debug("      Total spread energy: {$totalEnergy}");

        return $totalEnergy;
    }

    /**
     * Calculate energy bonuses
     */
    private function calculateBonuses(object $frame): float
    {
        $bonus = 0.0;

        // Multi-word expression bonus
        if ($frame->mwe) {
            $bonus += $this->energyBonus['mwe'];
        }

        // MKNOB domain bonus
        if ($frame->mknob) {
            $bonus += $this->energyBonus['mknob'];
        }

        // Qualia bonuses are already applied in semantic network construction

        return $bonus;
    }
}
