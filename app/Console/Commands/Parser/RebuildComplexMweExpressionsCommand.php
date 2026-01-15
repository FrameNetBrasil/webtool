<?php

namespace App\Console\Commands\Parser;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildComplexMweExpressionsCommand extends Command
{
    protected $signature = 'parser:rebuild-complex-mwes {--dry-run : Preview changes without applying them}';

    protected $description = 'Aggressively rebuild complex MWEs by deleting ALL expressions and recreating from scratch based on MWE phrase';

    private array $stats = [
        'total_mwes_checked' => 0,
        'mwes_rebuilt' => 0,
        'mwes_skipped' => 0,
        'expressions_deleted' => 0,
        'expressions_created' => 0,
        'lexicons_created' => 0,
        'errors' => 0,
    ];

    private array $rebuildLog = [];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->warn('⚠ AGGRESSIVE REBUILD MODE ⚠');
        $this->warn('This will DELETE ALL existing expressions for problematic MWEs and rebuild from scratch!');
        $this->newLine();

        if (! $isDryRun) {
            if (! $this->confirm('Are you sure you want to proceed? This action will delete data.', false)) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
            $this->newLine();
        }

        $this->info('Rebuilding complex MWE lexicon expressions for Portuguese (idLanguage = 1)...');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be applied');
        }
        $this->newLine();

        // Get all remaining problematic MWEs (those with incorrect expression counts)
        $problematicMwes = $this->getProblematicMwes();

        $this->stats['total_mwes_checked'] = count($problematicMwes);

        if (empty($problematicMwes)) {
            $this->info('✓ No problematic MWEs found!');

            return Command::SUCCESS;
        }

        $this->info("Found {$this->stats['total_mwes_checked']} MWEs to rebuild");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($problematicMwes));
        $progressBar->start();

        foreach ($problematicMwes as $mwe) {
            try {
                $this->rebuildMweExpressions($mwe, $isDryRun);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                logger()->error("Failed to rebuild MWE {$mwe->idLemma} ({$mwe->mwe_phrase}): ".$e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($isDryRun);

        // Save rebuild log if not dry run
        if (! $isDryRun && ! empty($this->rebuildLog)) {
            $this->saveRebuildLog();
        }

        return Command::SUCCESS;
    }

    private function getProblematicMwes(): array
    {
        $query = "
            SELECT
                lm.idLemma,
                lx.form as mwe_phrase,
                lx.idLexicon,
                COUNT(le.idLexiconExpression) as expression_count,
                LENGTH(lx.form) - LENGTH(REPLACE(lx.form, ' ', '')) + 1 as expected_word_count
            FROM lemma lm
            JOIN lexicon lx ON lm.idLexicon = lx.idLexicon
            LEFT JOIN lexicon_expression le ON lm.idLemma = le.idLemma
            WHERE lm.idLanguage = 1 AND lx.form LIKE '% %'
            GROUP BY lm.idLemma, lx.form, lx.idLexicon
            HAVING expression_count != expected_word_count
            ORDER BY expression_count DESC, lx.form
        ";

        return DB::select($query);
    }

    private function rebuildMweExpressions(object $mwe, bool $isDryRun): void
    {
        // Get all existing expressions (for logging before deletion)
        $existingExpressions = Criteria::table('lexicon_expression')
            ->where('idLemma', $mwe->idLemma)
            ->get();

        $this->stats['expressions_deleted'] += $existingExpressions->count();

        // Log the rebuild
        $this->logRebuild($mwe, $existingExpressions);

        // Delete ALL existing expressions for this lemma
        if (! $isDryRun) {
            DB::table('lexicon_expression')
                ->where('idLemma', $mwe->idLemma)
                ->delete();
        }

        // Parse the MWE phrase to get individual words
        $mwePhrase = $mwe->mwe_phrase;

        // Clean up special characters and brackets from the phrase
        // Remove content in brackets like [alguém], [entidade], etc.
        $mwePhrase = preg_replace('/\[.*?\]/', '', $mwePhrase);
        // Remove extra special characters but keep spaces and hyphens
        $mwePhrase = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $mwePhrase);
        // Normalize whitespace
        $mwePhrase = preg_replace('/\s+/', ' ', trim($mwePhrase));

        // Skip if phrase becomes empty after cleaning
        if (empty($mwePhrase)) {
            $this->stats['mwes_skipped']++;
            logger()->warning("Skipped MWE {$mwe->idLemma} - phrase became empty after cleaning: '{$mwe->mwe_phrase}'");

            return;
        }

        // Split by spaces
        $words = explode(' ', $mwePhrase);
        $expectedCount = count($words);

        // Create new expressions for each word at proper positions
        foreach ($words as $position => $word) {
            $word = trim($word);
            if (empty($word)) {
                continue;
            }

            $this->createLexiconExpression(
                $mwe->idLemma,
                $word,
                $position + 1, // Position is 1-indexed
                $isDryRun
            );
        }

        $this->stats['mwes_rebuilt']++;
    }

    private function createLexiconExpression(int $idLemma, string $wordForm, int $position, bool $isDryRun): void
    {
        $wordForm = trim($wordForm);

        // Look up or create lexicon entry for this word
        $lexicon = Criteria::table('lexicon')
            ->where('form', $wordForm)
            ->where('idLexiconGroup', 1) // Word forms group
            ->first();

        if (! $lexicon) {
            // Need to create lexicon entry
            if (! $isDryRun) {
                $idLexicon = Criteria::create('lexicon', [
                    'form' => $wordForm,
                    'idLexiconGroup' => 1, // Word forms
                    'idEntity' => null,
                ]);
                $this->stats['lexicons_created']++;
            } else {
                $idLexicon = 0; // Placeholder for dry run
                $this->stats['lexicons_created']++;
            }
        } else {
            $idLexicon = $lexicon->idLexicon;
        }

        // Create lexicon_expression record
        if (! $isDryRun) {
            Criteria::create('lexicon_expression', [
                'idLemma' => $idLemma,
                'idExpression' => $idLexicon,
                'position' => $position,
                'head' => $position == 1 ? 1 : 0, // First word is head
                'breakBefore' => 0,
            ]);
        }

        $this->stats['expressions_created']++;
    }

    private function logRebuild(object $mwe, $existingExpressions): void
    {
        $existingData = [];
        foreach ($existingExpressions as $expr) {
            $existingData[] = [
                'idLexiconExpression' => $expr->idLexiconExpression,
                'idExpression' => $expr->idExpression,
                'position' => $expr->position,
                'head' => $expr->head,
            ];
        }

        $this->rebuildLog[] = [
            'idLemma' => $mwe->idLemma,
            'mwe_phrase' => $mwe->mwe_phrase,
            'expressions_deleted_count' => count($existingExpressions),
            'existing_expressions' => $existingData,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    private function saveRebuildLog(): void
    {
        $logFile = storage_path('logs/mwe_rebuild_'.now()->format('Y-m-d_His').'.json');
        file_put_contents($logFile, json_encode($this->rebuildLog, JSON_PRETTY_PRINT));
        $this->newLine();
        $this->info("Rebuild log saved to: {$logFile}");
    }

    private function displaySummary(bool $isDryRun): void
    {
        $this->info('=== MWE Rebuild Summary ===');

        $this->table(
            ['Metric', 'Count'],
            [
                ['MWEs checked', $this->stats['total_mwes_checked']],
                ['MWEs successfully rebuilt', $this->stats['mwes_rebuilt']],
                ['MWEs skipped (empty after cleaning)', $this->stats['mwes_skipped']],
                ['OLD expressions deleted', $this->stats['expressions_deleted']],
                ['NEW expressions created', $this->stats['expressions_created']],
                ['NEW lexicon entries created', $this->stats['lexicons_created']],
                ['Errors encountered', $this->stats['errors']],
            ]
        );

        $this->newLine();

        if ($isDryRun) {
            $this->warn('⚠ DRY RUN - No changes were applied');
            $this->info('Run without --dry-run to apply these changes');
        } else {
            $this->info('✓ Complex MWEs rebuilt from scratch');
            if ($this->stats['expressions_deleted'] > 0) {
                $this->comment('A rebuild log has been saved with details of all deleted records');
            }
        }

        $this->newLine();
    }
}
