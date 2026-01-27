<?php

namespace App\Console\Commands\CLN;

use App\Database\Criteria;
use App\Models\CLN_RNT\LearnGraph;
use App\Models\CLN_RNT\RuntimeGraph;
use App\Services\CLN_RNT\CLNParser;
use App\Services\CLN_RNT\InputParserService;
use App\Services\CLN_RNT\ParserGraphExporter;
use App\Services\CLN_RNT\PatternGraphQuerier;
use App\Services\CLN_RNT\RNTGraphBuilder;
use Illuminate\Console\Command;

class LearningProcess extends Command
{
    protected $signature = 'cln:learning-process
                            {input-file : Path to input text file (one sentence per line)}
                            {--output-dir=storage/graphs : Output directory for DOT files}
                            {--no-render : Skip rendering PNG files with Graphviz}
                            {--language=1 : Language ID for parsing (default: 1)}';

    protected $description = 'Learning process: build pattern graph from training sentences using two-stage learning';

    public string $outputDir;
    public string $noRender;
    public string $sentence;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {

        $inputFile = $this->argument('input-file');
        $this->outputDir = $this->option('output-dir');
        $this->noRender = $this->option('no-render');
        $languageId = (int)$this->option('language');
        $this->sentence = '';

        // Validate input file
        if (!file_exists($inputFile)) {
            $this->error("Input file not found: {$inputFile}");

            return Command::FAILURE;
        }

        if (!is_readable($inputFile)) {
            $this->error("Input file is not readable: {$inputFile}");

            return Command::FAILURE;
        }

        // Create output directory
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
            $this->info("Created output directory: {$this->outputDir}");
        }

        $this->info('CLN Learning Process - Two Stage Learning');
        $this->info("Input file: {$inputFile}");
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
        ];

        $querier = new PatternGraphQuerier;
        $inputParser = new InputParserService;

        $parser = new CLNParser('learn', $querier, $inputParser, $config);

        // Read and parse sentences
        $sentences = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (empty($sentences)) {
            $this->error('No sentences found in input file');

            return Command::FAILURE;
        }

        $this->info('Found ' . count($sentences) . ' sentences to process');
        $this->newLine();

        $useSentence = true;

        // Parse all sentences
        $this->info('Parsing all sentences...');
        $parsedSentences = [];
        foreach ($sentences as $lineNum => $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) {
                continue;
            }

            if ($sentence == '/*') {
                $useSentence = false;
                continue;
            }

            if ($sentence == '*/') {
                $useSentence = true;
                continue;
            }
            if (!$useSentence) {
                continue;
            }

            if ($sentence[0] == '#') {
                continue;
            }

            $wordData = $parser->learn($sentence);
            if (!empty($wordData)) {
                $parsedSentences[] = [
                    'sentence' => $sentence,
                    'word_data' => $wordData,
                ];
            }
        }

        $this->line('  ✓ Parsed ' . count($parsedSentences) . ' sentences');
        $this->newLine();
        $this->generateGraph('stage_1', $parser->getRuntimeGraph());

        $this->updatePatternGraph($parser->getRuntimeGraph());

        return Command::SUCCESS;
    }

    private function updatePatternGraph(LearnGraph $graph)
    {
        $graphBuilder = new RNTGraphBuilder();
        $rightEdges = [];
        $idPatterns = [];
        // get the phrasal constructions
        $phrasal = Criteria::table("parser_construction_v4")
            ->where("constructionType", "phrasal")
            ->all();
        foreach ($phrasal as $phrase) {
            // sequencer for this construction
            $sequencer = Criteria::table("parser_pattern_node")
                ->where("construction_name", $phrase->name)
                ->where("type", "OR")
                ->first();
            $column = $graph->getColumn('L2', $sequencer->id);
            if (!is_null($column)) {
                $patternId = $phrase->idConstruction + 1000;
                $constructionName = 'phrase_' . $phrase->name;
                $idPatterns[$constructionName] = $patternId;
                $sequencerSpec = [
                    'type' => 'SEQUENCER',
                    'construction_name' => $constructionName,
                    'layer' => 'L5',
                ];
                $sequencerId = $graphBuilder->insertNode($sequencerSpec, $patternId, 'L5_S');
                $edges = $graph->getEdges("L2_L5_{$column->name}_{$phrase->name}");
                foreach($edges as $i => $edge) {
                    $target = $graph->getNode($edge->target);
                    print_r("====\n");
                    print_r($target->id . "\n");
                    print_r($target->type . "\n");
                    print_r($target->metadata);
                    $position = ($target->metadata['name'] == 'l' ? 'left' : 'right');
                    if ($position != 'left') {
                        $rightEdges[] = $edge;
                        continue;
                    }
                    $orSpec = [
                        'type' => 'OR',
                        'construction_name' => $constructionName,
                        'layer' => 'L23',
                        'position' => $position
                    ];
                    $orId = $graphBuilder->insertNode($orSpec, $patternId, "L23_{$position}_{$i}");

                    $graphBuilder->insertEdge($patternId, $sequencer->id, $orId, [
                        'sequence' => 1,
                        'label' => $position,
                        'position' => $position,
                    ]);

                    $graphBuilder->insertEdge($patternId, $orId, $sequencerId, [
                        'sequence' => 1,
                        'label' => $position,
                        'position' => $position,
                    ]);

                }
            }
        }
        foreach ($phrasal as $phrase) {
            // sequencer for this construction
            $sequencer = Criteria::table("parser_pattern_node")
                ->where("construction_name", $phrase->name)
                ->where("type", "OR")
                ->first();
            $column = $graph->getColumn('L2', $sequencer->id);
            if (!is_null($column)) {
                $patternId = $phrase->idConstruction + 1000;
                $constructionName = 'phrase_' . $phrase->name;
                $idSource = "L2_L5_{$column->name}_{$phrase->name}";
                foreach($rightEdges as $i => $edge) {
                    if ($edge->source == $idSource) {
                        $position = 'right';



                        $orSpec = [
                            'type' => 'OR',
                            'construction_name' => $constructionName,
                            'layer' => 'L23',
                            'position' => $position
                        ];
                        $orId = $graphBuilder->insertNode($orSpec, $patternId, "L23_{$position}_{$i}");

//                        $graphBuilder->insertEdge($patternId, $sequencer->id, $orId, [
//                            'sequence' => 1,
//                            'label' => $position,
//                            'position' => $position,
//                        ]);

                        $graphBuilder->insertEdge($patternId, $orId, $sequencer->id, [
                            'sequence' => 1,
                            'label' => $position,
                            'position' => $position,
                        ]);
                    }
                }

            }
        }
    }

    /**
     * Check if Graphviz is available
     */
    private function isGraphvizAvailable(): bool
    {
        exec('which dot', $output, $returnCode);

        return $returnCode === 0;
    }

    public function generateGraph(string $stage, RuntimeGraph|LearnGraph $graph): int
    {
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

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        if ($exporter->saveDotToFile($dot, $dotPath)) {
            $this->info("✓ DOT file saved: {$dotPath}");
        } else {
            $this->error('✗ Failed to save DOT file');

            return Command::FAILURE;
        }

        // Render to PNG unless skipped
        if (!$this->noRender) {
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
     * Render DOT file to PNG using Graphviz
     */
    private function renderDotToPng(string $dotFile, string $pngFile): void
    {
        $command = sprintf(
            'dot -Tpng %s -o %s 2>&1',
            escapeshellarg($dotFile),
            escapeshellarg($pngFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('  ✗ Failed to render: ' . implode("\n", $output));
        }
    }
}
