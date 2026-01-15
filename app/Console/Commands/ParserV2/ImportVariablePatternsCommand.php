<?php

namespace App\Console\Commands\ParserV2;

use App\Repositories\Parser\MWE;
use App\Rules\ValidMWEComponents;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Import variable component patterns from a JSON file into the parser_mwe table.
 *
 * JSON file format:
 * {
 *   "patterns": [
 *     {
 *       "phrase": "[NOUN] de [NOUN]",
 *       "components": [
 *         {"type": "P", "value": "NOUN"},
 *         {"type": "W", "value": "de"},
 *         {"type": "P", "value": "NOUN"}
 *       ],
 *       "semanticType": "E"
 *     }
 *   ]
 * }
 */
class ImportVariablePatternsCommand extends Command
{
    protected $signature = 'parser:import-variable-patterns
                            {file : Path to JSON file containing patterns}
                            {--grammar= : Grammar graph ID (required)}
                            {--dry-run : Show what would be imported without making changes}
                            {--update : Update existing patterns instead of skipping}';

    protected $description = 'Import variable component patterns (MWEs with POS, CE, or lemma components) from JSON file';

    private array $stats = [
        'total' => 0,
        'imported' => 0,
        'skipped_duplicate' => 0,
        'skipped_invalid' => 0,
        'updated' => 0,
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $idGrammarGraph = $this->option('grammar');
        $dryRun = $this->option('dry-run');
        $update = $this->option('update');

        // Validate grammar graph ID
        if (! $idGrammarGraph) {
            $this->error('Grammar graph ID is required. Use --grammar=ID');

            return Command::FAILURE;
        }

        // Validate file exists
        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        // Read and parse JSON
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: '.json_last_error_msg());

            return Command::FAILURE;
        }

        if (! isset($data['patterns']) || ! is_array($data['patterns'])) {
            $this->error("JSON must contain a 'patterns' array");

            return Command::FAILURE;
        }

        $this->displayConfiguration($filePath, $idGrammarGraph, $dryRun, $update, count($data['patterns']));

        // Process patterns
        $this->stats['total'] = count($data['patterns']);
        $progressBar = $this->output->createProgressBar($this->stats['total']);
        $progressBar->start();

        foreach ($data['patterns'] as $index => $pattern) {
            $this->processPattern($pattern, $idGrammarGraph, $index, $dryRun, $update);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->displayStatistics();

        return Command::SUCCESS;
    }

    private function displayConfiguration(string $filePath, int $idGrammarGraph, bool $dryRun, bool $update, int $patternCount): void
    {
        $this->info('Import Variable Patterns Command');
        $this->line(str_repeat('-', 60));
        $this->line('Configuration:');
        $this->line("  File: {$filePath}");
        $this->line("  Grammar Graph: {$idGrammarGraph}");
        $this->line("  Patterns to import: {$patternCount}");
        $this->line('  Dry run: '.($dryRun ? 'Yes' : 'No'));
        $this->line('  Update existing: '.($update ? 'Yes' : 'No'));
        $this->newLine();
    }

    private function processPattern(array $pattern, int $idGrammarGraph, int $index, bool $dryRun, bool $update): void
    {
        // Validate required fields
        if (! isset($pattern['phrase']) || ! isset($pattern['components']) || ! isset($pattern['semanticType'])) {
            if ($this->output->isVerbose()) {
                $this->warn("Pattern {$index}: Missing required fields (phrase, components, semanticType)");
            }
            $this->stats['skipped_invalid']++;

            return;
        }

        // Validate components using our rule
        $componentsRule = new ValidMWEComponents;
        $validator = Validator::make(
            ['components' => $pattern['components']],
            ['components' => ['required', 'array', $componentsRule]]
        );

        if ($validator->fails()) {
            if ($this->output->isVerbose()) {
                $errors = implode(', ', $validator->errors()->all());
                $this->warn("Pattern {$index} '{$pattern['phrase']}': {$errors}");
            }
            $this->stats['skipped_invalid']++;

            return;
        }

        // Detect format and calculate anchor
        $format = MWE::detectComponentFormat($pattern['components']);
        $anchor = MWE::calculateAnchor($pattern['components']);

        // Check for existing pattern
        $existing = MWE::getByPhrase($idGrammarGraph, $pattern['phrase']);

        if ($existing) {
            if (! $update) {
                if ($this->output->isVerbose()) {
                    $this->line("  Skipping duplicate: {$pattern['phrase']}");
                }
                $this->stats['skipped_duplicate']++;

                return;
            }

            // Update existing
            if (! $dryRun) {
                MWE::update($existing->idMWE, [
                    'components' => $pattern['components'],
                    'semanticType' => $pattern['semanticType'],
                    'componentFormat' => $format,
                    'anchorPosition' => $anchor['position'],
                    'anchorWord' => $anchor['word'],
                ]);
            }

            if ($this->output->isVerbose()) {
                $this->line("  Updated: {$pattern['phrase']} (format: {$format}, anchor: {$anchor['word']} at {$anchor['position']})");
            }
            $this->stats['updated']++;

            return;
        }

        // Create new pattern
        if (! $dryRun) {
            MWE::createExtended([
                'idGrammarGraph' => $idGrammarGraph,
                'phrase' => $pattern['phrase'],
                'components' => $pattern['components'],
                'semanticType' => $pattern['semanticType'],
                'componentFormat' => $format,
            ]);
        }

        if ($this->output->isVerbose()) {
            $anchorInfo = $anchor['word'] ? "anchor: {$anchor['word']} at {$anchor['position']}" : 'fully variable';
            $this->line("  Imported: {$pattern['phrase']} (format: {$format}, {$anchorInfo})");
        }
        $this->stats['imported']++;
    }

    private function displayStatistics(): void
    {
        $this->info('Statistics');
        $this->line(str_repeat('-', 60));

        $stats = [
            ['Total patterns', $this->stats['total']],
            ['Imported', $this->stats['imported']],
            ['Updated', $this->stats['updated']],
            ['Skipped (duplicate)', $this->stats['skipped_duplicate']],
            ['Skipped (invalid)', $this->stats['skipped_invalid']],
        ];

        $this->table(['Metric', 'Value'], $stats);
    }
}
