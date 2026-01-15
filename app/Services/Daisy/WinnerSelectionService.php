<?php

namespace App\Services\Daisy;

/**
 * WinnerSelectionService - Frame Disambiguation Resolution
 *
 * Responsible for:
 * - Selecting winning frames based on final energy values
 * - Handling ties (ambiguous cases)
 * - Excluding verbs if configured
 * - Supporting GregNet mode (multiple winners per word)
 */
class WinnerSelectionService
{
    private bool $excludeVerbs;

    private bool $gregnetMode;

    public function __construct(bool $gregnetMode = false)
    {
        $this->excludeVerbs = config('daisy.winnerSelection.excludeVerbs');
        $this->gregnetMode = $gregnetMode;
    }

    /**
     * Generate winners from final energy values
     *
     * @param  array  $windows  Windows with final energy values
     * @param  array  $qualiaFrames  Qualia frame energy contributions
     * @param  array  $luEquivalence  LU equivalence mappings
     * @return array Winners indexed by word position
     */
    public function generateWinners(array $windows, array $qualiaFrames = [], array $luEquivalence = []): array
    {
        $winners = [];
        $weights = [];

        debug('=== generateWinners START ===');
        debug('Windows count:', count($windows));
        debug('Windows keys:', array_keys($windows));
        debug('Config: excludeVerbs =', $this->excludeVerbs, 'gregnetMode =', $this->gregnetMode);

        foreach ($windows as $idWindow => $words) {
            debug("Window {$idWindow}: words count =", is_countable($words) ? count($words) : 'not countable', 'type =', gettype($words));

            if (! is_array($words)) {
                debug("ERROR: words is not an array for window {$idWindow}, it's:", gettype($words));

                continue;
            }

            foreach ($words as $word => $frames) {
                debug("  Word '{$word}': frames count =", is_countable($frames) ? count($frames) : 'not countable');

                if (! is_array($frames) && ! is_object($frames)) {
                    debug("  ERROR: frames is not iterable for word '{$word}'");

                    continue;
                }

                $maxEnergy = 0.0;
                $winnerCandidates = [];
                $wordPosition = null;

                foreach ($frames as $frameEntry => $frame) {
                    // Capture word position (same for all frames of this word)
                    if ($wordPosition === null) {
                        $wordPosition = is_object($frame) ? $frame->iword : ($frame['iword'] ?? null);
                        debug("    Frame '{$frameEntry}': captured iword =", $wordPosition);
                    }

                    $energy = $frame->energy;
                    debug("      Frame '{$frameEntry}': initial energy =", $energy);

                    // Apply additional qualia bonuses
                    if (isset($qualiaFrames[$frame->idLU])) {
                        foreach ($qualiaFrames[$frame->idLU] as $qualiaValue) {
                            $energy += (float) $qualiaValue;
                        }
                    }

                    $finalEnergy = round($energy, 2);
                    $weights[$idWindow][$frame->idLU] = $finalEnergy;
                    debug('      Final energy =', $finalEnergy, 'LU =', $frame->lu);

                    // Skip verbs if configured
                    if ($this->excludeVerbs && str_contains($frame->lu, '.v')) {
                        debug('      SKIPPED: Verb excluded (lu contains .v)');

                        continue;
                    }

                    debug("      Comparing: energy={$energy} vs maxEnergy={$maxEnergy}");

                    // Winner selection logic
                    if ($energy > $maxEnergy) {
                        // New winner
                        debug('      NEW WINNER: energy > maxEnergy');
                        $maxEnergy = $energy;
                        $winnerCandidates = [[
                            'idLU' => $frame->idLU,
                            'lu' => $frame->lu,
                            'frame' => $frameEntry,
                            'value' => $finalEnergy,
                            'equivalence' => $luEquivalence[$frame->idLU] ?? '',
                        ]];
                    } elseif ($energy == $maxEnergy && ! $this->gregnetMode) {
                        // Tie - keep first winner encountered (deterministic choice)
                        debug('      TIE: energy == maxEnergy, keeping first winner');
                        // Do nothing - keep the first winner that was already set
                    } elseif ($energy == $maxEnergy && $this->gregnetMode) {
                        // GregNet mode - allow multiple winners
                        debug('      TIE (GregNet): adding to winners');
                        $winnerCandidates[] = [
                            'idLU' => $frame->idLU,
                            'lu' => $frame->lu,
                            'frame' => $frameEntry,
                            'value' => $finalEnergy,
                            'equivalence' => $luEquivalence[$frame->idLU] ?? '',
                        ];
                    } else {
                        debug('      LOWER: energy < maxEnergy, not a winner');
                    }
                }

                // Use captured word position instead of undefined $frame
                if ($wordPosition !== null) {
                    $winners[$wordPosition] = $winnerCandidates;
                    debug("    Assigned winners for position {$wordPosition}:", count($winnerCandidates), 'candidates');
                } else {
                    debug("    WARNING: wordPosition is null for word '{$word}'");
                }
            }
        }

        debug('=== generateWinners END ===');
        debug('Total winners:', count($winners));
        debug('Total weights:', count($weights));

        return [
            'winners' => $winners,
            'weights' => $weights,
        ];
    }

    /**
     * Format winners for output
     */
    public function formatWinners(array $winners, array $windows): array
    {
        $result = [];

        foreach ($windows as $idWindow => $words) {
            $result[$idWindow] = [];

            foreach ($words as $word => $frames) {
                $wordWinners = [];

                foreach ($frames as $frame) {
                    if (isset($winners[$frame->iword])) {
                        $wordWinners = $winners[$frame->iword];
                        break;
                    }
                }

                if (! empty($wordWinners)) {
                    $result[$idWindow][$word] = $wordWinners;
                }
            }
        }

        return $result;
    }
}
