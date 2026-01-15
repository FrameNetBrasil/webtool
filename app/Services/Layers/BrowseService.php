<?php

namespace App\Services\Layers;

use App\Data\Layers\SearchData;
use App\Database\Criteria;

class BrowseService
{
    public static int $limit = 300;

    public static function browseAllLayerGroups(): array
    {
        $result = [];
        $layerGroups = Criteria::table('layergroup')
            ->select('idLayerGroup', 'name', 'type')
            ->orderBy('name')
            ->limit(self::$limit)
            ->all();

        foreach ($layerGroups as $layerGroup) {
            $result[$layerGroup->idLayerGroup] = [
                'id' => $layerGroup->idLayerGroup,
                'type' => 'layergroup',
                'text' => $layerGroup->name,
                'leaf' => false, // LayerGroups can be expanded to show LayerTypes
                'state' => 'closed',
            ];
        }

        return $result;
    }

    public static function browseLayerGroupBySearch(SearchData $search, bool $leaf = false): array
    {
        $result = [];
        if ($search->layerGroup != '') {
            $layerGroups = Criteria::table('layergroup')
                ->where('name', 'startswith', $search->layerGroup)
                ->select('idLayerGroup', 'name', 'type')
                ->orderBy('name')
                ->limit(self::$limit)
                ->all();

            foreach ($layerGroups as $layerGroup) {
                $result[$layerGroup->idLayerGroup] = [
                    'id' => $layerGroup->idLayerGroup,
                    'type' => 'layergroup',
                    'text' => $layerGroup->name,
                    'leaf' => $leaf,
                    'state' => 'closed',
                ];
            }
        }

        return $result;
    }

    public static function browseLayerTypeBySearch(SearchData $search): array
    {
        $result = [];
        if ($search->layerType != '') {
            $layerTypes = Criteria::table('view_layertype')
                ->where('name', 'startswith', $search->layerType)
                ->where('idLanguage', 2)
                ->select('idLayerType', 'name', 'entry', 'idLayerGroup')
                ->orderBy('name')
                ->limit(self::$limit)
                ->all();

            foreach ($layerTypes as $layerType) {
                $result[$layerType->idLayerType] = [
                    'id' => $layerType->idLayerType,
                    'type' => 'layertype',
                    'text' => $layerType->name.' ['.$layerType->entry.']',
                    'leaf' => false, // LayerTypes can be expanded to show GenericLabels
                    'state' => 'closed',
                ];
            }
        }

        return $result;
    }

    public static function browseGenericLabelBySearch(SearchData $search): array
    {
        $result = [];
        if ($search->genericLabel != '') {
            $genericLabels = Criteria::table('genericlabel')
                ->where('name', 'startswith', $search->genericLabel)
                ->where('idLanguage', 2)
                ->select('idGenericLabel', 'name', 'definition', 'idLayerType')
                ->orderBy('name')
                ->limit(self::$limit)
                ->all();

            foreach ($genericLabels as $genericLabel) {
                $result[$genericLabel->idGenericLabel] = [
                    'id' => $genericLabel->idGenericLabel,
                    'type' => 'genericlabel',
                    'text' => $genericLabel->name,
                    'leaf' => true, // GenericLabels are leaf nodes
                    'state' => 'open',
                ];
            }
        }

        return $result;
    }

    public static function browseLayerTypesByLayerGroup(SearchData $search): array
    {
        $result = [];
        if ($search->id > 0) {
            $layerTypes = Criteria::table('view_layertype')
                ->where('idLayerGroup', $search->id)
                ->where('idLanguage', 2)
                ->select('idLayerType', 'name', 'entry', 'layerOrder')
                ->orderBy('layerOrder')
                ->limit(self::$limit)
                ->all();

            foreach ($layerTypes as $layerType) {
                $result[$layerType->idLayerType] = [
                    'id' => $layerType->idLayerType,
                    'type' => 'layertype',
                    'text' => $layerType->name.' ['.$layerType->entry.']',
                    'leaf' => false, // LayerTypes can be expanded
                    'state' => 'closed',
                ];
            }
        }

        return $result;
    }

    public static function browseGenericLabelsByLayerType(SearchData $search): array
    {
        $result = [];
        if ($search->id > 0) {
            $genericLabels = Criteria::table('genericlabel')
                ->where('idLayerType', $search->id)
                ->where('idLanguage', 2)
                ->select('idGenericLabel', 'name', 'definition')
                ->orderBy('name')
                ->limit(self::$limit)
                ->all();

            foreach ($genericLabels as $genericLabel) {
                $result[$genericLabel->idGenericLabel] = [
                    'id' => $genericLabel->idGenericLabel,
                    'type' => 'genericlabel',
                    'text' => $genericLabel->name,
                    'leaf' => true, // GenericLabels are leaf nodes
                    'state' => 'open',
                ];
            }
        }

        return $result;
    }

    public static function browseLayersBySearch(SearchData $search): array
    {
        $result = [];

        // Handle tree expansion: if type is 'layergroup' and id is provided, return layertypes for that group
        if ($search->type === 'layergroup' && $search->id != 0) {
            $result = self::browseLayerTypesByLayerGroup($search);
        }
        // Handle tree expansion: if type is 'layertype' and id is provided, return genericlabels for that type
        elseif ($search->type === 'layertype' && $search->id != 0) {
            $result = self::browseGenericLabelsByLayerType($search);
        } else {
            // If searching by generic label name, return matching labels
            if ($search->genericLabel != '') {
                $result = self::browseGenericLabelBySearch($search);
            }
            // If searching by layer type name, return matching types
            elseif ($search->layerType != '') {
                $result = self::browseLayerTypeBySearch($search);
            } else {
                // If searching by layer group name, return filtered groups
                if ($search->layerGroup != '') {
                    $result = self::browseLayerGroupBySearch($search);
                } else {
                    // Show all layer groups by default
                    $result = self::browseAllLayerGroups();
                }
            }
        }

        return $result;
    }
}
