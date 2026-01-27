<?php

namespace App\Console\Commands\SeqGraph;

use App\Services\SeqGraph\PatternGraphLoader;
use App\Services\SeqGraph\SequenceGraphRenderer;
use App\Services\SeqGraph\UnifiedSequenceGraphBuilder;
use Illuminate\Console\Command;

/**
 * Command to build and render a unified sequence graph from database patterns.
 *
 * Creates a single graph combining all patterns with:
 * - One global START node
 * - PATTERN nodes representing pattern completion
 * - Cross-pattern edges from PATTERN to CONSTRUCTION_REF listeners
 */
class RenderUnifiedGraphCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seqgraph:render-unified
                            {--patterns=* : Pattern names to include (default: all patterns)}
                            {--output-dir= : Output directory for rendered files}
                            {--no-image : Skip PNG image generation (only generate DOT files)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build and render a unified sequence graph combining all patterns';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Unified Sequence Graph Renderer');
        $this->info('===============================');
        $this->newLine();

        // Load patterns from database
        $loader = new PatternGraphLoader;
        $patterns = $this->loadPatterns($loader);

        if (empty($patterns)) {
            $this->error('No patterns found in database!');

            return Command::FAILURE;
        }

        $this->info('Found '.count($patterns).' patterns to unify.');
        $this->newLine();

        // Show pattern names
        $this->info('Patterns:');
        foreach (array_keys($patterns) as $patternName) {
            $this->line("  - {$patternName}");
        }
        $this->newLine();

        // Create renderer with custom output directory if specified
        $outputDir = $this->option('output-dir');
        $renderer = $outputDir ? new SequenceGraphRenderer($outputDir) : new SequenceGraphRenderer;

        $this->info('Output directory: '.$renderer->getOutputDir());
        $this->newLine();

        // Build unified graph
        $this->info('Building unified graph...');
        $builder = new UnifiedSequenceGraphBuilder;
        $graph = $builder->build($patterns);

        $this->info('  - Nodes: '.count($graph->nodes));
        $this->info('  - Edges: '.count($graph->edges));
        $this->info('  - Pattern nodes: '.count($graph->patternNodeIds));
        $this->newLine();

        // Render graph
        $renderImage = ! $this->option('no-image');
        $this->info('Rendering unified graph'.($renderImage ? ' (with PNG image)' : ' (DOT only)').'...');

        $result = $renderer->renderUnified($graph, $renderImage);

        $this->newLine();
        $this->info('Rendering complete!');
        $this->newLine();

        // Summary
        $this->table(
            ['Output', 'Path'],
            [
                ['DOT file', $result['dotPath']],
                ['PNG image', $result['imagePath'] ?? 'Not generated'],
            ]
        );

        $this->newLine();

        // Graph structure summary
        $this->info('Graph Structure:');
        $this->table(
            ['Pattern', 'Entry Nodes', 'Pattern Node ID'],
            collect($graph->patternNodeIds)->map(function ($patternNodeId, $patternName) use ($graph) {
                $entryNodes = $graph->patternEntryNodes[$patternName] ?? [];

                return [
                    $patternName,
                    implode(', ', $entryNodes),
                    $patternNodeId,
                ];
            })->values()->all()
        );

        return Command::SUCCESS;
    }

    /**
     * Load patterns from database based on options.
     *
     * @param  PatternGraphLoader  $loader  Pattern graph loader
     * @return array<string, array<string, mixed>> Pattern graphs
     */
    private function loadPatterns(PatternGraphLoader $loader): array
    {
        $patternNames = $this->option('patterns');

        if (! empty($patternNames)) {
            return $loader->loadByNames($patternNames);
        }

        return $loader->loadAll();
    }
}
