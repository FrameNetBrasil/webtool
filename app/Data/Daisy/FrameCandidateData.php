<?php

namespace App\Data\Daisy;

class FrameCandidateData
{
    public string $lu;

    public int $idLU;

    public int $idFrame;

    public string $frameEntry;

    public float $energy;

    public int $iword; // word index

    public int $id; // component ID

    public bool $mwe = false; // is multi-word expression

    public bool $mknob = false; // is domain-specific (MKNOB)

    public array $pool = []; // semantic network pool

    public ?string $equivalence = null;

    public function __construct(
        string $lu,
        int $idLU,
        int $idFrame,
        string $frameEntry,
        float $energy,
        int $iword,
        int $id
    ) {
        $this->lu = $lu;
        $this->idLU = $idLU;
        $this->idFrame = $idFrame;
        $this->frameEntry = $frameEntry;
        $this->energy = $energy;
        $this->iword = $iword;
        $this->id = $id;
    }

    public function addToPool(string $frameName, PoolObjectData $poolObject): void
    {
        $this->pool[$frameName] = $poolObject;
    }
}
