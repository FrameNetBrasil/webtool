<?php

namespace App\Console\Commands\ParserV4;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fix Pattern Format in parser_construction_v4
 *
 * Converts malformed JSON array patterns to proper BNF format.
 *
 * Before: "[\"café\"\" \"\"da\"\" \"\"manhã\"]"
 * After:  "café" "da" "manhã"
 *
 * Usage:
 *   php artisan parser:fix-pattern-format --grammar=1
 *   php artisan parser:fix-pattern-format --grammar=1 --dry-run
 */
class FixPatternFormatCommand extends Command
{
    protected $signature = 'parser:fix-pattern-format
                            {--grammar=1 : Grammar graph ID}
                            {--dry-run : Show what would be fixed without actually fixing}';

    protected $description = 'Fix pattern format from JSON array strings to BNF format';

    private int $fixed = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $grammarId = (int) $this->option('grammar');
        $dryRun = $this->option('dry-run');

        $this->info('Parser V4 Pattern Format Fixer');
        $this->info("Grammar Graph ID: {$grammarId}");
        $this->newLine();

        // Get all constructions
        $constructions = DB::table('parser_construction_v4')
            ->where('idGrammarGraph', $grammarId)
            ->get();

        if ($constructions->isEmpty()) {
            $this->warn('No constructions found for this grammar.');

            return 0;
        }

        $this->info("Found {$constructions->count()} constructions to check");
        $this->newLine();

        $bar = $this->output->createProgressBar($constructions->count());
        $bar->start();

        foreach ($constructions as $construction) {
            $result = $this->fixPattern($construction, $dryRun);

            if ($result === 'fixed') {
                $this->fixed++;
            } elseif ($result === 'skipped') {
                $this->skipped++;
            } else {
                $this->errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Show examples of fixes
        if ($this->fixed > 0 && ! $dryRun) {
            $this->showExamples($grammarId);
        }

        // Summary
        $this->info('Fix Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Fixed', $this->fixed],
                ['Skipped (already correct)', $this->skipped],
                ['Errors', $this->errors],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. No data was actually modified.');
        }

        return $this->errors > 0 ? 1 : 0;
    }

    private function fixPattern(object $construction, bool $dryRun): string
    {
        try {
            $pattern = $construction->pattern;

            // Check if pattern looks like malformed JSON array
            if (! $this->isJSONArrayPattern($pattern)) {
                return 'skipped';
            }

            // Convert to BNF format
            $bnfPattern = $this->convertToBNF($pattern);

            if ($bnfPattern === null) {
                $this->error("Failed to convert pattern for construction {$construction->name}");

                return 'error';
            }

            if ($dryRun) {
                return 'fixed'; // Count as fixed in dry run
            }

            // Update database
            DB::table('parser_construction_v4')
                ->where('idConstruction', $construction->idConstruction)
                ->update([
                    'pattern' => $bnfPattern,
                    'updated_at' => now(),
                ]);

            return 'fixed';
        } catch (\Exception $e) {
            $this->error("Error fixing {$construction->name}: ".$e->getMessage());

            return 'error';
        }
    }

    /**
     * Check if pattern looks like a malformed JSON array
     */
    private function isJSONArrayPattern(string $pattern): bool
    {
        // Remove outer quotes if present
        $pattern = trim($pattern);
        if (str_starts_with($pattern, '"') && str_ends_with($pattern, '"')) {
            $pattern = substr($pattern, 1, -1);
        }

        // Check if it starts with [ and ends with ]
        return str_starts_with($pattern, '[') && str_ends_with($pattern, ']');
    }

    /**
     * Convert JSON array pattern to BNF format
     *
     * Input:  "["café"" ""da"" ""manhã"]"
     * Output: "café" "da" "manhã"
     */
    private function convertToBNF(string $pattern): ?string
    {
        try {
            // Remove outer quotes if present
            $pattern = trim($pattern);
            if (str_starts_with($pattern, '"') && str_ends_with($pattern, '"')) {
                $pattern = substr($pattern, 1, -1);
            }

            // Try to decode as JSON first
            $decoded = json_decode($pattern, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Successfully decoded as JSON array
                return $this->arrayToBNF($decoded);
            }

            // If JSON decode fails, try manual parsing
            // Remove surrounding brackets
            $pattern = trim($pattern, '[]');

            // The pattern has escaped quotes like: "café"" ""da"" ""manhã"
            // Split by the "" "" pattern (double quote, space, double quote)
            $components = preg_split('/"\s+"/', $pattern);

            // Clean each component
            $cleaned = [];
            foreach ($components as $component) {
                $component = trim($component);
                // Remove surrounding quotes if present
                $component = trim($component, '"');
                // Unescape any escaped quotes
                $component = str_replace('\\"', '"', $component);
                // Handle unicode escapes
                $component = json_decode('"'.$component.'"') ?? $component;

                if (! empty($component)) {
                    $cleaned[] = $component;
                }
            }

            return $this->arrayToBNF($cleaned);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convert array of components to BNF format
     */
    private function arrayToBNF(array $components): string
    {
        // Wrap each component in quotes and join with spaces
        $bnfComponents = array_map(function ($component) {
            return "\"{$component}\"";
        }, $components);

        return implode(' ', $bnfComponents);
    }

    /**
     * Show examples of fixed patterns
     */
    private function showExamples(int $grammarId): void
    {
        $this->newLine();
        $this->info('Examples of fixed patterns:');

        $examples = DB::table('parser_construction_v4')
            ->where('idGrammarGraph', $grammarId)
            ->orderBy('idConstruction')
            ->limit(5)
            ->get(['name', 'pattern']);

        $tableData = [];
        foreach ($examples as $example) {
            $tableData[] = [
                'name' => $example->name,
                'pattern' => strlen($example->pattern) > 80
                    ? substr($example->pattern, 0, 77).'...'
                    : $example->pattern,
            ];
        }

        $this->table(['Construction Name', 'BNF Pattern'], $tableData);
        $this->newLine();
    }
}
