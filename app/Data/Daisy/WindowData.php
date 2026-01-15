<?php

namespace App\Data\Daisy;

class WindowData
{
    public int $id;

    public int $up = 0; // parent window ID

    public array $components = [];

    public array $clusters = [];

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function canAddCluster(ClusterData $cluster): bool
    {
        // Logic to determine if cluster fits in this window
        // Windows create new boundaries at PUNCT, JOIN, or domain-changing REL
        return true;
    }

    public function addCluster(ClusterData $cluster): void
    {
        $this->clusters[] = $cluster;
    }

    public function addComponent(ComponentData $component): void
    {
        $this->components[] = $component;
    }
}
