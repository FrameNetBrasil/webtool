<?php

namespace App\Services\SeqGraph;

use App\Models\SeqGraph\ParseTreeNode;

/**
 * Renders parse result trees to DOT format and image files.
 *
 * Generates GraphViz DOT representations of parse trees showing
 * the hierarchical structure of how patterns combined to parse input.
 */
class ResultGraphRenderer
{
    /**
     * Output directory for generated files.
     */
    private string $outputDir;

    /**
     * Create a new renderer instance.
     *
     * @param  string|null  $outputDir  Directory for output files (defaults to storage/app/seqgraph)
     */
    public function __construct(?string $outputDir = null)
    {
        $this->outputDir = $outputDir ?? storage_path('app/seqgraph');
    }

    /**
     * Render a parse tree to DOT format and optionally to an image.
     *
     * @param  array<ParseTreeNode>  $roots  Root nodes of the parse tree
     * @param  string  $name  Name for the output file
     * @param  bool  $renderImage  Whether to render to PNG image
     * @return array{dot: string, dotPath: string, imagePath: string|null} Paths to generated files
     */
    public function render(array $roots, string $name = 'result', bool $renderImage = true): array
    {
        $this->ensureOutputDir();

        $dot = $this->generateDot($roots);
        $dotPath = $this->writeDotFile($name, $dot);

        $imagePath = null;
        if ($renderImage) {
            $imagePath = $this->renderToImage($dotPath, $name);
        }

        return [
            'dot' => $dot,
            'dotPath' => $dotPath,
            'imagePath' => $imagePath,
        ];
    }

    /**
     * Generate DOT format string for a parse tree.
     *
     * @param  array<ParseTreeNode>  $roots  Root nodes
     * @return string DOT format representation
     */
    public function generateDot(array $roots): string
    {
        $lines = [];
        $lines[] = 'digraph "parse_result" {';
        $lines[] = '    rankdir=TB;';
        $lines[] = '    node [fontname="Helvetica", fontsize=10];';
        $lines[] = '    edge [fontname="Helvetica", fontsize=9];';
        $lines[] = '';

        // Collect all unique nodes using BFS to handle cycles
        $allNodes = [];
        $visited = [];
        $queue = $roots;

        while (! empty($queue)) {
            $node = array_shift($queue);
            if (isset($visited[$node->id])) {
                continue;
            }
            $visited[$node->id] = true;
            $allNodes[] = $node;

            foreach ($node->children as $child) {
                if (! isset($visited[$child->id])) {
                    $queue[] = $child;
                }
            }
        }

        // Generate node definitions
        foreach ($allNodes as $node) {
            $attrs = $this->getNodeAttributes($node);
            $attrStr = $this->formatAttributes($attrs);
            $lines[] = "    \"{$node->id}\" [{$attrStr}];";
        }

        $lines[] = '';

        // Generate edges (track to avoid duplicates)
        $edgesSeen = [];
        foreach ($allNodes as $node) {
            foreach ($node->children as $child) {
                $edgeKey = "{$node->id}->{$child->id}";
                if (isset($edgesSeen[$edgeKey])) {
                    continue;
                }
                $edgesSeen[$edgeKey] = true;

                $edgeAttrs = [];
                if ($child->role !== null) {
                    $edgeAttrs['label'] = $child->role;
                }
                $attrStr = $this->formatAttributes($edgeAttrs);
                $suffix = $attrStr ? " [{$attrStr}]" : '';
                $lines[] = "    \"{$node->id}\" -> \"{$child->id}\"{$suffix};";
            }
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * Maximum depth for text tree generation to prevent infinite recursion.
     */
    private const MAX_TEXT_DEPTH = 20;

    /**
     * Maximum number of lines to generate.
     */
    private const MAX_TEXT_LINES = 500;

    /**
     * Generate a text representation of the parse tree.
     *
     * @param  array<ParseTreeNode>  $roots  Root nodes
     * @param  int  $maxRoots  Maximum number of root trees to render (0 = all)
     * @return string Text tree representation
     */
    public function generateText(array $roots, int $maxRoots = 0): string
    {
        $lines = [];
        $visited = [];
        $truncated = false;

        $rootsToRender = $maxRoots > 0 ? array_slice($roots, 0, $maxRoots) : $roots;

        foreach ($rootsToRender as $index => $root) {
            if ($index > 0) {
                $lines[] = '';
            }
            $this->generateTextNode($root, '', true, $lines, $visited, 0, $truncated);

            if ($truncated || count($lines) >= self::MAX_TEXT_LINES) {
                $lines[] = '';
                $lines[] = '[Output truncated at '.count($lines).' lines]';
                if (count($roots) > count($rootsToRender)) {
                    $lines[] = '['.count($roots).' total root nodes, showing '.($index + 1).']';
                }
                break;
            }
        }

        if (! $truncated && $maxRoots > 0 && count($roots) > $maxRoots) {
            $lines[] = '';
            $lines[] = "[Showing {$maxRoots} of ".count($roots).' root nodes]';
        }

        return implode("\n", $lines);
    }

    /**
     * Generate text for a single node and its descendants.
     *
     * @param  ParseTreeNode  $node  Node to render
     * @param  string  $prefix  Line prefix
     * @param  bool  $isLast  Whether this is the last sibling
     * @param  array<string>  $lines  Output lines array (by reference)
     * @param  array<string, bool>  $visited  Visited node IDs to detect cycles
     * @param  int  $depth  Current depth
     * @param  bool  $truncated  Whether output was truncated (by reference)
     */
    private function generateTextNode(ParseTreeNode $node, string $prefix, bool $isLast, array &$lines, array &$visited, int $depth, bool &$truncated): void
    {
        // Check line limit
        if (count($lines) >= self::MAX_TEXT_LINES) {
            $truncated = true;

            return;
        }

        // Prevent infinite recursion from cycles
        if (isset($visited[$node->id])) {
            $connector = $isLast ? '\\-- ' : '+-- ';
            $lines[] = "{$prefix}{$connector}[CYCLE: {$node->patternName} {$node->id}]";

            return;
        }

        // Prevent excessive depth
        if ($depth > self::MAX_TEXT_DEPTH) {
            $connector = $isLast ? '\\-- ' : '+-- ';
            $lines[] = "{$prefix}{$connector}[TRUNCATED at depth {$depth}]";

            return;
        }

        $visited[$node->id] = true;

        $connector = $isLast ? '\\-- ' : '+-- ';
        $roleStr = $node->role !== null ? "[{$node->role}] " : '';
        $timeStr = "[t:{$node->getSpanString()}]";

        if ($node->isTerminal) {
            $lines[] = "{$prefix}{$connector}{$roleStr}{$node->patternName} {$timeStr} \"{$node->inputValue}\"";
        } else {
            $lines[] = "{$prefix}{$connector}{$roleStr}{$node->patternName} {$timeStr}";
        }

        $childPrefix = $prefix.($isLast ? '    ' : '|   ');
        $childCount = count($node->children);

        foreach ($node->children as $index => $child) {
            $this->generateTextNode($child, $childPrefix, $index === $childCount - 1, $lines, $visited, $depth + 1, $truncated);
            if ($truncated) {
                return;
            }
        }

        // Allow revisiting this node from a different path (for DAGs, not cycles)
        unset($visited[$node->id]);
    }

    /**
     * Get DOT attributes for a node.
     *
     * @param  ParseTreeNode  $node  Node
     * @return array<string, string> DOT attributes
     */
    private function getNodeAttributes(ParseTreeNode $node): array
    {
        $timeStr = "t:{$node->getSpanString()}";

        if ($node->isTerminal) {
            $label = "{$node->patternName}\\n\\\"{$node->inputValue}\\\"\\n{$timeStr}";
            $attrs = [
                'label' => $label,
                'shape' => 'box',
                'style' => 'filled',
                'fillcolor' => 'lightblue',
            ];
        } else {
            $label = "{$node->patternName}\\n{$timeStr}";
            $attrs = [
                'label' => $label,
                'shape' => 'ellipse',
                'style' => 'filled',
                'fillcolor' => $this->getPatternColor($node->patternName),
            ];
        }

        return $attrs;
    }

    /**
     * Get color for a pattern type.
     *
     * @param  string  $patternName  Pattern name
     * @return string Color name
     */
    private function getPatternColor(string $patternName): string
    {
        $colors = [
            'CLAUSE' => 'lightyellow',
            'SUBJECT' => 'lightgreen',
            'OBJECT' => 'lightcoral',
            'PRED' => 'lightsalmon',
            'REF' => 'lightcyan',
            'REL' => 'lavender',
            'PRX' => 'mistyrose',
            'PRX_PRED' => 'honeydew',
        ];

        return $colors[$patternName] ?? 'white';
    }

    /**
     * Format attributes array into DOT attribute string.
     *
     * @param  array<string, string>  $attrs  Attributes
     * @return string Formatted attribute string
     */
    private function formatAttributes(array $attrs): string
    {
        if (empty($attrs)) {
            return '';
        }

        $parts = [];
        foreach ($attrs as $key => $value) {
            $parts[] = "{$key}=\"{$value}\"";
        }

        return implode(', ', $parts);
    }

    /**
     * Write DOT content to a file.
     *
     * @param  string  $name  File name (without extension)
     * @param  string  $dot  DOT content
     * @return string Path to written file
     */
    private function writeDotFile(string $name, string $dot): string
    {
        $filename = $this->sanitizeFilename($name).'.dot';
        $path = $this->outputDir.'/'.$filename;

        file_put_contents($path, $dot);

        return $path;
    }

    /**
     * Render DOT file to PNG image using GraphViz.
     *
     * @param  string  $dotPath  Path to DOT file
     * @param  string  $name  Name for output file
     * @return string|null Path to rendered image, or null if rendering failed
     */
    private function renderToImage(string $dotPath, string $name): ?string
    {
        $filename = $this->sanitizeFilename($name).'.png';
        $imagePath = $this->outputDir.'/'.$filename;

        $command = sprintf('dot -Tpng %s -o %s 2>&1', escapeshellarg($dotPath), escapeshellarg($imagePath));
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($imagePath)) {
            return $imagePath;
        }

        return null;
    }

    /**
     * Ensure output directory exists.
     */
    private function ensureOutputDir(): void
    {
        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Sanitize name for use as filename.
     *
     * @param  string  $name  Name
     * @return string Safe filename
     */
    private function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }

    /**
     * Get the output directory path.
     *
     * @return string Output directory path
     */
    public function getOutputDir(): string
    {
        return $this->outputDir;
    }
}
