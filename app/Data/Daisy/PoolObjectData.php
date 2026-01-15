<?php

namespace App\Data\Daisy;

class PoolObjectData
{
    public string $frameName;

    public array $set = [];

    public float $factor;

    public string $baseFrame;

    public int $level;

    public function __construct(
        string $frameName,
        float $factor,
        string $baseFrame,
        int $level
    ) {
        $this->frameName = $frameName;
        $this->factor = $factor;
        $this->baseFrame = $baseFrame;
        $this->level = $level;
    }

    public function addContributor(
        string $word,
        string $frame,
        float $energy,
        int $iword,
        int $level,
        int $idWindow,
        bool $isQualia = false
    ): void {
        $this->set[$word] = [
            'frame' => $frame,
            'energy' => $energy,
            'iword' => $iword,
            'level' => $level,
            'idWindow' => $idWindow,
            'isQualia' => $isQualia,
        ];
    }

    public function mergeContributor(string $word, float $newEnergy): void
    {
        if (isset($this->set[$word])) {
            if ($newEnergy > $this->set[$word]['energy']) {
                $this->set[$word]['energy'] = $newEnergy;
            }
        }
    }
}
