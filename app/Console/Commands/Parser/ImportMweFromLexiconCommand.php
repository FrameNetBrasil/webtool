<?php

namespace App\Console\Commands\Parser;

use App\Database\Criteria;
use App\Repositories\Parser\MWE;
use Illuminate\Console\Command;

class ImportMweFromLexiconCommand extends Command
{
    protected $signature = 'parser:import-mwe-from-lexicon
                            {--dry-run : Preview without applying changes}
                            {--limit= : Limit number of MWEs to process}';

    protected $description = 'Import multi-word expressions from lexicon to parser grammar';

    private array $stats = [
        'total_lemmas' => 0,
        'created' => 0,
        'skipped_duplicates' => 0,
        'no_pos_tag' => 0,
        'unmapped_pos' => 0,
        'errors' => 0,
    ];

    private const GRAMMAR_ID = 1; // Portuguese Basic Grammar

    private const LANGUAGE_ID = 1; // Portuguese

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        $this->displayConfiguration($isDryRun, $limit);

        // Query multi-word lemmas
        $lemmas = $this->queryMultiWordLemmas($limit);

        if (empty($lemmas)) {
            $this->warn('No multi-word lemmas found.');

            return Command::SUCCESS;
        }

        $this->stats['total_lemmas'] = count($lemmas);
        $this->info("Found {$this->stats['total_lemmas']} multi-word lemmas to process.");
        $this->newLine();

        // Process lemmas
        $progressBar = $this->output->createProgressBar(count($lemmas));
        $progressBar->start();

        foreach ($lemmas as $lemma) {
            $this->processLemma($lemma, $isDryRun);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($isDryRun);

        return Command::SUCCESS;
    }

    private function displayConfiguration(bool $isDryRun, ?string $limit): void
    {
        $this->info('Importing MWEs from Lexicon to Parser Grammar');
        $this->line(str_repeat('-', 60));
        $this->line('Configuration:');
        $this->line('  - Grammar ID: '.self::GRAMMAR_ID.' (Portuguese Basic)');
        $this->line('  - Language ID: '.self::LANGUAGE_ID.' (Portuguese)');
        $this->line('  - Limit: '.($limit ?: 'No limit'));

        if ($isDryRun) {
            $this->warn('  - DRY RUN MODE - No changes will be applied');
        }

        $this->newLine();
    }

    private function queryMultiWordLemmas(?string $limit): array
    {
        $query = Criteria::table('view_lemma')
            ->where('idLanguage', self::LANGUAGE_ID)
            ->whereRaw("name LIKE '% %'")
            ->orderBy('name');

        if ($limit) {
            $query->limit((int) $limit);
        }

        return $query->all();
    }

    private function processLemma(object $lemma, bool $isDryRun): void
    {
        try {
            $phrase = $lemma->name;
            $components = $this->extractComponents($phrase);

            // Validate components
            if (count($components) < 2) {
                logger()->warning("Lemma '{$phrase}' has less than 2 components after split");
                $this->stats['errors']++;

                return;
            }

            // Check for existing MWE
            if ($this->mweExists($phrase)) {
                $this->stats['skipped_duplicates']++;

                return;
            }

            // Get POS tag and map to semantic type
            $semanticType = $this->getSemanticType($lemma->idLemma);

            // Create MWE (if not dry-run)
            if (! $isDryRun) {
                MWE::create([
                    'idGrammarGraph' => self::GRAMMAR_ID,
                    'phrase' => $phrase,
                    'components' => $components,
                    'semanticType' => $semanticType,
                ]);
            }

            $this->stats['created']++;
        } catch (\Exception $e) {
            logger()->error("Failed to process lemma '{$lemma->name}': ".$e->getMessage());
            $this->stats['errors']++;
        }
    }

    private function extractComponents(string $phrase): array
    {
        return array_filter(explode(' ', $phrase), fn ($c) => ! empty(trim($c)));
    }

    private function mweExists(string $phrase): bool
    {
        $existing = Criteria::table('parser_mwe')
            ->where('idGrammarGraph', self::GRAMMAR_ID)
            ->where('phrase', $phrase)
            ->first();

        return $existing !== null;
    }

    private function getSemanticType(int $idLemma): string
    {
        // Get POS tag from view_lemma_pos
        $lemmaPos = Criteria::table('view_lemma_pos')
            ->where('idLemma', $idLemma)
            ->orderBy('idUDPOS')
            ->first();

        if (! $lemmaPos) {
            logger()->warning("No POS tag found for lemma ID {$idLemma}, defaulting to 'E'");
            $this->stats['no_pos_tag']++;

            return 'E';
        }

        // Map POS to semantic type
        return $this->mapPOSToSemanticType($lemmaPos->POS);
    }

    private function mapPOSToSemanticType(string $pos): string
    {
        $mappings = config('parser.wordTypeMappings');

        foreach ($mappings as $semanticType => $posTags) {
            if (in_array($pos, $posTags)) {
                return $semanticType;
            }
        }

        // Default fallback
        logger()->warning("Unmapped POS tag: {$pos}, defaulting to 'E'");
        $this->stats['unmapped_pos']++;

        return 'E';
    }

    private function displaySummary(bool $isDryRun): void
    {
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Lemmas Processed', $this->stats['total_lemmas']],
                ['MWEs Created', $this->stats['created']],
                ['Skipped (Duplicates)', $this->stats['skipped_duplicates']],
                ['No POS Tag (defaulted)', $this->stats['no_pos_tag']],
                ['Unmapped POS (defaulted)', $this->stats['unmapped_pos']],
                ['Errors', $this->stats['errors']],
            ]
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn('⚠ DRY RUN - No changes were applied');
            $this->info('Run without --dry-run to apply these changes');
        }
    }
}
