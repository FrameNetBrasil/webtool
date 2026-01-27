<?php

namespace App\Console\Commands\CLN;

use App\Models\CLN\RuntimeGraph;
use App\Services\CLN\CLNParser;
use App\Services\CLN\InputParserService;
use App\Services\CLN\ParserGraphExporter;
use Illuminate\Console\Command;

class TestParserGraphCommand extends Command
{
    protected $signature = 'cln:test-parser-graph
                            {sentence? : The sentence to parse}
                            {--output-dir=storage/graphs/parser : Output directory for DOT and PNG files}
                            {--no-render : Skip rendering PNG (only generate DOT)}';

    protected $description = 'Test CLN parser with activation dynamics and generate graph visualization';

    public string $outputDir;
    public string $noRender;
    public string $sentence;

    public function handle(): int
    {
        $this->sentence = $this->argument('sentence') ?? 'the cat sat on the mat';
        $this->outputDir = $this->option('output-dir');
        $this->noRender = $this->option('no-render');

        $this->info('CLN Parser Graph Test');
        $this->info('=====================');
        $this->newLine();
        $this->info("Sentence: {$this->sentence}");
        $this->newLine();

        // Initialize parser
        $this->info('Initializing parser...');
        $config = [
            'rnt_enabled' => false, // Temporarily disabled - deprecated querier file naming issue
            'incremental_enabled' => true,
            'dt' => 0.1,
            'max_timesteps' => 50,
            'min_timesteps' => 10,
            'convergence_check_interval' => 5,
            'pruning_interval' => 10,
            'enable_pruning' => false,
            'output_dir' => $this->outputDir,
        ];

        $inputParser = new InputParserService;

        $parser = new CLNParser($inputParser, $config);

        // Parser stage 1

        // Parse sentence
        $this->info('Parsing sentence (stage 1)...');
        $result = $parser->parse($this->sentence);

        // Display parse results
        $this->newLine();
        $this->info('Parse (Stage 1) Results:');
        $this->info('- L1 nodes: '.count($result['words'] ?? []));
        $this->info('- L2 nodes (constructions): '.count($result['constructions'] ?? []));

        $this->generateGraph('stage_1', $parser->getRuntimeGraph());

        // Parser stage 2
//        $this->info('Parsing sentence (stage 2)...');
        //$result = $parser->parseStage2($result['constructions'] ?? []);

        if (isset($result['activation_stats'])) {
            $stats = $result['activation_stats'];
            $this->info('- Activation iterations: '.($stats['iterations'] ?? 'N/A'));
            $this->info('- OR nodes activated: '.($stats['or_nodes_activated'] ?? 'N/A'));
            $this->info('- AND nodes activated: '.($stats['and_nodes_activated'] ?? 'N/A'));
            $this->info('- SEQUENCER nodes activated: '.($stats['sequencer_nodes_activated'] ?? 'N/A'));
        }

        $this->newLine();
        $this->info('Done!');

        return Command::SUCCESS;
    }

    public function generateGraph(string $stage, RuntimeGraph $graph): int {
        // Get runtime graph from parser
        $this->newLine();
        $this->info('Exporting graph visualization...');

        // Create exporter
        $exporter = new ParserGraphExporter;

        if ($graph === null) {
            $this->error('Failed to capture runtime graph');

            return Command::FAILURE;
        }

        // Generate DOT content
        $stats = $result['activation_stats'] ?? [];
        $dot = $exporter->exportToDot($graph, $this->sentence, $stats);

        // Save DOT file
//        $timestamp = date('Y-m-d_H-i-s');
        $baseName = "parser_graph_{$stage}";
        $dotPath = "{$this->outputDir}/{$baseName}.dot";

        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        if ($exporter->saveDotToFile($dot, $dotPath)) {
            $this->info("✓ DOT file saved: {$dotPath}");
        } else {
            $this->error('✗ Failed to save DOT file');

            return Command::FAILURE;
        }

        // Render to PNG unless skipped
        if (! $this->noRender) {
            $this->info('Rendering PNG...');
            $pngPath = "{$this->outputDir}/{$baseName}.png";

            $renderResult = $exporter->renderToPng($dotPath, $pngPath);

            if ($renderResult['success']) {
                $this->info("✓ {$renderResult['message']}");
            } else {
                $this->warn("✗ {$renderResult['message']}");

                if (isset($renderResult['output'])) {
                    $this->warn("  Output: {$renderResult['output']}");
                }
            }
        }
        return 0;

    }

    /**
     * Parse sentence and capture the runtime graph
     *
     * This is a workaround to access the internal runtime graph.
     * In a production implementation, CLNParser should expose the graph.
     *
     * @param  CLNParser  $parser  Parser instance
     * @param  string  $sentence  Sentence to parse
     * @return \App\Models\CLN_RNT\RuntimeGraph|null Runtime graph or null
     */
    private function parseAndCaptureGraph(CLNParser $parser, string $sentence): ?\App\Models\CLN_RNT\RuntimeGraph
    {
        // For now, we need to use reflection to access the private parseIncremental method
        // and capture the runtime graph
        // This is a temporary solution for testing purposes

        try {
            $reflection = new \ReflectionClass($parser);
            $method = $reflection->getMethod('parseIncremental');
            $method->setAccessible(true);

            // Create a new RuntimeGraph instance
            $graphClass = new \ReflectionClass(\App\Models\CLN_RNT\RuntimeGraph::class);
            $graph = $graphClass->newInstance();

            // Call parseIncremental with the graph
            $method->invoke($parser, $graph, $sentence);

            return $graph;
        } catch (\Exception $e) {
            $this->error("Failed to capture graph: {$e->getMessage()}");

            return null;
        }
    }
}
