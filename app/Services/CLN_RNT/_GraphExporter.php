<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\CLNColumn;
use App\Models\CLN_RNT\Node;

/**
 * Graph Database Exporter for CLN
 *
 * Exports the CLN node network to graph database formats.
 * This enables:
 * - Direct node-to-node traversal (no column reconstruction)
 * - Native graph queries on node relationships
 * - Position-based indexing for temporal ordering
 * - Subscription edges as first-class relationships
 *
 * Supported formats:
 * - Cypher (Neo4j)
 * - JSON Graph Format (generic)
 * - DOT (Graphviz visualization)
 *
 * Architecture benefit: Node-centric export naturally maps to graph databases,
 * making queries like "find all nodes connected to this construction" trivial.
 *
 * (Phase 6: Performance optimization - PRIMARY PRIORITY)
 */
class GraphExporter
{
    /**
     * Export columns to Neo4j Cypher format
     *
     * Generates CREATE statements for nodes and relationships.
     *
     * @param  array  $columns  Array of CLNColumn instances
     * @return string Cypher statements
     */
    public function exportToCypher(array $columns): string
    {
        $cypher = "// CLN Graph Export - Neo4j Cypher Format\n";
        $cypher .= "// Nodes represent CLN nodes (Node/Node)\n";
        $cypher .= "// Relationships represent activation flows\n\n";

        // Track all nodes for deduplication
        $allNodes = [];
        $allRelationships = [];

        // Collect all nodes from all columns
        foreach ($columns as $column) {
            if (! ($column instanceof CLNColumn)) {
                continue;
            }

            // Export L23 nodes
            $l23Nodes = $column->getL23()->getAllNodes();
            foreach ($l23Nodes as $node) {
                $nodeData = $this->extractNodeData($node, $column->getPosition(), 'L23');
                $allNodes[$nodeData['id']] = $nodeData;

                // Extract relationships (input/output connections)
                $allRelationships = array_merge(
                    $allRelationships,
                    $this->extractRelationships($node, $nodeData['id'])
                );
            }

            // Export L5 nodes
            $l5Nodes = $column->getL5()->getAllNodes();
            foreach ($l5Nodes as $node) {
                $nodeData = $this->extractNodeData($node, $column->getPosition(), 'L5');
                $allNodes[$nodeData['id']] = $nodeData;

                // Extract relationships
                $allRelationships = array_merge(
                    $allRelationships,
                    $this->extractRelationships($node, $nodeData['id'])
                );
            }
        }

        // Generate Cypher CREATE statements for nodes
        $cypher .= "// === NODES ===\n\n";
        foreach ($allNodes as $nodeData) {
            $cypher .= $this->generateCypherNode($nodeData);
        }

        // Generate Cypher CREATE statements for relationships
        $cypher .= "\n// === RELATIONSHIPS ===\n\n";
        foreach ($allRelationships as $relationship) {
            $cypher .= $this->generateCypherRelationship($relationship);
        }

        // Add indexes for performance
        $cypher .= "\n// === INDEXES ===\n\n";
        $cypher .= "CREATE INDEX node_position IF NOT EXISTS FOR (n:CLNNode) ON (n.position);\n";
        $cypher .= "CREATE INDEX node_type IF NOT EXISTS FOR (n:CLNNode) ON (n.node_type);\n";
        $cypher .= "CREATE INDEX node_layer IF NOT EXISTS FOR (n:CLNNode) ON (n.layer);\n";

        return $cypher;
    }

    /**
     * Export columns to JSON Graph Format
     *
     * Generic JSON format compatible with most graph databases.
     *
     * @param  array  $columns  Array of CLNColumn instances
     * @return string JSON graph
     */
    public function exportToJSON(array $columns): array
    {
        $nodes = [];
        $edges = [];

        foreach ($columns as $column) {
            if (! ($column instanceof CLNColumn)) {
                continue;
            }

            // Export L23 nodes
            $l23Nodes = $column->getL23()->getAllNodes();
            foreach ($l23Nodes as $node) {
                $nodeData = $this->extractNodeData($node, $column->getPosition(), 'L23');
                $nodes[] = $nodeData;

                // Extract edges
                $nodeEdges = $this->extractRelationships($node, $nodeData['id']);
                foreach ($nodeEdges as $edge) {
                    $edges[] = [
                        'source' => $edge['from'],
                        'target' => $edge['to'],
                        'type' => $edge['type'],
                        'metadata' => $edge['metadata'] ?? [],
                    ];
                }
            }

            // Export L5 nodes
            $l5Nodes = $column->getL5()->getAllNodes();
            foreach ($l5Nodes as $node) {
                $nodeData = $this->extractNodeData($node, $column->getPosition(), 'L5');
                $nodes[] = $nodeData;

                // Extract edges
                $nodeEdges = $this->extractRelationships($node, $nodeData['id']);
                foreach ($nodeEdges as $edge) {
                    $edges[] = [
                        'source' => $edge['from'],
                        'target' => $edge['to'],
                        'type' => $edge['type'],
                        'metadata' => $edge['metadata'] ?? [],
                    ];
                }
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'metadata' => [
                'format' => 'json-graph',
                'version' => '1.0',
                'exported_at' => date('Y-m-d H:i:s'),
                'node_count' => count($nodes),
                'edge_count' => count($edges),
            ],
        ];
    }

    /**
     * Export columns to DOT format for Graphviz visualization
     *
     * Creates hierarchical subgraphs:
     * - One subgraph per column (position)
     * - Within each column: L23 and L5 layer subgraphs
     *
     * @param  array  $columns  Array of CLNColumn instances
     * @return string DOT graph
     */
    public function exportToDOT(array $columns): string
    {
        $dot = "digraph CLN {\n";
        $dot .= "  rankdir=TB;\n";
        $dot .= "  compound=true;\n";  // Allow edges between clusters
        $dot .= "  node [shape=box, style=filled];\n";
        $dot .= "  graph [style=rounded];\n\n";

        // Organize data by column and layer
        $columnData = [];
        $allEdges = [];

        foreach ($columns as $column) {
            if (! ($column instanceof CLNColumn)) {
                continue;
            }

            $position = $column->position;

            if (! isset($columnData[$position])) {
                $columnData[$position] = [
                    'l23_nodes' => [],
                    'l5_nodes' => [],
                ];
            }

            // Collect L23 nodes
            $l23Nodes = $column->getL23()->getAllNodes();
            foreach ($l23Nodes as $node) {
                $nodeData = $this->extractNodeData($node, $position, 'L23');
                $columnData[$position]['l23_nodes'][] = $nodeData;

                // Extract edges with layer context
                $nodeEdges = $this->extractRelationships($node, $nodeData);
                $allEdges = array_merge($allEdges, $nodeEdges);
            }

            // Collect L5 nodes
            $l5Nodes = $column->getL5()->getAllNodes();
            foreach ($l5Nodes as $node) {
                $nodeData = $this->extractNodeData($node, $position, 'L5');
                $columnData[$position]['l5_nodes'][] = $nodeData;

                // Extract edges with layer context
                $nodeEdges = $this->extractRelationships($node, $nodeData);
                $allEdges = array_merge($allEdges, $nodeEdges);
            }
        }

        // Sort by position
        ksort($columnData);

        // Generate column subgraphs
        foreach ($columnData as $position => $data) {
            $dot .= $this->generateColumnSubgraph($position, $data);
        }

        // Generate all edges (outside subgraphs for proper rendering)
        $dot .= "  // Edges\n";
        foreach ($allEdges as $edge) {
            $dot .= $this->generateDOTEdge($edge);
        }

        $dot .= "}\n";

        return $dot;
    }

    /**
     * Generate a column subgraph with L23 and L5 layer subgraphs
     *
     * @param  int  $position  Column position
     * @param  array  $data  Column data with l23_nodes and l5_nodes
     * @return string DOT subgraph definition
     */
    private function generateColumnSubgraph(int $position, array $data): string
    {
        $clusterName = "cluster_col_{$position}";
        $dot = "  subgraph {$clusterName} {\n";
        $dot .= "    label=\"Column {$position}\";\n";
        $dot .= "    style=rounded;\n";
        $dot .= "    bgcolor=\"#f0f0f0\";\n";
        $dot .= "    fontsize=14;\n";
        $dot .= "    fontname=\"Arial Bold\";\n\n";

        // L23 Layer subgraph
        $l23ClusterName = "cluster_col_{$position}_l23";
        $dot .= "    subgraph {$l23ClusterName} {\n";
        $dot .= "      label=\"L23 (Input Layer)\";\n";
        $dot .= "      style=filled;\n";
        $dot .= "      bgcolor=\"#e3f2fd\";\n";
        $dot .= "      fontsize=12;\n";
        $dot .= "      fontname=\"Arial\";\n";
        $dot .= "      node [fillcolor=lightblue];\n\n";

        // Add L23 nodes
        foreach ($data['l23_nodes'] as $nodeData) {
            $dot .= '      '.$this->generateDOTNode($nodeData);
        }

        $dot .= "    }\n\n";

        // L5 Layer subgraph
        $l5ClusterName = "cluster_col_{$position}_l5";
        $dot .= "    subgraph {$l5ClusterName} {\n";
        $dot .= "      label=\"L5 (Construction Layer)\";\n";
        $dot .= "      style=filled;\n";
        $dot .= "      bgcolor=\"#e8f5e9\";\n";
        $dot .= "      fontsize=12;\n";
        $dot .= "      fontname=\"Arial\";\n";
        $dot .= "      node [fillcolor=lightgreen];\n\n";

        // Add L5 nodes
        foreach ($data['l5_nodes'] as $nodeData) {
            $dot .= '      '.$this->generateDOTNode($nodeData);
        }

        $dot .= "    }\n";
        $dot .= "  }\n\n";

        return $dot;
    }

    /**
     * Extract node data for export
     *
     * @param  Node  $node  Node instance
     * @param  int  $position  Column position
     * @param  string  $layer  Layer name (L23 or L5)
     * @return array Node data
     */
    private function extractNodeData(Node $node, int $position, string $layer): array
    {
        return [
            'id' => $node->id,
            'position' => $position,
            'layer' => $layer,
            'class' => $node instanceof Node ? 'Node' : 'Node',
            'node_type' => $node->metadata['node_type'] ?? 'unknown',
            'metadata' => $node->metadata,
            'threshold' => $node instanceof Node ? $node->threshold : null,
            'is_activated' => $node instanceof Node ? $node->isFired() : $node->isActivated(),
        ];
    }

    /**
     * Extract relationships (edges) from node
     *
     * Categorizes edges into different types:
     * - ACTIVATES: Basic activation flow (L23→L5 or within layer)
     * - FEEDBACK: L5→L23 (construction creates L23 construction node)
     * - PREDICTION: L5→L23 (partial construction creates predicted node)
     * - CONFIRMATION: L23→L23 (predicted node confirmed by token)
     *
     * @param  Node  $node  Node instance
     * @param  array  $nodeData  Node data with metadata and layer info
     * @return array Array of relationship data
     */
    private function extractRelationships(Node $node, array $nodeData): array
    {
        $relationships = [];
        $sourceLayer = $nodeData['layer'];
        $sourceType = $nodeData['node_type'];
        $metadata = $nodeData['metadata'];

        // Extract output connections
        // All link types (ACTIVATES, FEEDBACK, PREDICTION, CONFIRMATION) are now
        // stored as bidirectional connections, so we only need to iterate outputs
        foreach ($node->getOutputNodes() as $outputId => $outputNode) {
            // Determine edge type based on source/target layers and metadata
            $edgeType = $this->determineEdgeType(
                $sourceLayer,
                $sourceType,
                $metadata,
                $outputNode,
                $outputId
            );

            $relationships[] = [
                'from' => $nodeData['id'],
                'to' => $outputId,
                'type' => $edgeType,
                'metadata' => [],
            ];
        }

        return $relationships;
    }

    /**
     * Determine edge type based on source and target characteristics
     *
     * @param  string  $sourceLayer  Source node layer (L23 or L5)
     * @param  string  $sourceType  Source node type
     * @param  array  $sourceMetadata  Source node metadata
     * @param  object  $targetNode  Target node object
     * @param  string  $targetId  Target node ID
     * @return string Edge type
     */
    private function determineEdgeType(
        string $sourceLayer,
        string $sourceType,
        array $sourceMetadata,
        object $targetNode,
        string $targetId
    ): string {
        $targetMetadata = $targetNode->metadata ?? [];
        $targetType = $targetMetadata['node_type'] ?? 'unknown';

        // FEEDBACK: L5 construction → L23 construction (compositional feedback)
        if ($sourceLayer === 'L5' &&
            $sourceType === 'construction' &&
            ($targetMetadata['is_from_l5_feedback'] ?? false)) {
            return 'FEEDBACK';
        }

        // PREDICTION: L5 partial → L23 predicted node
        if ($sourceLayer === 'L5' &&
            str_contains($sourceType, 'partial') &&
            ($targetMetadata['is_predicted'] ?? false)) {
            return 'PREDICTION';
        }

        // CONFIRMATION: Predicted L23 node → confirming L23 node across columns
        if (($sourceMetadata['is_predicted'] ?? false) &&
            ($sourceMetadata['prediction_confirmed'] ?? false)) {
            return 'CONFIRMATION';
        }

        // Default: ACTIVATES (standard activation flow)
        return 'ACTIVATES';
    }

    /**
     * Generate Cypher CREATE statement for node
     *
     * @param  array  $nodeData  Node data
     * @return string Cypher statement
     */
    private function generateCypherNode(array $nodeData): string
    {
        $id = $this->escapeCypher($nodeData['id']);
        $position = $nodeData['position'];
        $layer = $nodeData['layer'];
        $class = $nodeData['class'];
        $nodeType = $this->escapeCypher($nodeData['node_type']);
        $isActivated = $nodeData['is_activated'] ? 'true' : 'false';

        // Build properties
        $properties = [
            "id: '{$id}'",
            "position: {$position}",
            "layer: '{$layer}'",
            "class: '{$class}'",
            "node_type: '{$nodeType}'",
            "is_activated: {$isActivated}",
        ];

        // Add threshold for Nodes
        if ($nodeData['threshold'] !== null) {
            $properties[] = 'threshold: '.$nodeData['threshold'];
        }

        // Add selected metadata fields
        if (isset($nodeData['metadata']['value'])) {
            $value = $this->escapeCypher($nodeData['metadata']['value']);
            $properties[] = "value: '{$value}'";
        }

        if (isset($nodeData['metadata']['name'])) {
            $name = $this->escapeCypher($nodeData['metadata']['name']);
            $properties[] = "name: '{$name}'";
        }

        $propertiesStr = implode(', ', $properties);

        return "CREATE (n{$id}:CLNNode {{$propertiesStr}});\n";
    }

    /**
     * Generate Cypher CREATE statement for relationship
     *
     * @param  array  $relationship  Relationship data
     * @return string Cypher statement
     */
    private function generateCypherRelationship(array $relationship): string
    {
        $from = $this->escapeCypher($relationship['from']);
        $to = $this->escapeCypher($relationship['to']);
        $type = $relationship['type'];

        return "MATCH (a:CLNNode {{id: '{$from}'}}), (b:CLNNode {{id: '{$to}'}})\n".
               "CREATE (a)-[:{$type}]->(b);\n";
    }

    /**
     * Generate DOT node statement
     *
     * Color scheme for node states:
     * - Construction (confirmed): Green (#4caf50)
     * - Partial construction: Yellow (#fff59d)
     * - Predicted node: Purple/Violet (#ba68c8) with dashed border
     * - Confirmed prediction: Orange (#ff9800) with bold border
     * - L23 input nodes: Light blue (#e3f2fd)
     * - Other nodes: Default (white or layer default)
     *
     * @param  array  $nodeData  Node data
     * @return string DOT statement
     */
    private function generateDOTNode(array $nodeData): string
    {
        $id = $this->escapeDOT($nodeData['id']);
        $nodeType = $nodeData['node_type'];
        $metadata = $nodeData['metadata'];

        // Build label with node type and value
        $label = $nodeType;

        // Add value if available
        if (isset($metadata['value'])) {
            $value = $this->escapeDOT($metadata['value']);
            $label .= "\\n{$value}";
        } elseif (isset($metadata['name'])) {
            $name = $this->escapeDOT($metadata['name']);
            $label .= "\\n{$name}";
        }

        // Add activation status for partials
        if ($nodeType === 'partial_construction' || $nodeType === 'construction') {
            $isActivated = $nodeData['is_activated'] ? '✓' : '○';
            $label .= "\\n{$isActivated}";
        }

        // Shape based on class and type
        $shape = match ($nodeData['class']) {
            'Node' => 'box',
//            'Node' => 'ellipse',
            default => 'box',
        };

        // Determine color and style based on node state
        $fillcolor = $this->determineNodeColor($nodeData);
        $style = 'filled';
        $extraAttrs = '';

        // Confirmed prediction: orange with bold border
        if (($metadata['is_predicted'] ?? false) && ($metadata['prediction_confirmed'] ?? false)) {
            $style = 'filled,bold';
            $extraAttrs = ', penwidth=3';
        }
        // Predicted node (not yet confirmed): purple with dashed border
        elseif ($metadata['is_predicted'] ?? false) {
            $style = 'filled,dashed';
            $extraAttrs = ', penwidth=2';
        }

        return "\"{$id}\" [label=\"{$label}\", shape={$shape}, style=\"{$style}\", fillcolor=\"{$fillcolor}\"{$extraAttrs}];\n";
    }

    /**
     * Determine node fill color based on node state
     *
     * @param  array  $nodeData  Node data
     * @return string Hex color code
     */
    private function determineNodeColor(array $nodeData): string
    {
        $nodeType = $nodeData['node_type'];
        $metadata = $nodeData['metadata'];
        $layer = $nodeData['layer'];

        // Predicted node (not yet confirmed)
        if ($metadata['is_predicted'] ?? false) {
            return '#ba68c8'; // Purple/Violet
        }

        // Confirmed prediction (overrides other colors)
        if (($metadata['is_predicted'] ?? false) && ($metadata['prediction_confirmed'] ?? false)) {
            return '#ff9800'; // Orange
        }

        // Partial construction
        if ($nodeType === 'partial_construction') {
            return '#fff59d'; // Yellow
        }

        // Confirmed construction
        if ($nodeType === 'construction' && $nodeData['is_activated']) {
            return '#4caf50'; // Green
        }

        // Inactive construction
        if ($nodeType === 'construction' && ! $nodeData['is_activated']) {
            return '#c8e6c9'; // Light green
        }

        // L23 input layer nodes (word, lemma, pos, feature)
        if ($layer === 'L23' && in_array($nodeType, ['word', 'lemma', 'pos', 'feature', 'ce_label'])) {
            return '#e3f2fd'; // Light blue
        }

        // L5 layer default (for other L5 nodes)
        if ($layer === 'L5') {
            return '#e8f5e9'; // Light green background
        }

        // Default (white)
        return '#ffffff';
    }

    /**
     * Generate DOT edge statement with type-specific styling
     *
     * Edge types and their visual styles:
     * - ACTIVATES: Gray solid arrow (standard activation)
     * - FEEDBACK: Green dashed arrow (L5→L23 compositional feedback)
     * - PREDICTION: Blue dotted arrow (L5→L23 prediction)
     * - CONFIRMATION: Orange bold arrow (L23→L23 prediction confirmation)
     *
     * @param  array  $edge  Edge data
     * @return string DOT statement
     */
    private function generateDOTEdge(array $edge): string
    {
        $from = $this->escapeDOT($edge['from']);
        $to = $this->escapeDOT($edge['to']);
        $type = $edge['type'] ?? 'ACTIVATES';

        // Style based on edge type
        $attrs = match ($type) {
            'FEEDBACK' => ' [color="#4caf50", style=dashed, penwidth=2.0, label="feedback"]',
            'PREDICTION' => ' [color="#2196f3", style=dotted, penwidth=2.0, label="predict"]',
            'CONFIRMATION' => ' [color="#ff9800", style=bold, penwidth=2.5, label="confirm"]',
            'ACTIVATES' => ' [color="#666666", penwidth=1.5]',
            default => ' [color="#666666", penwidth=1.5]',
        };

        return "  \"{$from}\" -> \"{$to}\"{$attrs};\n";
    }

    /**
     * Escape string for Cypher
     *
     * @param  string  $str  String to escape
     * @return string Escaped string
     */
    private function escapeCypher(string $str): string
    {
        return str_replace("'", "\\'", $str);
    }

    /**
     * Escape string for DOT
     *
     * @param  string  $str  String to escape
     * @return string Escaped string
     */
    private function escapeDOT(string $str): string
    {
        return str_replace('"', '\\"', $str);
    }
}
