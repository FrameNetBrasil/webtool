<?php

namespace App\Services\Daisy;

use App\Data\Daisy\ClusterData;
use App\Data\Daisy\ComponentData;
use App\Data\Daisy\WindowData;

/**
 * GridService - GRID Window Creation and Clustering
 *
 * Responsible for:
 * - Mapping UPOS tags to GRID semantic functions
 * - Creating clusters from words with compatible functions
 * - Building windows from clusters
 * - Disambiguating grid functions based on context
 */
class GridService
{
    private array $uposToGrid;

    private array $combinationValue;

    private array $clusterCanUse;

    private array $udRelationsKeep;

    public function __construct()
    {
        $this->uposToGrid = config('daisy.uposToGrid');
        $this->combinationValue = config('daisy.combinationValue');
        $this->clusterCanUse = config('daisy.clusterCanUse');
        $this->udRelationsKeep = config('daisy.udRelationsKeep');
    }

    /**
     * Process UD parsed sentence into GRID windows
     *
     * @param  array  $udParsed  UD parsed sentence from Trankit
     * @return array ['windows' => WindowData[], 'lemmas' => ComponentData[]]
     */
    public function processToWindows(array $udParsed): array
    {
        // Step 1: Map UPOS to GRID functions and query lemmas
        $components = $this->mapUPOSToGrid($udParsed);

        // Step 2: Disambiguate grid functions based on context
        $components = $this->disambiguateGridFunctions($components);

        // Step 3: Create clusters from components
        $clusters = $this->createClusters($components);

        // Step 4: Create windows from clusters
        $windows = $this->createWindows($clusters);

        return [
            'windows' => $windows,
            'lemmas' => $components,
        ];
    }

    /**
     * Map UPOS tags to GRID functions
     */
    private function mapUPOSToGrid(array $udParsed): array
    {
        $components = [];

        foreach ($udParsed as $id => $node) {
            $component = new ComponentData($id, $node['word'], $node['pos']);
            $component->head($node['parent'] ?? 0);

            // Map UPOS to GRID function
            if (isset($this->uposToGrid[$node['pos']])) {
                $gridFunction = $this->uposToGrid[$node['pos']];
                $component->fn = [$gridFunction];
                $component->fnDef = $gridFunction;
            } else {
                // Unknown POS - default to ENT
                $component->fn = ['ENT'];
                $component->fnDef = 'ENT';
            }

            // Store lemma if available
            if (isset($node['lemma']) && ! empty($node['lemma'])) {
                $component->lemmas = [$node['lemma']];
            }

            // Filter by UD relations
            if (isset($node['rel'])) {
                $rel = $node['rel'];
                // Handle relations with colon (e.g., nmod:tmod -> nmod)
                if (str_contains($rel, ':')) {
                    $rel = explode(':', $rel)[0];
                }
                $node['keep'] = in_array($rel, $this->udRelationsKeep);
            }

            $components[$id] = $component;
        }

        return $components;
    }

    /**
     * Disambiguate grid functions using combination value matrix
     */
    private function disambiguateGridFunctions(array $components): array
    {
        $n = count($components);
        $componentArray = array_values($components);

        foreach ($componentArray as $i => $component) {
            // Skip if only one possible function
            if (count($component->fn) <= 1) {
                continue;
            }

            $maxScore = 0;
            $bestFn = $component->fn[0];

            // Compare with previous word
            if ($i > 0) {
                $prevFn = $componentArray[$i - 1]->fnDef;
                foreach ($component->fn as $candidateFn) {
                    $score = $this->getCombinationValue($prevFn, $candidateFn);
                    if ($score > $maxScore) {
                        $maxScore = $score;
                        $bestFn = $candidateFn;
                    }
                }
            }

            // Compare with next word
            if ($i < $n - 1) {
                foreach ($component->fn as $candidateFn) {
                    foreach ($componentArray[$i + 1]->fn as $nextFn) {
                        $score = $this->getCombinationValue($candidateFn, $nextFn);
                        if ($score > $maxScore) {
                            $maxScore = $score;
                            $bestFn = $candidateFn;
                        }
                    }
                }
            }

            // Set definitive function
            $component->fnDef = $bestFn;
        }

        return $components;
    }

    /**
     * Create clusters from components
     */
    private function createClusters(array $components): array
    {
        $clusters = [];
        $clusterId = 1;
        $currentCluster = null;

        foreach ($components as $component) {
            $fn = $component->fnDef;

            if ($fn === 'PUNCT') {
                // Punctuation creates its own cluster
                $cluster = new ClusterData($clusterId++, 'PUNCT', ['PUNCT']);
                $cluster->addComponent($component);
                $clusters[] = $cluster;
                $currentCluster = null;

                continue;
            }

            // Determine cluster type for this function
            $clusterType = $this->getClusterTypeForFunction($fn);

            if ($clusterType === null) {
                // Unknown function, skip
                continue;
            }

            // Check if we can add to current cluster
            if ($currentCluster === null ||
                $currentCluster->type !== $clusterType ||
                ! $currentCluster->canAdd($fn)) {
                // Create new cluster
                $canUse = $this->clusterCanUse[$clusterType] ?? [$fn];
                $currentCluster = new ClusterData($clusterId++, $clusterType, $canUse);
                $clusters[] = $currentCluster;
            }

            // Add component to current cluster
            $currentCluster->addComponent($component);
        }

        return $clusters;
    }

    /**
     * Get cluster type for a grid function
     */
    private function getClusterTypeForFunction(string $fn): ?string
    {
        foreach ($this->clusterCanUse as $clusterType => $functions) {
            if (in_array($fn, $functions)) {
                return $clusterType;
            }
        }

        return null;
    }

    /**
     * Create windows from clusters
     */
    private function createWindows(array $clusters): array
    {
        $windows = [];
        $windowId = 1;
        $currentWindow = new WindowData($windowId++);

        foreach ($clusters as $cluster) {
            // Check if we need a new window
            if ($cluster->type === 'PUNCT') {
                // Punctuation ends current window and starts new one
                if (! empty($currentWindow->clusters)) {
                    $windows[] = $currentWindow;
                }
                $currentWindow = new WindowData($windowId++);

                continue;
            }

            // Add cluster to current window
            $cluster->idWindow = $currentWindow->id;
            $currentWindow->addCluster($cluster);

            // Add all components from cluster to window
            foreach ($cluster->components as $component) {
                $currentWindow->addComponent($component);
            }
        }

        // Add final window if not empty
        if (! empty($currentWindow->clusters)) {
            $windows[] = $currentWindow;
        }

        return $windows;
    }

    /**
     * Get combination value between two grid functions
     */
    private function getCombinationValue(string $fn1, string $fn2): int
    {
        return $this->combinationValue[$fn1][$fn2][1] ?? 0;
    }
}
