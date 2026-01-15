<?php

namespace App\Console\Commands\Parser;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateMweLexiconExpressionsCommand extends Command
{
    protected $signature = 'parser:validate-mwe-expressions {--dry-run : Preview changes without applying them}';

    protected $description = 'Validate and fix MWE lemmas in lexicon_expression table - ensures each n-word MWE has exactly n expression records';

    private array $stats = [
        'total_mwes_checked' => 0,
        'mwes_with_issues' => 0,
        'expressions_created' => 0,
        'lexicons_created' => 0,
        'errors' => 0,
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Validating MWE Lexicon Expressions for Portuguese (idLanguage = 1)...');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be applied');
        }
        $this->newLine();

        // Get all MWEs with issues
        $problematicMwes = $this->getProblematicMwes();

        $this->stats['total_mwes_checked'] = count($problematicMwes);

        if (empty($problematicMwes)) {
            $this->info('✓ All MWE lemmas have correct lexicon_expression records!');

            return Command::SUCCESS;
        }

        $this->stats['mwes_with_issues'] = count($problematicMwes);
        $this->info("Found {$this->stats['mwes_with_issues']} MWEs with incorrect expression counts");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($problematicMwes));
        $progressBar->start();

        foreach ($problematicMwes as $mwe) {
            try {
                $this->fixMweExpressions($mwe, $isDryRun);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                logger()->error("Failed to fix MWE {$mwe->idLemma} ({$mwe->mwe_phrase}): ".$e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($isDryRun);

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
            ORDER BY lx.form
        ";

        return DB::select($query);
    }

    private function fixMweExpressions(object $mwe, bool $isDryRun): void
    {
        // Split MWE into individual words
        $words = explode(' ', $mwe->mwe_phrase);
        $expectedCount = count($words);

        // Get existing expressions for this lemma
        $existingExpressions = Criteria::table('lexicon_expression')
            ->where('idLemma', $mwe->idLemma)
            ->orderBy('position')
            ->get();

        $existingCount = $existingExpressions->count();

        if ($existingCount == 0) {
            // No expressions exist - create all
            foreach ($words as $position => $word) {
                $this->createLexiconExpression($mwe->idLemma, $word, $position + 1, $isDryRun);
            }
        } elseif ($existingCount < $expectedCount) {
            // Some expressions exist but not enough
            // Get existing positions
            $existingPositions = $existingExpressions->pluck('position')->toArray();

            // Create missing positions
            for ($position = 1; $position <= $expectedCount; $position++) {
                if (! in_array($position, $existingPositions)) {
                    $word = $words[$position - 1];
                    $this->createLexiconExpression($mwe->idLemma, $word, $position, $isDryRun);
                }
            }
        } elseif ($existingCount > $expectedCount) {
            // Too many expressions - this is unusual, log it
            $this->warn("MWE {$mwe->idLemma} ({$mwe->mwe_phrase}) has {$existingCount} expressions but only {$expectedCount} words - skipping");
            $this->stats['errors']++;
        }
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

    private function displaySummary(bool $isDryRun): void
    {
        $this->info('=== MWE Validation Summary ===');

        $this->table(
            ['Metric', 'Count'],
            [
                ['MWEs checked', $this->stats['total_mwes_checked']],
                ['MWEs with issues', $this->stats['mwes_with_issues']],
                ['Lexicon entries created', $this->stats['lexicons_created']],
                ['Expression records created', $this->stats['expressions_created']],
                ['Errors encountered', $this->stats['errors']],
            ]
        );

        $this->newLine();

        if ($isDryRun) {
            $this->warn('⚠ DRY RUN - No changes were applied');
            $this->info('Run without --dry-run to apply these changes');
        } else {
            $this->info('✓ MWE lexicon expressions validated and fixed');
        }

        $this->newLine();
    }
}
