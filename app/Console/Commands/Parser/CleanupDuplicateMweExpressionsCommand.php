<?php

namespace App\Console\Commands\Parser;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateMweExpressionsCommand extends Command
{
    protected $signature = 'parser:cleanup-mwe-duplicates {--dry-run : Preview changes without applying them}';

    protected $description = 'Clean up MWEs with too many lexicon_expression records by removing duplicates and keeping valid entries';

    private array $stats = [
        'total_mwes_checked' => 0,
        'mwes_cleaned' => 0,
        'mwes_skipped' => 0,
        'expressions_deleted' => 0,
        'expressions_updated' => 0,
        'errors' => 0,
    ];

    private array $deletionLog = [];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Cleaning up duplicate MWE lexicon expressions for Portuguese (idLanguage = 1)...');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be applied');
        }
        $this->newLine();

        // Get all MWEs with too many expressions
        $problematicMwes = $this->getProblematicMwes();

        $this->stats['total_mwes_checked'] = count($problematicMwes);

        if (empty($problematicMwes)) {
            $this->info('✓ All MWE lemmas have correct expression counts!');

            return Command::SUCCESS;
        }

        $this->info("Found {$this->stats['total_mwes_checked']} MWEs with too many expressions");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($problematicMwes));
        $progressBar->start();

        foreach ($problematicMwes as $mwe) {
            try {
                $this->cleanupMweExpressions($mwe, $isDryRun);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                logger()->error("Failed to cleanup MWE {$mwe->idLemma} ({$mwe->mwe_phrase}): ".$e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($isDryRun);

        // Save deletion log if not dry run
        if (! $isDryRun && ! empty($this->deletionLog)) {
            $this->saveDeletionLog();
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
            HAVING expression_count > expected_word_count
            ORDER BY expression_count DESC, lx.form
        ";

        return DB::select($query);
    }

    private function cleanupMweExpressions(object $mwe, bool $isDryRun): void
    {
        // Get all expressions for this lemma
        $expressions = Criteria::table('lexicon_expression')
            ->where('idLemma', $mwe->idLemma)
            ->orderBy('position')
            ->orderBy('idLexiconExpression') // Secondary sort for consistency
            ->get();

        $expectedCount = (int) $mwe->expected_word_count;
        $currentCount = $expressions->count();

        if ($currentCount <= $expectedCount) {
            // Shouldn't happen, but skip if it does
            return;
        }

        // Strategy: Keep expressions with unique, sequential positions 1..n
        // Delete duplicates and excess records
        $positionsNeeded = range(1, $expectedCount);
        $keptExpressions = [];
        $toDelete = [];

        // Group expressions by position
        $byPosition = [];
        foreach ($expressions as $expr) {
            $pos = (int) $expr->position;
            if (! isset($byPosition[$pos])) {
                $byPosition[$pos] = [];
            }
            $byPosition[$pos][] = $expr;
        }

        // For each needed position, keep the first expression, mark rest for deletion
        foreach ($positionsNeeded as $neededPos) {
            if (isset($byPosition[$neededPos]) && count($byPosition[$neededPos]) > 0) {
                // Keep the first one for this position
                $keptExpressions[$neededPos] = $byPosition[$neededPos][0];

                // Mark duplicates for deletion
                for ($i = 1; $i < count($byPosition[$neededPos]); $i++) {
                    $toDelete[] = $byPosition[$neededPos][$i];
                }
            }
        }

        // Mark any expressions with positions outside the expected range for deletion
        foreach ($byPosition as $pos => $exprs) {
            if ($pos < 1 || $pos > $expectedCount) {
                foreach ($exprs as $expr) {
                    if (! in_array($expr, $toDelete)) {
                        $toDelete[] = $expr;
                    }
                }
            }
        }

        // If we still have too many after removing duplicates and out-of-range,
        // we need to be more aggressive
        $totalAfterCleanup = count($keptExpressions) + (count($expressions) - count($toDelete) - count($keptExpressions));

        if ($totalAfterCleanup > $expectedCount) {
            // Delete all expressions not in keptExpressions
            foreach ($expressions as $expr) {
                $pos = (int) $expr->position;
                if (! isset($keptExpressions[$pos]) || $keptExpressions[$pos]->idLexiconExpression != $expr->idLexiconExpression) {
                    if (! in_array($expr, $toDelete)) {
                        $toDelete[] = $expr;
                    }
                }
            }
        }

        // If we have missing positions, we might need to keep more expressions
        $missingPositions = array_diff($positionsNeeded, array_keys($keptExpressions));
        if (! empty($missingPositions)) {
            // This is complex - skip this MWE and warn
            $this->stats['mwes_skipped']++;
            logger()->warning("MWE {$mwe->idLemma} ({$mwe->mwe_phrase}) has missing positions: ".implode(',', $missingPositions));

            return;
        }

        // Execute deletions
        if (! empty($toDelete)) {
            foreach ($toDelete as $expr) {
                $this->logDeletion($mwe, $expr);

                if (! $isDryRun) {
                    Criteria::deleteById('lexicon_expression', 'idLexiconExpression', $expr->idLexiconExpression);
                }

                $this->stats['expressions_deleted']++;
            }

            $this->stats['mwes_cleaned']++;
        }

        // Ensure head word is set correctly (position 1 should have head=1)
        if (isset($keptExpressions[1])) {
            $firstExpr = $keptExpressions[1];
            if ($firstExpr->head != 1) {
                if (! $isDryRun) {
                    DB::table('lexicon_expression')
                        ->where('idLexiconExpression', $firstExpr->idLexiconExpression)
                        ->update(['head' => 1]);
                }
                $this->stats['expressions_updated']++;
            }
        }
    }

    private function logDeletion(object $mwe, object $expr): void
    {
        $this->deletionLog[] = [
            'idLemma' => $mwe->idLemma,
            'mwe_phrase' => $mwe->mwe_phrase,
            'idLexiconExpression' => $expr->idLexiconExpression,
            'idExpression' => $expr->idExpression,
            'position' => $expr->position,
            'head' => $expr->head,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    private function saveDeletionLog(): void
    {
        $logFile = storage_path('logs/mwe_cleanup_deletions_'.now()->format('Y-m-d_His').'.json');
        file_put_contents($logFile, json_encode($this->deletionLog, JSON_PRETTY_PRINT));
        $this->newLine();
        $this->info("Deletion log saved to: {$logFile}");
    }

    private function displaySummary(bool $isDryRun): void
    {
        $this->info('=== MWE Cleanup Summary ===');

        $this->table(
            ['Metric', 'Count'],
            [
                ['MWEs checked', $this->stats['total_mwes_checked']],
                ['MWEs cleaned', $this->stats['mwes_cleaned']],
                ['MWEs skipped (complex issues)', $this->stats['mwes_skipped']],
                ['Expression records deleted', $this->stats['expressions_deleted']],
                ['Expression records updated', $this->stats['expressions_updated']],
                ['Errors encountered', $this->stats['errors']],
            ]
        );

        $this->newLine();

        if ($isDryRun) {
            $this->warn('⚠ DRY RUN - No changes were applied');
            $this->info('Run without --dry-run to apply these changes');
        } else {
            $this->info('✓ MWE duplicate expressions cleaned up');
            if ($this->stats['expressions_deleted'] > 0) {
                $this->comment('A deletion log has been saved with details of all deleted records');
            }
        }

        $this->newLine();
    }
}
