<?php

namespace App\Models\CLN;

/**
 * Tracks the state of an AND node that is partially complete.
 * Created when the left element arrives, resolved when right arrives.
 */
class PendingConstruction {
    public FunctionalColumn $andColumn;
    public FunctionalColumn $leftFiller;
    public int $startedAtTime;
    //public array $leftActivationWindow;   // Snapshot of left's L5 history

    public function __construct(
        FunctionalColumn $andColumn,
        FunctionalColumn $leftFiller,
        int $startedAtTime,
        array $leftActivationWindow
    ) {
        $this->andColumn = $andColumn;
        $this->leftFiller = $leftFiller;
        $this->startedAtTime = $startedAtTime;
        //$this->leftActivationWindow = $leftActivationWindow;
    }
}
