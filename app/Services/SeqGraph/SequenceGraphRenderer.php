<?php

namespace App\Services\SeqGraph;

use App\Models\SeqGraph\SeqNode;
use App\Models\SeqGraph\SequenceGraph;
use App\Models\SeqGraph\UnifiedSequenceGraph;

/**
 * Renders sequence graphs to DOT format and image files.
 *
 * Generates GraphViz DOT representations of sequence graphs for
 * visualization and debugging purposes.
 */
class SequenceGraphRenderer
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
     * Render a sequence graph to DOT format and optionally to an image.
     *
     * @param  SequenceGraph  $graph  The graph to render
     * @param  bool  $renderImage  Whether to render to PNG image
     * @return array{dot: string, dotPath: string, imagePath: string|null} Paths to generated files
     */
    public function render(SequenceGraph $graph, bool $renderImage = true): array
    {
        $this->ensureOutputDir();

        $dot = $this->generateDot($graph);
        $dotPath = $this->writeDotFile($graph->patternName, $dot);

        $imagePath = null;
        if ($renderImage) {
            $imagePath = $this->renderToImage($dotPath, $graph->patternName);
        }

        return [
            'dot' => $dot,
            'dotPath' => $dotPath,
            'imagePath' => $imagePath,
        ];
    }

    /**
     * Render multiple sequence graphs.
     *
     * @param  array<string, SequenceGraph>  $graphs  Graphs indexed by pattern name
     * @param  bool  $renderImage  Whether to render to PNG images
     * @return array<string, array{dot: string, dotPath: string, imagePath: string|null}> Results indexed by pattern name
     */
    public function renderAll(array $graphs, bool $renderImage = true): array
    {
        $results = [];

        foreach ($graphs as $patternName => $graph) {
            $results[$patternName] = $this->render($graph, $renderImage);
        }

        return $results;
    }

    /**
     * Render a unified sequence graph to DOT format and optionally to an image.
     *
     * @param  UnifiedSequenceGraph  $graph  The unified graph to render
     * @param  bool  $renderImage  Whether to render to PNG image
     * @return array{dot: string, dotPath: string, imagePath: string|null} Paths to generated files
     */
    public function renderUnified(UnifiedSequenceGraph $graph, bool $renderImage = true): array
    {
        $this->ensureOutputDir();

        $dot = $this->generateUnifiedDot($graph);
        $dotPath = $this->writeDotFile('unified', $dot);

        $imagePath = null;
        if ($renderImage) {
            $imagePath = $this->renderToImage($dotPath, 'unified');
        }

        return [
            'dot' => $dot,
            'dotPath' => $dotPath,
            'imagePath' => $imagePath,
        ];
    }

    /**
     * Generate DOT format string for a unified sequence graph.
     *
     * Uses subgraph clusters to group nodes by pattern, with distinct
     * styling for PATTERN nodes and cross-pattern edges.
     *
     * @param  UnifiedSequenceGraph  $graph  The unified graph
     * @return string DOT format representation
     */
    public function generateUnifiedDot(UnifiedSequenceGraph $graph): string
    {
        $lines = [];
        $lines[] = 'digraph "unified" {';
        $lines[] = '    rankdir=LR;';
        $lines[] = '    compound=true;';
        $lines[] = '    node [fontname="Helvetica", fontsize=10];';
        $lines[] = '    edge [fontname="Helvetica", fontsize=9];';
        $lines[] = '';

        // Global START node (outside clusters)
        $globalStart = $graph->getNode($graph->globalStartId);
        if ($globalStart !== null) {
            $attrs = $this->getNodeAttributes($globalStart);
            $attrs['label'] = 'GLOBAL\\nSTART';
            $attrStr = $this->formatAttributes($attrs);
            $lines[] = "    \"{$graph->globalStartId}\" [{$attrStr}];";
            $lines[] = '';
        }

        // Create subgraph clusters for each pattern
        $patternNames = $graph->getPatternNames();
        $clusterIndex = 0;
        foreach ($patternNames as $patternName) {
            $lines[] = "    subgraph cluster_{$clusterIndex} {";
            $lines[] = "        label=\"{$patternName}\";";
            $lines[] = '        style=filled;';
            $lines[] = '        fillcolor=white;';
            $lines[] = '        color=gray;';
            $lines[] = '';

            // Add nodes belonging to this pattern
            $patternNodes = $graph->getNodesByPattern($patternName);
            foreach ($patternNodes as $node) {
                $attrs = $this->getNodeAttributes($node);
                $attrStr = $this->formatAttributes($attrs);
                $lines[] = "        \"{$node->id}\" [{$attrStr}];";
            }

            $lines[] = '    }';
            $lines[] = '';
            $clusterIndex++;
        }

        // Define edges with special styling for cross-pattern edges
        $patternNodeIds = array_flip($graph->patternNodeIds);
        foreach ($graph->edges as $edge) {
            $attrs = [];

            // Check if this is a cross-pattern edge (from PATTERN to CONSTRUCTION_REF)
            $fromNode = $graph->getNode($edge->from);
            $toNode = $graph->getNode($edge->to);
            $isCrossPattern = $fromNode !== null
                && $fromNode->type === SeqNode::TYPE_PATTERN
                && $toNode !== null
                && $toNode->patternName !== $fromNode->patternName;

            if ($isCrossPattern) {
                $attrs['style'] = 'bold';
                $attrs['color'] = 'darkgreen';
                $attrs['penwidth'] = '2.0';
            } elseif ($edge->bypass) {
                $attrs['style'] = 'dashed';
                $attrs['color'] = 'gray';
                $attrs['label'] = 'bypass';
            }

            $attrStr = $this->formatAttributes($attrs);
            $suffix = $attrStr ? " [{$attrStr}]" : '';
            $lines[] = "    \"{$edge->from}\" -> \"{$edge->to}\"{$suffix};";
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * Generate DOT format string for a sequence graph.
     *
     * @param  SequenceGraph  $graph  The graph to convert
     * @return string DOT format representation
     */
    public function generateDot(SequenceGraph $graph): string
    {
        $lines = [];
        $lines[] = "digraph \"{$graph->patternName}\" {";
        $lines[] = '    rankdir=LR;';
        $lines[] = '    node [fontname="Helvetica", fontsize=10];';
        $lines[] = '    edge [fontname="Helvetica", fontsize=9];';
        $lines[] = '';

        // Define nodes with styling based on type
        foreach ($graph->nodes as $nodeId => $node) {
            $attrs = $this->getNodeAttributes($node);
            $attrStr = $this->formatAttributes($attrs);
            $lines[] = "    \"{$nodeId}\" [{$attrStr}];";
        }

        $lines[] = '';

        // Define edges
        foreach ($graph->edges as $edge) {
            $attrs = [];
            if ($edge->bypass) {
                $attrs['style'] = 'dashed';
                $attrs['color'] = 'gray';
                $attrs['label'] = 'bypass';
            }
            $attrStr = $this->formatAttributes($attrs);
            $suffix = $attrStr ? " [{$attrStr}]" : '';
            $lines[] = "    \"{$edge->from}\" -> \"{$edge->to}\"{$suffix};";
        }

        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * Get DOT attributes for a node based on its type.
     *
     * @param  SeqNode  $node  The node
     * @return array<string, string> DOT attributes
     */
    private function getNodeAttributes(SeqNode $node): array
    {
        $label = $node->id;

        if ($node->elementType !== null) {
            $label .= "\\n{$node->elementType}";
            if ($node->elementValue !== null) {
                $label .= ":{$node->elementValue}";
            }
        }

        $attrs = ['label' => $label];

        switch ($node->type) {
            case SeqNode::TYPE_START:
                $attrs['shape'] = 'circle';
                $attrs['style'] = 'filled';
                $attrs['fillcolor'] = 'lightgreen';
                $attrs['label'] = 'START';
                break;

            case SeqNode::TYPE_END:
                $attrs['shape'] = 'doublecircle';
                $attrs['style'] = 'filled';
                $attrs['fillcolor'] = 'lightcoral';
                $attrs['label'] = 'END';
                break;

            case SeqNode::TYPE_PATTERN:
                $attrs['shape'] = 'doubleoctagon';
                $attrs['style'] = 'filled';
                $attrs['fillcolor'] = 'lightsalmon';
                $attrs['label'] = $node->patternName !== null ? "PATTERN\\n{$node->patternName}" : 'PATTERN';
                break;

            case SeqNode::TYPE_INTERMEDIATE:
                $attrs['shape'] = 'diamond';
                $attrs['style'] = 'filled';
                $attrs['fillcolor'] = 'lightyellow';
                break;

            case SeqNode::TYPE_ELEMENT:
                $attrs['shape'] = 'box';
                $attrs['style'] = 'filled';
                $attrs['fillcolor'] = 'lightblue';
                break;

            default:
                $attrs['shape'] = 'ellipse';
                break;
        }

        return $attrs;
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
     * @param  string  $patternName  Pattern name for filename
     * @param  string  $dot  DOT content
     * @return string Path to written file
     */
    private function writeDotFile(string $patternName, string $dot): string
    {
        $filename = $this->sanitizeFilename($patternName).'.dot';
        $path = $this->outputDir.'/'.$filename;

        file_put_contents($path, $dot);

        return $path;
    }

    /**
     * Render DOT file to PNG image using GraphViz.
     *
     * @param  string  $dotPath  Path to DOT file
     * @param  string  $patternName  Pattern name for output filename
     * @return string|null Path to rendered image, or null if rendering failed
     */
    private function renderToImage(string $dotPath, string $patternName): ?string
    {
        $filename = $this->sanitizeFilename($patternName).'.png';
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
     * Sanitize pattern name for use as filename.
     *
     * @param  string  $name  Pattern name
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
