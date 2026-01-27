<?php

namespace App\Console\Commands\SeqGraph;

use App\Services\SeqGraph\PatternGraphLoader;
use App\Services\SeqGraph\SequenceGraphBuilder;
use Illuminate\Console\Command;

/**
 * Test command for loading and building patterns from the database.
 *
 * Demonstrates loading pattern graphs from parser_construction_v4 table
 * and building them into sequence graphs.
 */
class TestDatabasePatternsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seqgraph:test-db {patterns?* : Optional pattern names to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test loading and building patterns from database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sequence Graph Database Pattern Test');
        $this->info('====================================');
        $this->newLine();

        $loader = new PatternGraphLoader;
        $builder = new SequenceGraphBuilder;

        $patternNames = $this->argument('patterns');

        if (empty($patternNames)) {
            // Test with a few basic patterns
            $patternNames = ['NOUN', 'VERB', 'DET', 'REF'];
            $this->info('No patterns specified, testing with: '.implode(', ', $patternNames));
            $this->newLine();
        }

        $sequenceGraphs = $builder->buildByNames($loader, $patternNames);

        if (empty($sequenceGraphs)) {
            $this->error('No patterns found!');

            return Command::FAILURE;
        }

        $this->info('Successfully built '.count($sequenceGraphs).' sequence graphs:');
        $this->newLine();

        foreach ($sequenceGraphs as $patternName => $graph) {
            $this->displayGraphInfo($patternName, $graph);
            $this->newLine();
        }

        $this->info('Test completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Display information about a sequence graph.
     *
     * @param  string  $patternName  Pattern name
     * @param  \App\Data\SeqGraph\SequenceGraph  $graph  Sequence graph
     */
    private function displayGraphInfo(string $patternName, $graph): void
    {
        $this->line("Pattern: {$patternName}");
        $this->line('  Nodes: '.count($graph->nodes));
        $this->line('  Edges: '.count($graph->edges));
        $this->line("  Start: {$graph->startId}");
        $this->line("  End: {$graph->endId}");

        $elementNodes = $graph->getElementNodes();
        if (! empty($elementNodes)) {
            $this->line('  Element nodes:');
            foreach ($elementNodes as $node) {
                $elementInfo = $node->elementType;
                if ($node->elementValue !== null) {
                    $elementInfo .= " = '{$node->elementValue}'";
                }
                $this->line("    - {$node->id}: {$elementInfo}");
            }
        }

        // Show routing nodes count
        $routingCount = 0;
        foreach ($graph->nodes as $node) {
            if ($node->isRouting()) {
                $routingCount++;
            }
        }
        $this->line("  Routing nodes: {$routingCount}");

        // Check for bypass edges
        $bypassCount = 0;
        foreach ($graph->edges as $edge) {
            if ($edge->bypass) {
                $bypassCount++;
            }
        }
        if ($bypassCount > 0) {
            $this->line("  Bypass edges: {$bypassCount}");
        }
    }
}
