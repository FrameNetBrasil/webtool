<?php

namespace App\Models\CLN_RNT;

class NeuralPopulation
{
    public float $activation = 0.0;

    public float $tau;

    public array $nodes = [];

    public string $layer;

    public Column $column;

    public function __construct(float $tau, string $layer, Column $column)
    {
        $this->tau = $tau;
        $this->nodes = [];
        $this->layer = $layer;
        $this->column = $column;
    }

    public function update(float $input, float $dt): void
    {
        $this->activation += $dt * (($input - $this->activation) / $this->tau);
        $this->activation = max(0.0, min(1.0, $this->activation));
    }

    public function reset(): void
    {
        $this->activation = 0.0;
    }

    public function createNode(
        string $id,
        string $type, // DATA, OR, AND, SEQUENCER
        array $span,
        array $metadata
    ): Node {
        $node = new Node($this, $id, $type, $span, $metadata);
        $this->nodes[] = $node;
        return $node;
    }

    public function getLayer(): string {
        return $this->layer;
    }

    public function getNodes(): array {
        return $this->nodes;
    }

}
