<?php

namespace App\Services\CLN;

use App\Models\CLN\Binding;
use App\Models\CLN\FunctionalColumn;
use App\Models\CLN\PatternNodeType;
use App\Models\CLN\RuntimeGraph;

/**
 * Parse Graph Exporter
 *
 * Exports the parsing result as a bottom-up graph showing:
 * - Words at each timestep
 * - Constructions activated at each timestep
 * - Links showing which words activated which constructions
 * - Bindings showing which elements filled which roles
 */
class ParseGraphExporter
{
    /**
     * Export parse results to DOT format
     *
     * @param  array  $wordData  Array of word info with timestamps
     * @param  array  $activationHistory  [time => [columnId => activation]]
     * @param  array  $bindings  Array of Binding objects
     * @param  array  $columnNames  [columnId => name]
     * @param  string  $outputPath  Path to save DOT file
     * @return string DOT file path
     */
    public function exportParseToDot(
        RuntimeGraph $graph,
        array $wordData,
        array $activationHistory,
        array $bindings,
        array $columnNames,
        string $outputPath
    ): string {
        print_r($activationHistory);
        $dot = [];
        $dot[] = 'digraph ParseGraph {';
        $dot[] = '  rankdir=TB;';  // Top to bottom (time flows down)
        $dot[] = '  node [style=filled, fontname="Arial", fontsize=10];';
        $dot[] = '  edge [fontname="Arial", fontsize=9];';
        $dot[] = '';

        // Add title
        $sentence = implode(' ', array_column($wordData, 'word'));
        $dot[] = '  label="Parse Graph: '.addslashes($sentence).'";';
        $dot[] = '  labelloc="t";';
        $dot[] = '  fontsize=14;';
        $dot[] = '';

        // Group nodes by timestep for better layout
        foreach ($activationHistory as $time => $activations) {
            $dot[] = "  subgraph cluster_t{$time} {";
            $dot[] = "    label=\"T={$time}\";";
            $dot[] = '    style=dashed;';
            $dot[] = '    color=gray;';
            $dot[] = '';

            // Add word node if present
            if (isset($wordData[$time])) {
                $word = $wordData[$time];
                $wordNode = "word_t{$time}";
                $label = "{$word['word']}\\n({$word['pos']})";
                $dot[] = "    {$wordNode} [label=\"{$label}\", shape=box, fillcolor=\"#90EE90\"];";
            }

            // Add construction nodes activated at this time
            foreach ($activations as $columnId => $activation) {
                $column = $graph->getColumnById($columnId);
                if ($activation >= 0.3) {  // Only show significant activations
                    $name = $columnNames[$columnId] ?? $columnId;
                    $constructionNode = "c{$columnId}_t{$time}";
                    $color = $this->getActivationColor($activation);
                    $label = "{$name}\\n{$columnId}\\n(L5={$activation})";
                    $shape = $this->getNodeShape($column);
                    $dot[] = "    {$constructionNode} [label=\"{$label}\", shape={$shape}, fillcolor=\"{$color}\"];";
                }
            }

            $dot[] = '  }';
            $dot[] = '';
        }

        // Add edges showing activation flow
        $dot[] = '  // Activation links (word -> construction)';
        foreach ($activationHistory as $time => $activations) {
            if (! isset($wordData[$time])) {
                continue;
            }

            $wordNode = "word_t{$time}";

            // Find constructions activated at this time
            foreach ($activations as $columnId => $activation) {
                if ($activation >= 0.3) {
                    $constructionNode = "c{$columnId}_t{$time}";
                    $dot[] = "  {$wordNode} -> {$constructionNode} [color=\"#228B22\", label=\"activates\"];";
                }
            }
        }
        $dot[] = '';

        // Add edges showing construction hierarchy
        $dot[] = '  // Construction hierarchy (feedforward)';
        foreach ($activationHistory as $time => $activations) {
            foreach ($activations as $columnId => $activation) {
                if ($activation < 0.3) {
                    continue;
                }

                $sourceNode = "c{$columnId}_t{$time}";

                // Check if this column activated higher-level constructions at the same or next timestep
                for ($t = $time; $t <= $time + 1; $t++) {
                    if (! isset($activationHistory[$t])) {
                        continue;
                    }

                    foreach ($activationHistory[$t] as $targetId => $targetActivation) {
                        if ($targetActivation < 0.3) {
                            continue;
                        }
                        if ($targetId === $columnId) {
                            continue;
                        }

                        // Check if there's a relationship (simplified - shows potential links)
                        $targetNode = "c{$targetId}_t{$t}";
                        // We'd need pathway info to be more precise
                        // For now, show hierarchical structure based on naming
                        $sourceName = $columnNames[$columnId] ?? '';
                        $targetName = $columnNames[$targetId] ?? '';

                        // If source is part of target (e.g., NOUN -> ARG, ARG -> SUBJECT)
                        if ($this->isLikelyChild($sourceName, $targetName)) {
                            $dot[] = "  {$sourceNode} -> {$targetNode} [color=\"#4169E1\", style=dashed, label=\"feeds\"];";
                        }
                    }
                }
            }
        }
        $dot[] = '';

        // Add binding edges
        $dot[] = '  // Bindings (filler -> slot)';
        foreach ($bindings as $binding) {
            $fillerId = $binding->filler->id;
            $slotId = $binding->slot->id;
            $time = $binding->boundAtTime;
            $role = $binding->role;

            // Find the timestep where filler was active
            $fillerNode = null;
            foreach ($activationHistory as $t => $activations) {
                if (isset($activations[$fillerId]) && $activations[$fillerId] >= 0.3) {
                    $fillerNode = "c{$fillerId}_t{$t}";
                    break;
                }
            }

            $slotNode = "c{$slotId}_t{$time}";

            if ($fillerNode && $slotNode) {
                $color = $role === 'left' ? '#FF6347' : '#9370DB';
                $dot[] = "  {$fillerNode} -> {$slotNode} [color=\"{$color}\", style=bold, label=\"{$role}\"];";
            }
        }
        $dot[] = '';

        $dot[] = '}';

        // Write to file
        $dotContent = implode("\n", $dot);
        file_put_contents($outputPath, $dotContent);

        return $outputPath;
    }

    /**
     * Get color based on activation level
     */
    private function getActivationColor(float $activation): string
    {
        if ($activation >= 0.8) {
            return '#32CD32';  // Lime green
        } elseif ($activation >= 0.5) {
            return '#FFD700';  // Gold
        } else {
            return '#FFA500';  // Orange
        }
    }

    /**
     * Get node shape based on construction type
     */
    private function getNodeShape(FunctionalColumn $column): string
    {
        $name = $column->name;
        // Heuristic: uppercase names are likely POS/categories
        if ($column->type == PatternNodeType::AND) {
            return 'box';
        } else {
            return 'ellipse';
        }
        //        return 'box';
    }

    /**
     * Check if source is likely a child of target (heuristic)
     */
    private function isLikelyChild(string $source, string $target): bool
    {
        // Heuristic: if target name contains source name
        // e.g., NOUN is child of ARG, VERB is child of PRED
        if (empty($source) || empty($target)) {
            return false;
        }

        // Common patterns
        $patterns = [
            'NOUN' => ['ARG', 'MOD'],
            'VERB' => ['PRED'],
            'DET' => ['MOD'],
            'ADJ' => ['MOD'],
            'ARG' => ['SUBJECT', 'OBJECT'],
            'PRED' => ['SUBJECT', 'OBJECT', 'CLAUSE'],
            'SUBJECT' => ['CLAUSE'],
            'OBJECT' => ['CLAUSE'],
        ];

        if (isset($patterns[$source])) {
            return in_array($target, $patterns[$source]);
        }

        return false;
    }

    /**
     * Render DOT file to PNG using Graphviz
     */
    public function renderToPng(string $dotPath, string $pngPath): bool
    {
        $command = 'dot -Tpng '.escapeshellarg($dotPath).' -o '.escapeshellarg($pngPath).' 2>&1';
        exec($command, $output, $returnCode);

        return $returnCode === 0;
    }
}
