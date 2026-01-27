<?php

namespace App\Models\CLN;

/**
 * SOM+ inhibitor: blocks pathways while construction is incomplete.
 * Now supports both AND and OR blocking modes.
 */
class SOM_Inhibitor {
    public FunctionalColumn $owner;
    public bool $active = false;
    public int $activationTime = 0;
    public int $maxDuration = 4;

    public SOMMode $mode = SOMMode::AND;

    // For AND mode: the specific column that triggers release
    public ?FunctionalColumn $releaseColumn = null;

    // For OR mode: POS types that MAINTAIN blocking (expected types)
    // When anything NOT in this list arrives, we release
    /** @var string[] */
    public array $expectedPOSTypes = [];

    public function activate(int $currentTime): void {
        $this->active = true;
        $this->activationTime = $currentTime;
    }

    public function isActive(): bool {
        return $this->active;
    }

    public function release(): void {
        $this->active = false;
    }

    public function checkTimeout(int $currentTime): bool {
        if (!$this->active) return false;
        return ($currentTime - $this->activationTime) >= $this->maxDuration;
    }

    /**
     * Check if the given activated columns should release this SOM.
     *
     * @param FunctionalColumn[] $activatedPOSColumns - POS columns activated this cycle
     */
    public function shouldRelease(array $activatedPOSColumns): bool {
        if (!$this->active) return false;

        if ($this->mode === SOMMode::AND) {
            // AND mode: release when the specific right column activates
            foreach ($activatedPOSColumns as $col) {
                if ($this->releaseColumn !== null &&
                    $col->id === $this->releaseColumn->id) {
                    return true;
                }
            }
            return false;

        } else {
            // OR mode: release when NONE of the activated columns are expected
            // (i.e., something unexpected arrived)
            if (empty($activatedPOSColumns)) {
                return false;
            }

            foreach ($activatedPOSColumns as $col) {
                if (in_array($col->id, $this->expectedPOSTypes)) {
                    // At least one expected type arrived - keep blocking
                    return false;
                }
            }

            // Nothing expected arrived - release!
            return true;
        }
    }
}
