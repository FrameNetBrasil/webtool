<?php

namespace App\Models\CLN;

/**
 * VIP+ inhibitor: suppresses SOM when trigger conditions are met.
 * Now supports both AND and OR modes.
 */
class VIP_Inhibitor {
    public FunctionalColumn $owner;
    /** @var SOM_Inhibitor[] */
    public array $targetSOMs = [];

    public SOMMode $mode = SOMMode::AND;

    // For AND mode: specific trigger column
    public ?FunctionalColumn $triggerColumn = null;

    // For OR mode: expected POS types (fire when something else arrives)
    /** @var string[] */
    public array $expectedPOSTypes = [];

    /**
     * Check if activated columns should trigger this VIP.
     *
     * @param FunctionalColumn[] $activatedPOSColumns
     */
    public function checkAndFire(array $activatedPOSColumns): bool {
        $shouldFire = false;

        if ($this->mode === SOMMode::AND) {
            foreach ($activatedPOSColumns as $col) {
                if ($this->triggerColumn !== null &&
                    $col->id === $this->triggerColumn->id) {
                    $shouldFire = true;
                    break;
                }
            }
        } else {
            // OR mode: fire when unexpected input arrives
            if (!empty($activatedPOSColumns)) {
                $allUnexpected = true;
                foreach ($activatedPOSColumns as $col) {
                    if (in_array($col->id, $this->expectedPOSTypes)) {
                        $allUnexpected = false;
                        break;
                    }
                }
                $shouldFire = $allUnexpected;
            }
        }

        if ($shouldFire) {
            $this->fire();
        }

        return $shouldFire;
    }

    public function fire(): void {
        foreach ($this->targetSOMs as $som) {
            $som->release();
        }
    }
}
