<?php

namespace App\Models\CLN;

class PV_Inhibitor
{
    public array $competingColumns;       // Columns in mutual competition
    public string $targetLayer = 'L5';    // Inhibit representation layer

    public function computeWinner(): FunctionalColumn
    {
        // Winner-take-all: highest L5 activation suppresses others
        $maxActivation = 0;
        $winner = null;

        foreach ($this->competingColumns as $col) {
            if ($col->L5->activation > $maxActivation) {
                $maxActivation = $col->L5->activation;
                $winner = $col;
            }
        }

        // Suppress losers
        foreach ($this->competingColumns as $col) {
            if ($col !== $winner) {
                $col->L5->activation *= 0.1;  // Strong suppression
            }
        }

        return $winner;
    }

}
