<?php

namespace App\Console\Commands\SeqGraph;

use App\Services\SeqGraph\PatternGraphLoader;
use App\Services\SeqGraph\SequenceGraphBuilder;
use App\Services\SeqGraph\SequenceGraphRenderer;
use Illuminate\Console\Command;

/**
 * Command to build and render sequence graphs from database patterns.
 *
 * Loads pattern graphs from the database, builds sequence graphs,
 * and renders them to DOT and PNG files for visualization.
 */
class RenderGraphsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seqgraph:render
                            {--patterns=* : Pattern names to render (default: all patterns)}
                            {--output-dir= : Output directory for rendered files}
                            {--no-image : Skip PNG image generation (only generate DOT files)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build and render sequence graphs from database patterns to DOT/PNG files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sequence Graph Renderer');
        $this->info('=======================');
        $this->newLine();

        // Load patterns from database
        $loader = new PatternGraphLoader;
        $patterns = $this->loadPatterns($loader);

        if (empty($patterns)) {
            $this->error('No patterns found in database!');

            return Command::FAILURE;
        }

        $this->info('Found '.count($patterns).' patterns to render.');
        $this->newLine();

        // Create renderer with custom output directory if specified
        $outputDir = $this->option('output-dir');
        $renderer = $outputDir ? new SequenceGraphRenderer($outputDir) : new SequenceGraphRenderer;

        $this->info('Output directory: '.$renderer->getOutputDir());
        $this->newLine();

        // Build and render graphs
        $builder = new SequenceGraphBuilder;
        $renderImage = ! $this->option('no-image');

        $this->info('Rendering graphs'.($renderImage ? ' (with PNG images)' : ' (DOT only)').':');

        $results = [];
        foreach ($patterns as $patternName => $patternGraph) {
            $graph = $builder->build($patternName, $patternGraph);
            $result = $renderer->render($graph, $renderImage);
            $results[$patternName] = $result;

            $status = $renderImage && $result['imagePath'] ? 'DOT + PNG' : 'DOT only';
            $this->line("  - {$patternName}: {$status}");
        }

        $this->newLine();
        $this->info('Rendering complete!');
        $this->newLine();

        // Summary
        $dotCount = count(array_filter($results, fn ($r) => file_exists($r['dotPath'])));
        $pngCount = count(array_filter($results, fn ($r) => $r['imagePath'] && file_exists($r['imagePath'])));

        $this->table(
            ['Metric', 'Count'],
            [
                ['Patterns processed', count($patterns)],
                ['DOT files generated', $dotCount],
                ['PNG images generated', $pngCount],
            ]
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
