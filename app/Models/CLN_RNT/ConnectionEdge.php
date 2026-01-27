<?php

namespace App\Models\CLN_RNT;

class ConnectionEdge
{
    public function __construct(
        public readonly string $source,
        public readonly string $target,
        public readonly string $type,
        public float $weight = 1.0,
        public bool $optional = false,
        public bool $active = false,
    ) {}

    public float $signal = 0.0;

    public function transmit(float $sourceActivation): float
    {
        $this->signal = $sourceActivation * $this->weight;

        return $this->signal;
    }
}
