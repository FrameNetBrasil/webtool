<?php

namespace App\Repositories;

use App\Data\Daisy\DaisyNodeData;
use App\Data\Daisy\DaisyLinkData;
use App\Database\Criteria;

class Daisy
{
    // ==================== NODE METHODS ====================

    public static function createNode(DaisyNodeData $data): int
    {
        return Criteria::table("daisy_node")
            ->insertGetId($data->toArray());
    }

    public static function getNodeById(int $id): ?object
    {
        return Criteria::table("daisy_node")
            ->where("idDaisyNode", $id)
            ->first();
    }

    public static function getNodesByType(string $type): array
    {
        return Criteria::table("daisy_node")
            ->where("type", $type)
            ->orderBy("name")
            ->all();
    }

    public static function updateNode(int $id, DaisyNodeData $data): void
    {
        Criteria::table("daisy_node")
            ->where("idDaisyNode", $id)
            ->update($data->toArray());
    }

    public static function deleteNode(int $id): void
    {
        Criteria::table("daisy_node")
            ->where("idDaisyNode", $id)
            ->delete();
    }

    // ==================== LINK METHODS ====================

    public static function createLink(DaisyLinkData $data): int
    {
        return Criteria::table("daisy_link")
            ->insertGetId($data->toArray());
    }

    public static function getLinkById(int $id): ?object
    {
        return Criteria::table("daisy_link")
            ->where("idDaisyLink", $id)
            ->first();
    }

    public static function getLinksByNode(int $idNode, string $direction = 'both'): array
    {
        $query = Criteria::table("daisy_link");

        if ($direction === 'source') {
            $query->where("idDaisyNodeSource", $idNode);
        } elseif ($direction === 'target') {
            $query->where("idDaisyNodeTarget", $idNode);
        } else {
            // both: get links where node is either source or target
            $query->where(function($q) use ($idNode) {
                $q->where("idDaisyNodeSource", $idNode)
                  ->orWhere("idDaisyNodeTarget", $idNode);
            });
        }

        return $query->all();
    }

    public static function updateLink(int $id, DaisyLinkData $data): void
    {
        Criteria::table("daisy_link")
            ->where("idDaisyLink", $id)
            ->update($data->toArray());
    }

    public static function deleteLink(int $id): void
    {
        Criteria::table("daisy_link")
            ->where("idDaisyLink", $id)
            ->delete();
    }

    public static function deleteLinksByNode(int $idNode): void
    {
        Criteria::table("daisy_link")
            ->where("idDaisyNodeSource", $idNode)
            ->orWhere("idDaisyNodeTarget", $idNode)
            ->delete();
    }
}
