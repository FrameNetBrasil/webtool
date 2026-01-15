<?php

namespace App\Data\Daisy;

class ClusterData
{
    public int $id;

    public string $type;

    public array $canUse = [];

    public array $components = [];

    public ?int $idWindow = null;

    public function __construct(int $id, string $type, array $canUse = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->canUse = $canUse;
    }

    public function canAdd(string $function): bool
    {
        return in_array($function, $this->canUse);
    }

    public function addComponent(ComponentData $component): void
    {
        $component->idCluster = $this->id;
        $this->components[] = $component;
    }
}
