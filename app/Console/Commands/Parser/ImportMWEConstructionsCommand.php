<?php

namespace App\Console\Commands\Parser;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import MWE Constructions from Lemmas
 *
 * Automatically imports Multi-Word Expression (MWE) constructions from lemmas
 * in the database that contain spaces (identified as MWEs).
 *
 * Query: select idLemma,name from view_lemma where idlanguage=1 and name like '% %'
 *
 * Creates construction patterns for each MWE lemma with:
 * - Pattern: quoted words from lemma (e.g., "café" "da" "manhã")
 * - Name: MWE_<UPPERCASED_NAME>
 * - Aggregate: underscored version of lemma
 * - Default priority and settings
 *
 * V5 Extensions:
 * - mandatoryElements: All tokens in the MWE are mandatory
 * - optionalElements: Empty by default
 * - ghostCreationRules: null (MWEs don't create ghosts)
 *
 * Usage:
 *   php artisan parser:import-mwe-constructions --grammar=1 --language=1
 *   php artisan parser:import-mwe-constructions --grammar=1 --language=1 --clear
 *   php artisan parser:import-mwe-constructions --dry-run --limit=10
 */
class ImportMWEConstructionsCommand extends Command
{
    protected $signature = 'parser:import-mwe-constructions
                            {--grammar=1 : Grammar graph ID}
                            {--language=1 : Language ID (1=Portuguese, 2=English, etc.)}
                            {--clear : Clear existing MWE constructions before import}
                            {--dry-run : Show what would be imported without actually importing}
                            {--limit= : Limit number of MWEs to import (for testing)}
                            {--priority=170 : Default priority for MWE constructions}
                            {--phrasal-ce=Head : Default phrasal CE for MWEs}';

    protected $description = 'Import MWE constructions automatically from database lemmas';

    private int $imported = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $grammarId = (int) $this->option('grammar');
        $languageId = (int) $this->option('language');
        $clear = $this->option('clear');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        $priority = (int) $this->option('priority');
        $phrasalCE = $this->option('phrasal-ce');

        $this->info('Parser MWE Construction Import');
        $this->info("Grammar Graph ID: {$grammarId}");
        $this->info("Language ID: {$languageId}");
        $this->info("Default Priority: {$priority}");
        $this->info("Default Phrasal CE: {$phrasalCE}");
        $this->newLine();

        // Verify V5 schema extensions exist
        if (! $this->verifyV5Schema($dryRun)) {
            $this->warn('V5 schema extensions not found. Import will continue with V4 schema only.');
        }

        // Clear existing MWE constructions if requested
        if ($clear && ! $dryRun) {
            $this->clearExistingMWEConstructions($grammarId);
        }

        // Fetch MWE lemmas from database
        $mweLemmas = $this->fetchMWELemmas($languageId, $limit);

        if (empty($mweLemmas)) {
            $this->warn('No MWE lemmas found in the database.');

            return 0;
        }

        $count = count($mweLemmas);
        $this->info("Found {$count} MWE lemmas");
        $this->newLine();

        // Import each MWE as a construction
        $bar = $this->output->createProgressBar(count($mweLemmas));
        $bar->start();

        foreach ($mweLemmas as $lemma) {
            $result = $this->importMWEConstruction(
                lemma: $lemma,
                grammarId: $grammarId,
                priority: $priority,
                phrasalCE: $phrasalCE,
                dryRun: $dryRun
            );

            if ($result === 'imported') {
                $this->imported++;
            } elseif ($result === 'skipped') {
                $this->skipped++;
            } else {
                $this->errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Import Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Imported', $this->imported],
                ['Skipped', $this->skipped],
                ['Errors', $this->errors],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. No data was actually imported.');
        }

        return $this->errors > 0 ? 1 : 0;
    }

    private function verifyV5Schema(bool $dryRun): bool
    {
        if ($dryRun) {
            return true; // Skip verification in dry-run mode
        }

        // Check if V5 columns exist in parser_construction_v4
        $columns = DB::select(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'parser_construction_v4'
             AND COLUMN_NAME IN ('mandatoryElements', 'optionalElements', 'ghostCreationRules')"
        );

        $foundColumns = array_column($columns, 'COLUMN_NAME');

        return count($foundColumns) === 3;
    }

    private function clearExistingMWEConstructions(int $grammarId): void
    {
        $this->warn("Clearing existing MWE constructions for grammar {$grammarId}...");

        $deleted = DB::table('parser_construction_v4')
            ->where('idGrammarGraph', $grammarId)
            ->where('constructionType', 'mwe')
            ->delete();

        $this->info("Deleted {$deleted} existing MWE constructions");
        $this->newLine();
    }

    private function fetchMWELemmas(int $languageId, ?string $limit): array
    {
        $query = "SELECT idLemma, name
                  FROM view_lemma
                  WHERE idlanguage = ?
                  AND name LIKE '% %'
                  ORDER BY name";

        if ($limit) {
            $query .= " LIMIT {$limit}";
        }

        return DB::select($query, [$languageId]);
    }

    private function importMWEConstruction(
        object $lemma,
        int $grammarId,
        int $priority,
        string $phrasalCE,
        bool $dryRun
    ): string {
        try {
            $name = $this->generateConstructionName($lemma->name);

            // Check if construction already exists
            $existing = DB::table('parser_construction_v4')
                ->where('idGrammarGraph', $grammarId)
                ->where('name', $name)
                ->first();

            if ($existing) {
                return 'skipped';
            }

            if ($dryRun) {
                return 'imported'; // Count as imported in dry run
            }

            // Generate pattern from lemma name
            $pattern = $this->generatePattern($lemma->name);
            $aggregateAs = $this->generateAggregateAs($lemma->name);
            $tokens = $this->extractTokens($lemma->name);

            // Prepare insert data
            $insertData = [
                'idGrammarGraph' => $grammarId,
                'name' => $name,
                'constructionType' => 'mwe',
                'pattern' => $pattern,
                'compiledPattern' => null, // Will be compiled by PatternCompiler
                'priority' => $priority,
                'enabled' => true,
                'phrasalCE' => $phrasalCE,
                'clausalCE' => null,
                'sententialCE' => null,
                'constraints' => json_encode([]),
                'aggregateAs' => $aggregateAs,
                'semanticType' => null, // Can be set later
                'semantics' => json_encode(['type' => 'mwe', 'lemmaId' => $lemma->idLemma]),
                'lookaheadEnabled' => false,
                'lookaheadMaxDistance' => 2,
                'invalidationPatterns' => json_encode([]),
                'confirmationPatterns' => json_encode([]),
                'description' => "MWE: {$lemma->name}",
                'examples' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Add V5 extensions if schema supports it
            if ($this->verifyV5Schema($dryRun)) {
                // All tokens in MWE are mandatory
                $insertData['mandatoryElements'] = json_encode($tokens);
                $insertData['optionalElements'] = json_encode([]);
                $insertData['ghostCreationRules'] = null; // MWEs don't create ghosts
            }

            // Insert construction
            DB::table('parser_construction_v4')->insert($insertData);

            return 'imported';
        } catch (\Exception $e) {
            $this->error("Error importing {$lemma->name}: ".$e->getMessage());

            return 'error';
        }
    }

    /**
     * Generate construction name from lemma
     * Example: "café da manhã" -> "MWE_CAFE_DA_MANHA"
     */
    private function generateConstructionName(string $lemmaName): string
    {
        $normalized = $this->removeAccents($lemmaName);
        $uppercased = Str::upper($normalized);
        $underscored = str_replace(' ', '_', $uppercased);

        return "MWE_{$underscored}";
    }

    /**
     * Generate pattern from lemma name
     * Example: "café da manhã" -> "\"café\" \"da\" \"manhã\""
     */
    private function generatePattern(string $lemmaName): string
    {
        $words = explode(' ', $lemmaName);
        $quotedWords = array_map(fn ($word) => "\"{$word}\"", $words);

        return implode(' ', $quotedWords);
    }

    /**
     * Generate aggregateAs from lemma name
     * Example: "café da manhã" -> "café_da_manhã"
     */
    private function generateAggregateAs(string $lemmaName): string
    {
        return str_replace(' ', '_', $lemmaName);
    }

    /**
     * Extract tokens from lemma name
     * Example: "café da manhã" -> ["café", "da", "manhã"]
     */
    private function extractTokens(string $lemmaName): array
    {
        return explode(' ', $lemmaName);
    }

    /**
     * Remove accents from string
     */
    private function removeAccents(string $str): string
    {
        $unwanted_array = [
            'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c',
            'Ñ' => 'N', 'ñ' => 'n',
        ];

        return strtr($str, $unwanted_array);
    }
}
