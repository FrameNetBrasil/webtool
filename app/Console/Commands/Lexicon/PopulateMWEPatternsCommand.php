<?php

namespace App\Console\Commands\Lexicon;

use App\Database\Criteria;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateMWEPatternsCommand extends Command
{
    protected $signature = 'lexicon:populate-mwe-patterns
                            {--dry-run : Simulate without database changes}';

    protected $description = 'Populate nodes and edges for MWE patterns from lexicon_pattern table';

    private const DEFAULT_UD_RELATION_ID = 20; // 'fixed' relation for MWE

    private array $stats = [
        'patterns_processed' => 0,
        'nodes_created' => 0,
        'edges_created' => 0,
        'words_not_found' => 0,
        'empty_lemmas_skipped' => 0,
        'single_word_patterns' => 0,
    ];

    private array $lemmaCache = [];

    private array $lexiconCache = [];

    private array $wordsNotFound = [];

    public function handle(): int
    {
        $this->info('Populate MWE Pattern Nodes and Edges');
        $this->newLine();

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No database changes will be made');
            $this->newLine();
        }

        $this->info('Building lookup caches...');
        $this->buildLookupCaches();
        $this->info('  - Lemma cache: '.$this->formatNumber(count($this->lemmaCache)).' entries');
        $this->info('  - Lexicon lookups: on-demand (cached as used)');
        $this->newLine();

        $this->info('Processing MWE patterns...');
        $this->newLine();

        try {
            if ($isDryRun) {
                $this->processPatterns(true);
            } else {
                DB::transaction(function () {
                    $this->processPatterns(false);
                });
            }
        } catch (Exception $e) {
            $this->error('Error processing patterns: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->newLine();
        $this->displaySummary($isDryRun);

        if (! empty($this->wordsNotFound)) {
            $this->newLine();
            $this->displayWordsNotFound();
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('Dry run completed. Use without --dry-run to create nodes and edges.');
        }

        return Command::SUCCESS;
    }

    private function buildLookupCaches(): void
    {
        // Build lemma cache from view_lemma (word -> idLexicon)
        // This is manageable in size
        $lemmas = Criteria::table('view_lemma')
            ->select('name', 'idLexicon')
            ->get();

        foreach ($lemmas as $lemma) {
            $name = mb_strtolower($lemma->name);
            if (! isset($this->lemmaCache[$name])) {
                $this->lemmaCache[$name] = $lemma->idLexicon;
            }
        }

        // Note: lexiconCache is built on-demand during lookupIdLexicon
        // to avoid loading 1M+ records into memory
    }

    private function processPatterns(bool $isDryRun): void
    {
        // Get all MWE patterns with their lemma names
        $patterns = Criteria::table('lexicon_pattern as lp')
            ->join('view_lemma as vl', 'vl.idLemma', '=', 'lp.idLemma')
            ->where('lp.patternType', 'MWE')
            ->select('lp.idLexiconPattern', 'vl.name')
            ->get();

        $total = count($patterns);
        $this->info("Found {$this->formatNumber($total)} MWE patterns to process");
        $this->newLine();

        $this->withProgressBar($patterns, function ($pattern) use ($isDryRun) {
            $this->processPattern($pattern, $isDryRun);
        });

        $this->newLine();
    }

    private function processPattern(object $pattern, bool $isDryRun): void
    {
        $patternId = $pattern->idLexiconPattern;
        $lemmaName = trim($pattern->name);

        // Skip empty lemma names
        if (empty($lemmaName)) {
            $this->stats['empty_lemmas_skipped']++;

            return;
        }

        // Split lemma name into words
        $words = preg_split('/\s+/', $lemmaName);
        $words = array_filter($words, fn ($w) => ! empty(trim($w)));
        $words = array_values($words);

        if (empty($words)) {
            $this->stats['empty_lemmas_skipped']++;

            return;
        }

        // Track single-word patterns
        if (count($words) === 1) {
            $this->stats['single_word_patterns']++;
        }

        // Create nodes
        $nodeIds = $this->createNodes($patternId, $words, $isDryRun);

        // Create edges (only if more than one word)
        if (count($nodeIds) > 1) {
            $this->createEdges($patternId, $nodeIds, $isDryRun);
        }

        $this->stats['patterns_processed']++;
    }

    private function lookupIdLexicon(string $word): ?int
    {
        $lowerWord = mb_strtolower($word);

        // First: try lemma cache (from view_lemma)
        if (isset($this->lemmaCache[$lowerWord])) {
            return $this->lemmaCache[$lowerWord];
        }

        // Check if we've already looked up this word in lexicon
        if (array_key_exists($lowerWord, $this->lexiconCache)) {
            $cachedValue = $this->lexiconCache[$lowerWord];
            if ($cachedValue === null) {
                // We've already looked and found nothing
                $this->stats['words_not_found']++;
                if (! isset($this->wordsNotFound[$word])) {
                    $this->wordsNotFound[$word] = 0;
                }
                $this->wordsNotFound[$word]++;
            }

            return $cachedValue;
        }

        // Fallback: query lexicon table on-demand
        $lexicon = Criteria::table('lexicon')
            ->where('form', '=', $word)
            ->orderBy('idLexicon')
            ->first();

        if ($lexicon) {
            $this->lexiconCache[$lowerWord] = $lexicon->idLexicon;

            return $lexicon->idLexicon;
        }

        // Not found - cache the null result and track for reporting
        $this->lexiconCache[$lowerWord] = null;
        $this->stats['words_not_found']++;
        if (! isset($this->wordsNotFound[$word])) {
            $this->wordsNotFound[$word] = 0;
        }
        $this->wordsNotFound[$word]++;

        return null;
    }

    /**
     * @return array<int> Array of created node IDs
     */
    private function createNodes(int $patternId, array $words, bool $isDryRun): array
    {
        $nodeIds = [];

        foreach ($words as $position => $word) {
            $idLexicon = $this->lookupIdLexicon($word);
            $isRoot = $position === 0 ? 1 : 0;

            if (! $isDryRun) {
                $nodeId = Criteria::create('lexicon_pattern_node', [
                    'idLexiconPattern' => $patternId,
                    'position' => $position,
                    'idLexicon' => $idLexicon,
                    'idUDPOS' => null,
                    'isRoot' => $isRoot,
                    'isRequired' => 1,
                ]);
                $nodeIds[] = $nodeId;
            } else {
                // For dry run, use position as placeholder ID
                $nodeIds[] = $position;
            }

            $this->stats['nodes_created']++;
        }

        return $nodeIds;
    }

    private function createEdges(int $patternId, array $nodeIds, bool $isDryRun): void
    {
        // Create edges for consecutive nodes
        // Edge structure: first word is HEAD, subsequent words chain from it
        // edge1: node[0] (head) -> node[1] (dependent)
        // edge2: node[1] (head) -> node[2] (dependent)
        for ($i = 0; $i < count($nodeIds) - 1; $i++) {
            if (! $isDryRun) {
                Criteria::create('lexicon_pattern_edge', [
                    'idLexiconPattern' => $patternId,
                    'idNodeHead' => $nodeIds[$i],
                    'idNodeDependent' => $nodeIds[$i + 1],
                    'idUDRelation' => self::DEFAULT_UD_RELATION_ID,
                ]);
            }

            $this->stats['edges_created']++;
        }
    }

    private function displaySummary(bool $isDryRun): void
    {
        $suffix = $isDryRun ? ' (would be)' : '';

        $this->info('Summary:');
        $this->newLine();

        $tableData = [
            ['Patterns processed', $this->formatNumber($this->stats['patterns_processed'])],
            ['Nodes created'.$suffix, $this->formatNumber($this->stats['nodes_created'])],
            ['Edges created'.$suffix, $this->formatNumber($this->stats['edges_created'])],
            ['Single-word patterns', $this->formatNumber($this->stats['single_word_patterns'])],
            ['Empty lemmas skipped', $this->formatNumber($this->stats['empty_lemmas_skipped'])],
            ['Words not found in lexicon', $this->formatNumber($this->stats['words_not_found'])],
        ];

        $this->table(['Metric', 'Count'], $tableData);
    }

    private function displayWordsNotFound(): void
    {
        $this->warn('Words not found in lexicon (sample - first 20):');
        $this->newLine();

        // Sort by frequency descending
        arsort($this->wordsNotFound);
        $sample = array_slice($this->wordsNotFound, 0, 20, true);

        $tableData = [];
        foreach ($sample as $word => $count) {
            $tableData[] = [$word, $count];
        }

        $this->table(['Word', 'Occurrences'], $tableData);

        $uniqueCount = count($this->wordsNotFound);
        if ($uniqueCount > 20) {
            $this->line('  ... and '.($uniqueCount - 20).' more unique words');
        }
    }

    private function formatNumber(int $number): string
    {
        return number_format($number);
    }
}
