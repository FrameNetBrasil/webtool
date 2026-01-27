<?php

namespace App\Services\Daisy;

use App\Data\Daisy\ClusterData;
use App\Data\Daisy\ComponentData;

class ClusterService
{
    public static array $ignorePOS = [
        'DET',
        'PUNCT',
        'SYM',
        'X',
        'AUX',
        'CCONJ',
        'PART',
        'SCONJ',
        'PRON',
        'INTJ',
        'PROPN',
        'ADP',
    ];

    public static array $validRelation = [
        'NOUN' => ['nmod', 'acl', 'nsubj', 'nsubj:pass', 'obj', 'iobj', 'obl', 'obl:agent'],
        'VERB' => ['nsubj', 'nsubj:pass', 'obj', 'iobj', 'obl:agent', 'acl', 'acl:relcl'],
        'ADJ' => ['amod'],
        'ADV' => ['advmod'],
    ];

    public static function createClusters(array $udParsed, int $idLanguage): array
    {
        $clusters = [];
        foreach ($udParsed as $i => $word) {
            $id = $word['id'];
            if (in_array($word['pos'], self::$ignorePOS)) {
                continue;
            }
            $cluster = new ClusterData($id, $word['pos'], $word['word']);
            $components = self::getComponents($udParsed, $id, $idLanguage);
            foreach ($components as $component) {
                $cluster->addComponent($component);
            }
            $cluster->idLanguage = $idLanguage;
            $clusters[$i] = $cluster;

        }

        return $clusters;
    }

    public static function matchLexicalUnits(array $clusters)
    {
        foreach ($clusters as $cluster) {
            foreach ($cluster->components as $component) {
                $component->setLexicalUnits();
            }
        }
    }

    public static function createVectors(array $clusters, SpreadingActivationService $spreadingActivationService)
    {
        foreach ($clusters as $cluster) {
            foreach ($cluster->components as $component) {
                $component->createVectors($spreadingActivationService);
            }
        }

    }

    public static function processClusters(array $udParsed, array $clusters): void
    {
        foreach ($clusters as $cluster) {
            $headComponent = $cluster->components[0];
            if (count($cluster->components) > 1) {
                foreach ($cluster->components as $i => $component) {
                    if ($i != 0) {
                        $headComponent->compareToComponent($component);
                    }
                }
            } else {
                $headComponent->idLU = array_first($headComponent->lus);
            }
        }
    }

    public static function resultFromClusters(array $clusters): array
    {
        $result = [];
        foreach ($clusters as $cluster) {
            $headComponent = $cluster->components[0];
            $idLU = $headComponent->idLU;
            if ($idLU) {
                $lu = DBService::getLUFrame($idLU);
                $result[$cluster->id] = [
                    'id' => $cluster->id,
                    'word' => $cluster->word,
                    'idLU' => $idLU,
                    'lu' => $lu->name,
                    'idFrame' => $lu->idFrame,
                    'frame' => $lu->frameName,
                ];
            }
        }

        return $result;
    }

    public static function traversalOrder(array $udParsed): array
    {
        $order = [];
        $visited = [];

        // Find the root node (parent = 0)
        $root = null;
        foreach ($udParsed as $node) {
            if ($node['parent'] === 0) {
                $root = $node['id'];
                break;
            }
        }

        if ($root === null) {
            return $order;
        }

        // Perform post-order traversal from root (bottom-up)
        self::postOrderTraversal($udParsed, $root, $visited, $order);

        return $order;
    }

    private static function getComponents(array $udParsed, int $id, int $idLanguage): array
    {
        $components = [];

        // Check if the node exists
        if (! isset($udParsed[$id])) {
            return $components;
        }

        $node = $udParsed[$id];
        $componentPOS = $node['pos'];
        $component = new ComponentData($node['id'], $node['word'], $componentPOS, $idLanguage);
        $component->isMwe = $node['isMwe'];
        $component->lemmas = $node['idLemmas'];
        $components[$node['id']] = $component;

        // Get parent node (if exists and is not root)
        $parentId = $node['parent'];
        if ($parentId > 0 && isset($udParsed[$parentId])) {
            $parent = $udParsed[$parentId];
            $deprel = $node['rel'];
            if (in_array($deprel, self::$validRelation[$componentPOS])) {
                $component = new ComponentData($parent['id'], $parent['word'], $parent['pos'], $idLanguage);
                $component->isMwe = $parent['isMwe'];
                $component->lemmas = $parent['idLemmas'];
                $components[$parent['id']] = $component;
            }
        }

        // Get all descendants (children and children of children)
        $descendantIds = self::getDescendants($udParsed, $id);
        foreach ($descendantIds as $descendantId) {
            $descendant = $udParsed[$descendantId];
            $deprel = $descendant['rel'];
            if (in_array($deprel, self::$validRelation[$componentPOS])) {
                $component = new ComponentData($descendant['id'], $descendant['word'], $descendant['pos'], $idLanguage);
                $component->isMwe = $descendant['isMwe'];
                $component->lemmas = $descendant['idLemmas'];
                $components[$descendant['id']] = $component;
            }
        }

        return $components;
    }

    private static function getDescendants(array $udParsed, int $id): array
    {
        $descendants = [];

        // Check if the node exists
        if (! isset($udParsed[$id])) {
            return $descendants;
        }

        $node = $udParsed[$id];

        // Recursively collect all children and their descendants
        foreach ($node['children'] as $childId) {
            $descendants[] = $childId;
            // Get descendants of this child
            // $childDescendants = self::getDescendants($udParsed, $childId);
            // $descendants = array_merge($descendants, $childDescendants);
        }

        return $descendants;
    }

    private static function postOrderTraversal(array $udParsed, int $nodeId, array &$visited, array &$order): void
    {
        // Skip if already visited or node doesn't exist
        if (isset($visited[$nodeId]) || ! isset($udParsed[$nodeId])) {
            return;
        }

        $visited[$nodeId] = true;
        $node = $udParsed[$nodeId];

        // Visit all children first (recursively)
        foreach ($node['children'] as $childId) {
            self::postOrderTraversal($udParsed, $childId, $visited, $order);
        }

        // Add this node to the order after all children are visited
        $order[] = $nodeId;
    }
}
