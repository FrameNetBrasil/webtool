<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateLuPosCommand extends Command
{
    protected $signature = 'fn3:validate-lu-pos
                            {--output=/home/ematos/devel/fnbr/webtool4/app/Console/Commands/FN3/Data/lu_pos_validation_errors.csv : Path to output CSV file}
                            {--language=1 : Language ID to filter LUs (default: 1 for Portuguese)}';

    protected $description = 'Validate LU POS based on frame namespace heuristics and export failures to CSV';

    /**
     * POS validation rules based on frame namespace.
     * Key: namespace name, Value: array of allowed POS values
     */
    private array $namespaceRules = [
        // Eventive frames - LU must be VERB (or deverbal NOUN)
        'Situation' => ['VERB'],
        'Eventive' => ['VERB'],
        'Causative' => ['VERB'],
        'Inchoative' => ['VERB'],
        'Action' => ['VERB'],
        'Transition' => ['VERB'],
        // Stative frames - LU must be ADJ
        'Stative' => ['ADJ'],
        'Attribute' => ['ADJ'],
        // Experiential frames - LU can be NOUN or VERB
        'Experiential' => ['NOUN', 'VERB'],
        // Entity frames - LU must be NOUN
        'Entity' => ['NOUN'],
        'Relational' => ['NOUN'],
    ];

    /**
     * Namespaces where deverbal NOUNs are acceptable (eventive-type frames)
     */
    private array $deverbalAllowedNamespaces = [
        'Eventive',
        'Causative',
        'Inchoative',
        'Action',
        'Transition',
    ];

    /**
     * Common Portuguese deverbal noun suffixes
     * These suffixes typically derive nouns from verbs, denoting actions or results
     */
    private array $deverbalSuffixes = [
        'ção',      // comunicar → comunicação
        'são',      // compreender → compreensão
        'mento',    // desenvolver → desenvolvimento
        'agem',     // armazenar → armazenagem
        'ura',      // abrir → abertura
        'ância',    // tolerar → tolerância
        'ência',    // existir → existência
        'ado',      // resultado, comunicado
        'ida',      // saída, corrida
        'ança',     // lembrar → lembrança
        'ença',     // diferir → diferença
        'dura',     // morder → mordedura
        'aria',     // piratear → pirataria
        'eria',     // correger → corregeria (less common)
        'io',       // começar → começo (when derived)
//        'a',        // lutar → luta, buscar → busca
//        'e',        // combater → combate, cortar → corte
//        'o',        // abraçar → abraço, grito
    ];

    public function handle(): int
    {
        $outputPath = $this->option('output');
        $languageId = (int) $this->option('language');

        // Make path absolute if relative
        if (!str_starts_with($outputPath, '/')) {
            $outputPath = base_path($outputPath);
        }

        $this->info('Validating LU POS based on frame namespace...');
        $this->newLine();

        // Step 1: Query all LUs with frame namespace
        $this->info('Querying LUs from view_lu_full...');
        $lus = $this->queryLus($languageId);
        $this->info("Found " . count($lus) . " LUs");

        // Step 2: Validate POS
        $this->newLine();
        $this->info('Validating POS against namespace rules...');
        $failures = $this->validateLus($lus);

        // Step 3: Write failures to CSV
        $this->newLine();
        if (count($failures) > 0) {
            $this->info("Writing " . count($failures) . " failures to CSV...");
            $this->writeCsv($outputPath, $failures);
            $this->info("CSV saved to: {$outputPath}");
        } else {
            $this->info("No validation failures found!");
        }

        // Step 4: Display summary
        $this->displaySummary($lus, $failures);

        return Command::SUCCESS;
    }

    private function queryLus(int $languageId): array
    {
        $query = "
            SELECT
                lu.idLU,
                lu.name,
                lu.senseDescription,
                lu.frameName,
                lu.lemmaName,
                lu.status,
                f.namespace,
                lemma.udPOS
            FROM view_lu_full lu
            JOIN view_frame f ON lu.idFrame = f.idFrame AND lu.idLanguage = f.idLanguage
            JOIN view_lemma lemma ON lu.idLemma = lemma.idLemma
            WHERE lu.idLanguage = ?
              AND lu.status != 'DELETED'
              AND lu.origin != 'LOME'
        ";

        return DB::select($query, [$languageId]);
    }

    private function validateLus(array $lus): array
    {
        $failures = [];

        foreach ($lus as $lu) {
            $namespace = $lu->namespace;
            $pos = $lu->udPOS;
            $lemmaName = $lu->lemmaName ?? '';

            // Skip namespaces not in our rules (e.g., Class, Microframe, Pragmatic)
            if (!isset($this->namespaceRules[$namespace])) {
                continue;
            }

            $allowedPos = $this->namespaceRules[$namespace];

            // Check if POS is valid for this namespace
            if (!in_array($pos, $allowedPos)) {
                // Special case: deverbal NOUNs are acceptable in eventive-type namespaces
                if ($pos === 'NOUN'
                    && in_array($namespace, $this->deverbalAllowedNamespaces)
                    && $this->isDeverbalNoun($lemmaName)
                ) {
                    continue; // Skip this - it's an acceptable deverbal noun
                }

                $failures[] = [
                    'idLU' => $lu->idLU,
                    'name' => $lu->name,
                    'udPOS' => $pos,
                    'senseDescription' => $lu->senseDescription ?? '',
                    'frameName' => $lu->frameName,
                    'namespace' => $namespace,
                    'expectedPOS' => implode(' or ', $allowedPos),
                    'lemmaName' => $lemmaName,
                    'status' => $lu->status,
                ];
            }
        }

        return $failures;
    }

    /**
     * Check if a lemma is likely a deverbal noun based on suffix patterns
     */
    private function isDeverbalNoun(string $lemmaName): bool
    {
        $lemmaLower = mb_strtolower($lemmaName);

        // Check longer suffixes first to avoid false matches
        // Sort suffixes by length (descending) for proper matching
        $sortedSuffixes = $this->deverbalSuffixes;
        usort($sortedSuffixes, fn($a, $b) => mb_strlen($b) - mb_strlen($a));

        foreach ($sortedSuffixes as $suffix) {
            if (mb_substr($lemmaLower, -mb_strlen($suffix)) === $suffix) {
                // Additional check: the word should be at least suffix + 2 chars
                // to avoid matching very short words that happen to end with the suffix
                if (mb_strlen($lemmaLower) >= mb_strlen($suffix) + 2) {
                    return true;
                }
            }
        }

        return false;
    }

    private function writeCsv(string $path, array $failures): void
    {
        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($path, 'w');

        // Sort failures by frameName alphabetically
        usort($failures, fn($a, $b) => strcasecmp($a['frameName'], $b['frameName']));

        // Write header
        fputcsv($handle, ['idLU', 'frameName', 'namespace', 'expected_pos', 'lu_name', 'udPOS', 'lu_status', 'senseDescription']);

        // Write data
        foreach ($failures as $row) {
            fputcsv($handle, [
                $row['idLU'],
                $row['frameName'],
                $row['namespace'],
                $row['expectedPOS'],
                $row['name'],
                $row['udPOS'],
                $row['status'],
                $row['senseDescription'],
            ]);
        }

        fclose($handle);
    }

    private function displaySummary(array $lus, array $failures): void
    {
        $this->newLine();
        $this->info('=== Validation Summary ===');

        // Count LUs by namespace
        $byNamespace = [];
        foreach ($lus as $lu) {
            $ns = $lu->namespace;
            if (!isset($byNamespace[$ns])) {
                $byNamespace[$ns] = ['total' => 0, 'failures' => 0];
            }
            $byNamespace[$ns]['total']++;
        }

        // Count failures by namespace
        foreach ($failures as $failure) {
            $ns = $failure['namespace'];
            $byNamespace[$ns]['failures']++;
        }

        // Count failures by POS
        $failuresByPos = [];
        foreach ($failures as $failure) {
            $key = "{$failure['namespace']}:{$failure['udPOS']}";
            if (!isset($failuresByPos[$key])) {
                $failuresByPos[$key] = [
                    'namespace' => $failure['namespace'],
                    'actualPOS' => $failure['udPOS'],
                    'expectedPOS' => $failure['expectedPOS'],
                    'count' => 0,
                ];
            }
            $failuresByPos[$key]['count']++;
        }

        // Summary table
        $totalValidated = 0;
        $tableData = [];
        foreach ($this->namespaceRules as $namespace => $allowedPos) {
            if (isset($byNamespace[$namespace])) {
                $total = $byNamespace[$namespace]['total'];
                $fails = $byNamespace[$namespace]['failures'];
                $totalValidated += $total;
                $tableData[] = [
                    $namespace,
                    implode(', ', $allowedPos),
                    $total,
                    $fails,
                    $this->percent($fails, $total),
                ];
            }
        }

        $this->table(
            ['Namespace', 'Expected POS', 'Total LUs', 'Failures', 'Failure %'],
            $tableData
        );

        // Failures breakdown
        if (count($failuresByPos) > 0) {
            $this->newLine();
            $this->info('=== Failures by POS ===');

            $posTableData = [];
            foreach ($failuresByPos as $data) {
                $posTableData[] = [
                    $data['namespace'],
                    $data['actualPOS'],
                    $data['expectedPOS'],
                    $data['count'],
                ];
            }

            // Sort by count descending
            usort($posTableData, fn($a, $b) => $b[3] <=> $a[3]);

            $this->table(
                ['Namespace', 'Actual POS', 'Expected POS', 'Count'],
                $posTableData
            );
        }

        // Final summary
        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total LUs analyzed', count($lus)],
                ['LUs in validated namespaces', $totalValidated],
                ['Total failures', count($failures)],
                ['Failure rate', $this->percent(count($failures), $totalValidated)],
            ]
        );

        if (count($failures) > 0) {
            $this->newLine();
            $this->warn("Found " . count($failures) . " LUs with POS mismatches.");
        } else {
            $this->newLine();
            $this->info("All LUs pass POS validation!");
        }
    }

    private function percent(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }

        return round(($value / $total) * 100, 1) . '%';
    }
}
