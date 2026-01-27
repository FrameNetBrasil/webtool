<?php

namespace App\Console\Commands\CLN;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Visualize RNT Pattern Graph Command
 *
 * Generates a DOT file (GraphViz format) for visualizing the RNT pattern graph.
 * Supports visualizing the entire graph or a subgraph starting from a specific node.
 *
 * Usage:
 *   php artisan rnt:visualize-pattern-graph
 *   php artisan rnt:visualize-pattern-graph --node=5
 *   php artisan rnt:visualize-pattern-graph --pattern=4096
 *   php artisan rnt:visualize-pattern-graph --output=my-rnt-graph.dot
 */
class VisualizeRNTPatternGraph extends Command
{
    protected $signature = 'cln:visualize-pattern-graph
        {--node= : Start visualization from a specific node ID (shows subgraph)}
        {--pattern= : Show only edges for a specific pattern ID}
        {--depth=999 : Maximum depth when starting from a node (default: unlimited)}
        {--output=rnt-graph.dot : Output filename}
        {--render : Attempt to render to PNG using GraphViz (requires dot command)}';

    protected $description = 'Generate DOT file for visualizing the RNT pattern graph (DATA/OR/AND/SEQUENCER nodes)';

    private array $visitedNodes = [];

    private array $visitedEdges = [];

    public function handle(): int
    {
        $this->info('RNT Pattern Graph Visualizer');
        $this->info('Node types: DATA (yellow box), OR (orange oval), AND (purple box), SEQUENCER (cyan hexagon)');
        $this->info('SEQUENCER edge colors: LEFT (blue), HEAD (red), RIGHT (green)');
        $this->newLine();

        // Validate options
        if ($this->option('node') && $this->option('pattern')) {
            $this->error('Cannot specify both --node and --pattern options');

            return self::FAILURE;
        }

        try {
            // Generate DOT content
            $dot = $this->generateDot();

            // Save to file
            $outputPath = $this->option('output');
            file_put_contents($outputPath, $dot);

            $this->info("✓ DOT file generated: {$outputPath}");
            $this->line('  Nodes: '.count($this->visitedNodes));
            $this->line('  Edges: '.count($this->visitedEdges));

            // Optionally render to PNG
            if ($this->option('render')) {
                $this->renderToPng($outputPath);
            }

            $this->newLine();
            $this->line('To visualize:');
            $this->line("  dot -Tpng {$outputPath} -o rnt-graph.png");
            $this->line("  dot -Tsvg {$outputPath} -o rnt-graph.svg");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to generate visualization: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    /**
     * Generate DOT format content
     */
    private function generateDot(): string
    {
        $dot = "digraph RNTPatternGraph {\n";
        $dot .= "  rankdir=LR;\n";
        $dot .= "  node [shape=box, style=rounded];\n";
        $dot .= "  edge [fontsize=10];\n\n";
        $dot .= "  // RNT Graph: DATA (yellow box), OR (orange oval), AND (purple box), SEQUENCER (cyan hexagon)\n\n";

        if ($this->option('node')) {
            // Subgraph from specific node
            $dot .= $this->generateSubgraph((int) $this->option('node'));
        } elseif ($this->option('pattern')) {
            // Show only specific pattern
            $dot .= $this->generatePatternGraph((int) $this->option('pattern'));
        } else {
            // Full graph (only RNT nodes)
            $dot .= $this->generateFullGraph();
        }

        $dot .= "}\n";

        return $dot;
    }

    /**
     * Generate full RNT graph visualization
     */
    private function generateFullGraph(): string
    {
        $dot = '';

        // Get only RNT nodes (DATA, OR, AND, SEQUENCER)
        $nodes = DB::table('parser_pattern_node')
            ->whereIn('type', ['DATA', 'OR', 'AND', 'SEQUENCER', 'SOM', 'VIP'])
            ->orderBy('id')
            ->get();

        // Group nodes by construction (for SeqColumn clustering)
        $seqColumnNodes = $this->groupSeqColumnNodes($nodes);
        $otherNodes = [];

        foreach ($nodes as $node) {
            $inSeqColumn = false;
            foreach ($seqColumnNodes as $construction => $clusterNodes) {
                if (in_array($node->id, array_column($clusterNodes, 'id'))) {
                    $inSeqColumn = true;
                    break;
                }
            }

            if (! $inSeqColumn) {
                $otherNodes[] = $node;
            }
        }

        // Output SeqColumn clusters first
        foreach ($seqColumnNodes as $construction => $clusterNodes) {
            $sanitizedName = preg_replace('/[^a-zA-Z0-9_]/', '_', $construction);
            $dot .= "  subgraph cluster_{$sanitizedName} {\n";
            $dot .= "    label=\"{$construction}\";\n";
            $dot .= "    style=rounded;\n";
            $dot .= "    bgcolor=gray99;\n";
            $dot .= "    fontsize=12;\n\n";

            foreach ($clusterNodes as $node) {
                // Format node with cluster indentation
                $nodeFormat = $this->formatNode($node);
                $dot .= '  '.$nodeFormat; // Add extra indentation
                $this->visitedNodes[$node->id] = true;
            }

            $dot .= "  }\n\n";
        }

        // Output other nodes (DATA, AND nodes not in clusters)
        foreach ($otherNodes as $node) {
            $dot .= $this->formatNode($node);
            $this->visitedNodes[$node->id] = true;
        }

        $dot .= "\n";

        // Get edges connected to RNT nodes
        $edges = DB::table('parser_pattern_edge as e')
            ->join('parser_pattern_node as fn', 'e.from_node_id', '=', 'fn.id')
            ->join('parser_pattern_node as tn', 'e.to_node_id', '=', 'tn.id')
            // ->join('parser_construction_v4 as c', 'e.pattern_id', '=', 'c.idConstruction')
            // ->select('e.*', 'c.name as pattern_name', 'fn.type as from_type', 'tn.type as to_type')
            ->select('e.*', 'tn.construction_name as pattern_name', 'fn.type as from_type', 'tn.type as to_type')
            ->whereIn('fn.type', ['DATA', 'OR', 'AND', 'SEQUENCER', 'SOM', 'VIP'])
            ->whereIn('tn.type', ['DATA', 'OR', 'AND', 'SEQUENCER', 'SOM', 'VIP'])
            ->orderBy('e.id')
            ->get();

        foreach ($edges as $edge) {
            $dot .= $this->formatEdge($edge);
            $this->visitedEdges[$edge->id] = true;
        }

        return $dot;
    }

    /**
     * Generate subgraph starting from a specific node
     */
    private function generateSubgraph(int $startNodeId): string
    {
        $this->info("Generating subgraph from node {$startNodeId}...");

        $dot = '';
        $maxDepth = (int) $this->option('depth');

        // BFS traversal from start node
        $queue = [[$startNodeId, 0]]; // [nodeId, depth]
        $this->visitedNodes = [];

        while (! empty($queue)) {
            [$currentNodeId, $depth] = array_shift($queue);

            if (isset($this->visitedNodes[$currentNodeId]) || $depth > $maxDepth) {
                continue;
            }

            // Add node
            $node = DB::table('parser_pattern_node')
                ->where('id', $currentNodeId)
                ->whereIn('type', ['DATA', 'OR', 'AND', 'SEQUENCER'])
                ->first();

            if (! $node) {
                continue;
            }

            $dot .= $this->formatNode($node);
            $this->visitedNodes[$currentNodeId] = true;

            // Get outgoing edges
            $edges = DB::table('parser_pattern_edge as e')
                ->join('parser_construction_v4 as c', 'e.pattern_id', '=', 'c.idConstruction')
                ->join('parser_pattern_node as tn', 'e.to_node_id', '=', 'tn.id')
                ->select('e.*', 'c.name as pattern_name', 'tn.type as to_type')
                ->where('e.from_node_id', $currentNodeId)
                ->whereIn('tn.type', ['DATA', 'OR', 'AND', 'SEQUENCER'])
                ->get();

            foreach ($edges as $edge) {
                if (! isset($this->visitedEdges[$edge->id])) {
                    $dot .= $this->formatEdge($edge);
                    $this->visitedEdges[$edge->id] = true;

                    // Add target node to queue
                    $queue[] = [$edge->to_node_id, $depth + 1];
                }
            }
        }

        return $dot;
    }

    /**
     * Generate graph for a specific pattern
     */
    private function generatePatternGraph(int $patternId): string
    {
        $pattern = DB::table('parser_construction_v4')
            ->where('idConstruction', $patternId)
            ->first();

        if (! $pattern) {
            throw new \Exception("Pattern {$patternId} not found");
        }

        $this->info("Generating graph for pattern: {$pattern->name} (ID: {$patternId})");

        $dot = "  // Pattern: {$pattern->name}\n";
        $dot .= "  // Original: {$pattern->pattern}\n\n";

        // Get all RNT nodes used by this pattern
        $nodeIds = DB::table('parser_pattern_edge')
            ->where('pattern_id', $patternId)
            ->select('from_node_id as node_id')
            ->union(
                DB::table('parser_pattern_edge')
                    ->where('pattern_id', $patternId)
                    ->select('to_node_id as node_id')
            )
            ->pluck('node_id')
            ->unique();

        $nodes = DB::table('parser_pattern_node')
            ->whereIn('id', $nodeIds)
            ->whereIn('type', ['DATA', 'OR', 'AND', 'SEQUENCER', 'SOM'])
            ->get();

        foreach ($nodes as $node) {
            $dot .= $this->formatNode($node);
            $this->visitedNodes[$node->id] = true;
        }

        $dot .= "\n";

        // Get all edges for this pattern
        $edges = DB::table('parser_pattern_edge as e')
            ->join('parser_construction_v4 as c', 'e.pattern_id', '=', 'c.idConstruction')
            ->select('e.*', 'c.name as pattern_name')
            ->where('e.pattern_id', $patternId)
            ->orderBy('e.sequence')
            ->get();

        foreach ($edges as $edge) {
            $dot .= $this->formatEdge($edge);
            $this->visitedEdges[$edge->id] = true;
        }

        return $dot;
    }

    /**
     * Group nodes into Column structures
     *
     * Returns array: construction_name => [SEQUENCER node, left OR, head OR, right OR]
     */
    private function groupSeqColumnNodes($allNodes): array
    {
        $seqColumns = [];

        // Find all SEQUENCER nodes
        $sequencerNodes = [];
        foreach ($allNodes as $node) {
            $spec = json_decode($node->specification, true);
            if (($node->type == 'AND')) {
                if ($spec['construction_type'] == 'mwe') {
                    $sequencerNodes[] = $node;
                }
            }
        }

        foreach ($sequencerNodes as $sequencerNode) {
            $spec = json_decode($sequencerNode->specification, true);
            $constructionName = $spec['construction_name'] ?? null;

            if (! $constructionName) {
                continue;
            }

            // Find the OR nodes for this construction
            $orNodes = array_filter($allNodes->toArray(), function ($n) use ($constructionName) {
                if ($n->type !== 'OR') {
                    return false;
                }

                $spec = json_decode($n->specification, true);

                return ($spec['construction_name'] ?? null) === $constructionName;
            });

            // Find the AND nodes for this construction
            $andNodes = array_filter($allNodes->toArray(), function ($n) use ($constructionName) {
                if ($n->type !== 'AND') {
                    return false;
                }

                $spec = json_decode($n->specification, true);

                return ($spec['construction_name'] ?? null) === $constructionName;
            });

            // Should have exactly 3 OR nodes (left, head, right)
            // if (count($orNodes) === 3) {
            $seqColumns[$constructionName] = array_merge(
                [$sequencerNode],
                array_values($orNodes),
                array_values($andNodes)
            );
            // }
        }

        return $seqColumns;
    }

    /**
     * Format a node for DOT output
     */
    private function formatNode($node): string
    {
        $label = $this->getNodeLabel($node);
        $style = $this->getNodeStyle($node);

        return "  node_{$node->id} [label=\"{$label}\"{$style}];\n";
    }

    /**
     * Get node label for visualization
     */
    private function getNodeLabel($node): string
    {
        $spec = json_decode($node->specification, true);

        if ($node->type === 'DATA') {
            $dataType = $spec['dataType'] ?? 'unknown';

            // Show the actual value/name from the specification
            return match ($dataType) {
                'literal' => "\\\"{$spec['value']}\\\"",
                'slot' => "{{$spec['pos']}}\\n{$node->id}",
                'ce_slot' => "{{$spec['ce_label']}}\\n({$spec['ce_tier']})",
                'combined_slot' => "{{$spec['pos']}@{$spec['ce_label']}}",
                'wildcard' => '{*}',
                default => "DATA\\n{$dataType}",
            };
        } elseif ($node->type === 'OR') {
            // For OR nodes in SeqColumn: use position label (l/h/r)
            $position = $spec['position'] ?? null;
            $constructionName = $spec['construction_name'] ?? 'OR';

            if ($position) {
                // SeqColumn OR nodes: show position abbreviation
                $posLabel = match ($position) {
                    'left' => 'l',
                    'head' => 'h',
                    'right' => 'r',
                    default => $position,
                };

                return "{$constructionName}\\n{$node->id}\\n[{$posLabel}]";
            }

            return $constructionName;
        } elseif ($node->type === 'AND') {
            // Use construction name if available
            $constructionName = $spec['construction_name'] ?? null;
            if ($constructionName) {
                return "{$constructionName}\\n{$node->id}";
            }

            $position = $spec['position'] ?? 'unknown';

            return "AND\\n[{$position}]";
        } elseif ($node->type === 'SEQUENCER') {
            // Use construction name instead of "SEQUENCER [position]"
            $constructionName = $spec['construction_name'] ?? 'SEQUENCER';

            return $constructionName;
        } elseif ($node->type === 'SOM') {
            $constructionName = $spec['construction_name'];

            return $constructionName;
        } elseif ($node->type === 'VIP') {
            $constructionName = $spec['construction_name'];

            return $constructionName;
        }

        return $node->type;
    }

    /**
     * Get node style attributes
     */
    private function getNodeStyle($node): string
    {
        $styles = [];

        // Color and shape by type
        if ($node->type === 'DATA') {
            $styles[] = 'fillcolor=lightyellow, style=filled';
            $styles[] = 'shape=box';
        } elseif ($node->type === 'OR') {
            $styles[] = 'fillcolor=orange, style=filled';
            $styles[] = 'shape=oval';
        } elseif ($node->type === 'AND') {
            $styles[] = 'fillcolor=plum, style=filled';
            $styles[] = 'shape=box';
        } elseif ($node->type === 'SEQUENCER') {
            $styles[] = 'fillcolor=cyan, style=filled';
            $styles[] = 'shape=hexagon';
        } elseif ($node->type === 'SOM') {
            $styles[] = 'fillcolor=cyan, style=filled';
            $styles[] = 'shape=diamond';
        } elseif ($node->type === 'VIP') {
            $styles[] = 'fillcolor=green, style=filled';
            $styles[] = 'shape=triangle';
        }

        return $styles ? ', '.implode(', ', $styles) : '';
    }

    /**
     * Format an edge for DOT output
     */
    private function formatEdge($edge): string
    {
        $label = $edge->pattern_name ?? "P{$edge->pattern_id}";

        // Add properties to label and style
        $style = '';
        $color = null;

        if ($edge->properties) {
            $props = json_decode($edge->properties, true);
            if (isset($props['label'])) {
                $label .= '\\n'.$props['label'];
            }

            // SEQUENCER position colors (left/head/right)
            if (isset($props['position'])) {
                $position = $props['position'];
                $label .= '\\n['.$position.']';

                // Different colors for each position
                $color = match ($position) {
                    'left' => 'blue',      // Left element (optional)
                    'head' => 'red',       // Head element (mandatory core)
                    'right' => 'green',    // Right element (optional)
                    default => 'black',
                };
            }

            // Render optional edges as dashed lines (if not already colored by position)
            if (isset($props['optional']) && $props['optional'] && ! $color) {
                $style = ', style=dashed, color=gray';
            }
        }

        // Apply position color if set
        if ($color) {
            $lineStyle = (isset($props['optional']) && $props['optional']) ? 'dashed' : 'solid';
            $style = ", color={$color}, style={$lineStyle}, penwidth=2";
        }

        return "  node_{$edge->from_node_id} -> node_{$edge->to_node_id} [label=\"{$label}\"{$style}];\n";
    }

    /**
     * Render DOT file to PNG using GraphViz
     */
    private function renderToPng(string $dotPath): void
    {
        $pngPath = str_replace('.dot', '.png', $dotPath);

        // Check if dot command is available
        exec('which dot', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->warn('GraphViz dot command not found. Skipping PNG rendering.');
            $this->line('Install GraphViz: sudo apt-get install graphviz');

            return;
        }

        $this->info('Rendering to PNG...');

        $command = "dot -Tpng {$dotPath} -o {$pngPath}";
        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            $this->info("✓ PNG rendered: {$pngPath}");
        } else {
            $this->error('Failed to render PNG');
        }
    }
}
