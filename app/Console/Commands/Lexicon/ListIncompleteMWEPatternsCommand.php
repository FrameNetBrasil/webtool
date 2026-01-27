<?php

namespace App\Console\Commands\Lexicon;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListIncompleteMWEPatternsCommand extends Command
{
    protected $signature = 'lexicon:list-incomplete-mwe
                            {--language=1 : Language ID (default: 1 for Portuguese)}
                            {--export= : Export to CSV file path}
                            {--limit=50 : Limit number of results (0 for all)}';

    protected $description = 'List MWE patterns with incomplete node data (missing idLemma)';

    public function handle(): int
    {
        $idLanguage = (int) $this->option('language');
        $exportPath = $this->option('export');
        $limit = (int) $this->option('limit');

        $this->info("Listing incomplete MWE patterns for language ID: {$idLanguage}");
        $this->newLine();

        $query = Criteria::table('lexicon_pattern as lp')
            ->join('view_lemma as vl', 'vl.idLemma', '=', 'lp.idLemma')
            ->where('lp.patternType', '=', 'MWE')
            ->where('vl.idLanguage', '=', $idLanguage)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('lexicon_pattern_node as lpn')
                    ->whereColumn('lpn.idLexiconPattern', '=', 'lp.idLexiconPattern')
                    ->whereNull('lpn.idLemma');
            })
            ->select(
                'lp.idLexiconPattern',
                'vl.idLemma as mweLemma',
                'vl.name as mweName'
            )
            ->orderBy('vl.name');

        $patterns = $query->get();
        $totalCount = $patterns->count();

        $this->info("Found {$this->formatNumber($totalCount)} incomplete MWE patterns");
        $this->newLine();

        // Get node counts for each pattern
        $results = [];
        foreach ($patterns as $pattern) {
            $nodes = Criteria::table('lexicon_pattern_node')
                ->where('idLexiconPattern', '=', $pattern->idLexiconPattern)
                ->select('idLemma', 'position')
                ->orderBy('position')
                ->get();

            $totalNodes = $nodes->count();
            $nodesWithLemma = $nodes->whereNotNull('idLemma')->count();
            $missingPositions = $nodes->whereNull('idLemma')->pluck('position')->implode(', ');

            $results[] = [
                'idLexiconPattern' => $pattern->idLexiconPattern,
                'mweLemma' => $pattern->mweLemma,
                'mweName' => $pattern->mweName,
                'totalNodes' => $totalNodes,
                'nodesWithLemma' => $nodesWithLemma,
                'missingPositions' => $missingPositions,
            ];
        }

        if ($exportPath) {
            $this->exportToCsv($results, $exportPath);
            $this->info("Exported {$this->formatNumber($totalCount)} patterns to: {$exportPath}");

            return Command::SUCCESS;
        }

        // Display table
        $displayResults = $limit > 0 ? array_slice($results, 0, $limit) : $results;

        $tableData = [];
        foreach ($displayResults as $result) {
            $tableData[] = [
                $result['idLexiconPattern'],
                $result['mweLemma'],
                mb_substr($result['mweName'], 0, 40),
                "{$result['nodesWithLemma']}/{$result['totalNodes']}",
                $result['missingPositions'],
            ];
        }

        $this->table(
            ['Pattern ID', 'MWE Lemma', 'MWE Name', 'Nodes', 'Missing Positions'],
            $tableData
        );

        if ($limit > 0 && $totalCount > $limit) {
            $this->newLine();
            $this->info("Showing {$limit} of {$this->formatNumber($totalCount)} patterns. Use --limit=0 to show all or --export=<path> to export.");
        }

        return Command::SUCCESS;
    }

    private function exportToCsv(array $results, string $path): void
    {
        $handle = fopen($path, 'w');

        // Header
        fputcsv($handle, [
            'idLexiconPattern',
            'mweLemma',
            'mweName',
            'totalNodes',
            'nodesWithLemma',
            'missingPositions',
        ]);

        // Data
        foreach ($results as $result) {
            fputcsv($handle, $result);
        }

        fclose($handle);
    }

    private function formatNumber(int $number): string
    {
        return number_format($number);
    }
}
