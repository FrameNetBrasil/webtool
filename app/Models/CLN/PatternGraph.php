<?php

namespace App\Models\CLN;

use App\Database\Criteria;
use Illuminate\Support\Facades\DB;

/**
 * Your existing pattern graph - a collection of PatternNodes.
 */
class PatternGraph
{
    /** @var array<string, PatternNode> */
    public array $nodes = [];

    public function __construct()
    {
        $this->buildFromDatabase();
    }

    public function getNode(string $id): ?PatternNode
    {
        return $this->nodes[$id] ?? null;
    }

    public function getAllNodes(): array
    {
        return $this->nodes;
    }

    public function buildFromDatabase()
    {
        $this->getAllPatternNodes();
        foreach ($this->nodes as $id => $node) {
            $incoming = $this->getIncomingNodes($id);
            if ($node->type === PatternNodeType::OR) {
                foreach ($incoming as $incomingNode) {
                    $node->children[] = $incomingNode->id;
                }
            } elseif ($node->type === PatternNodeType::AND) {
                foreach ($incoming as $incomingNode) {
                    $properties = json_decode($incomingNode->properties, true);
                    if ($properties['label'] == 'left') {
                        $node->leftChildId = $incomingNode->id;
                    }
                    if ($properties['label'] == 'right') {
                        $node->rightChildId = $incomingNode->id;
                    }
                }
            }
        }
    }

    private function getAllPatternNodes(): void
    {
        $nodes = Criteria::table('parser_pattern_node')
            ->select('id', 'type', 'specification', 'construction_name', 'value')
            ->all();

        foreach ($nodes as $node) {
            if ($node->type == 'SOM') {
                continue;
            }
            if ($node->type == 'VIP') {
                continue;
            }
            $type = null;
            $specification = json_decode($node->specification, true);
            if ($node->type == 'DATA') {
                if (isset($specification['dataType'])) {
                    if ($specification['dataType'] == 'slot') {
                        $type = PatternNode::getPatternNodeType('POS');
                        $node->value = $specification['pos'];
                    } else {
                        $type = PatternNode::getPatternNodeType('LITERAL');
                    }
                }
            }
            if ($node->type == 'OR') {
                $type = PatternNode::getPatternNodeType('OR');
                $node->value = $specification['construction_name'];
            }
            if ($node->type == 'AND') {
                $type = PatternNode::getPatternNodeType('AND');
                $node->value = $specification['construction_name'];
            }
            $this->nodes[$node->id] = new PatternNode(
                id: $node->id,
                type: $type,
                value: $node->value
            );
        }
    }

    private function getPatternNode(int $nodeId): ?array
    {
        if (isset($this->nodeCache[$nodeId])) {
            return $this->nodeCache[$nodeId];
        }

        $node = DB::table('parser_pattern_node')
            ->where('id', $nodeId)
            ->select('id', 'type', 'specification', 'construction_name', 'value')
            ->first();

        if (! $node) {
            return null;
        }

        $this->nodeCache[$nodeId] = [
            'id' => $node->id,
            'type' => $node->type,
            'specification' => json_decode($node->specification, true),
            'construction_name' => $node->construction_name,
            'value' => $node->value,
        ];

        return $this->nodeCache[$nodeId];
    }

    private function getIncomingNodes(int $toNodeId): array
    {
        $incoming = Criteria::table('parser_pattern_edge as e')
            ->join('parser_pattern_node as n', 'e.from_node_id', '=', 'n.id')
            ->where('to_node_id', $toNodeId)
            ->select('n.id', 'n.type', 'n.specification', 'n.construction_name', 'n.value', 'e.properties')
            ->all();

        return $incoming;
    }
}
