<?php

namespace App\Console\Commands\FlatSyntax;

use App\Services\Trankit\TrankitService;
use Exception;
use Illuminate\Console\Command;

class AnnotateStage1Command extends Command
{
    protected $signature = 'flat-syntax:annotate-stage1
                            {--input= : Input file path (default: sentences_01.txt)}
                            {--output= : Output file path (default: sentences_01_stage1.txt)}
                            {--dry-run : Preview without writing file}
                            {--debug : Show detailed UD parse information}
                            {--limit= : Limit number of sentences to process}';

    protected $description = 'Stage 1: Parse sentences and add flat syntax boundary markers (separators only, no CE labels)';

    private TrankitService $trankit;
    private bool $isDryRun = false;
    private bool $debugMode = false;

    private array $stats = [
        'total_sentences' => 0,
        'sentences_processed' => 0,
        'sentences_failed' => 0,
        'parse_errors' => 0,
        'boundaries_inserted' => [
            'phrase' => 0,      // +
            'clause' => 0,      // #
            'sentence' => 0,    // .
            'interruption' => 0, // {}
            'mwe' => 0,         // ^
        ],
    ];

    public function handle(): int
    {
        // Parse options
        $this->isDryRun = $this->option('dry-run');
        $this->debugMode = $this->option('debug');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        // Get file paths
        $inputPath = $this->option('input')
            ?? app_path('Console/Commands/FlatSyntax/Data/sentences_01.txt');
        $outputPath = $this->option('output')
            ?? app_path('Console/Commands/FlatSyntax/Data/sentences_01_stage1.txt');

        // Display configuration
        $this->displayConfiguration($inputPath, $outputPath);

        // Validate input file
        if (!file_exists($inputPath)) {
            $this->error("Input file not found: {$inputPath}");
            return Command::FAILURE;
        }

        // Read input sentences
        $sentences = $this->parseInputFile($inputPath);
        if (empty($sentences)) {
            $this->warn('No sentences found in input file');
            return Command::SUCCESS;
        }

        $this->stats['total_sentences'] = count($sentences);

        // Apply limit if specified
        if ($limit && $limit < count($sentences)) {
            $sentences = array_slice($sentences, 0, $limit);
            $this->info("Processing limited to {$limit} sentences");
        }

        // Initialize Trankit service
        try {
            $this->trankit = new TrankitService();
            $this->trankit->init(config('udparser.trankit_url'));

            if (!$this->validateConfiguration()) {
                return Command::FAILURE;
            }
        } catch (Exception $e) {
            $this->error('Failed to initialize Trankit service: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Processing sentences...');
        $this->newLine();

        // Process sentences
        $results = [];
        $this->withProgressBar($sentences, function ($sentenceData) use (&$results) {
            $result = $this->processSentence($sentenceData);
            if ($result !== null) {
                $results[] = $result;
            }
        });

        $this->newLine(2);

        // Write output
        if (!$this->isDryRun && !empty($results)) {
            try {
                $this->writeOutputFile($outputPath, $results);
                $this->info("Results written to: {$outputPath}");
            } catch (Exception $e) {
                $this->error('Failed to write output file: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } elseif ($this->isDryRun) {
            $this->info('Dry run - showing first 5 results:');
            $this->newLine();
            foreach (array_slice($results, 0, 5) as $result) {
                $this->line($result);
            }
            if (count($results) > 5) {
                $this->line('... (and ' . (count($results) - 5) . ' more)');
            }
        }

        // Display summary
        $this->displaySummary();

        return Command::SUCCESS;
    }

    /**
     * Process a single sentence and return annotated result
     */
    private function processSentence(array $sentenceData): ?string
    {
        $lineNum = $sentenceData['line'];
        $sentence = $sentenceData['text'];

        try {
            // Parse with Trankit
            $result = $this->trankit->getUDTrankit($sentence, 1); // 1 = Portuguese
            $tokens = $result->udpipe;

            if (empty($tokens)) {
                throw new Exception('Empty parse result');
            }

            if ($this->debugMode) {
                $this->debugPrintSentence($lineNum, $sentence, $tokens);
            }

            // Identify clauses
            $clauses = $this->identifyClauses($tokens);

            // Detect multiword expressions first
            $this->detectMultiwordExpressions($tokens);

            // Detect phrase boundaries
            $this->detectPhraseBoundaries($tokens, $clauses);

            // Detect clause boundaries
            $this->detectClauseBoundaries($tokens, $clauses);

            // Detect interruptions
            $this->detectInterruptions($tokens, $clauses);

            // Construct annotated sentence
            $annotated = $this->constructAnnotatedSentence($tokens);

            $this->stats['sentences_processed']++;

            return $annotated;

        } catch (Exception $e) {
            $this->stats['sentences_failed']++;
            $this->stats['parse_errors']++;
            logger()->error("Parse failed for sentence line {$lineNum}: " . $e->getMessage(), [
                'sentence' => $sentence,
            ]);
            return null;
        }
    }

    /**
     * Identify all clauses in the sentence
     */
    private function identifyClauses(array &$tokens): array
    {
        $clauses = [];
        $clauseId = 0;

        // Find all predicates (clause roots)
        $predicates = [];
        foreach ($tokens as $token) {
            if ($this->isClauseRoot($token)) {
                $predicates[] = $token['id'];
            }
        }

        // Build clauses
        foreach ($predicates as $predicateId) {
            $clauseId++;

            // Get all tokens in this clause (descendants, excluding subordinate clause tokens)
            $clauseTokenIds = $this->getClauseTokens($predicateId, $tokens, $predicates);

            $clause = [
                'id' => $clauseId,
                'root_token_id' => $predicateId,
                'token_ids' => $clauseTokenIds,
                'span' => [min($clauseTokenIds), max($clauseTokenIds)],
                'is_main' => $tokens[$predicateId]['rel'] === 'root',
                'is_embedded' => false,
                'parent_clause_id' => null,
                'type' => $tokens[$predicateId]['rel'],
            ];

            // Mark tokens with their clause ID
            foreach ($clauseTokenIds as $tokenId) {
                $tokens[$tokenId]['clause_id'] = $clauseId;
            }

            $clauses[] = $clause;
        }

        // Determine parent-child relationships between clauses
        foreach ($clauses as $idx => $clause) {
            $rootToken = $tokens[$clause['root_token_id']];
            if ($rootToken['parent'] > 0) {
                $parentClauseId = $tokens[$rootToken['parent']]['clause_id'] ?? null;
                if ($parentClauseId !== null) {
                    $clauses[$idx]['parent_clause_id'] = $parentClauseId;
                }
            }
        }

        return $clauses;
    }

    /**
     * Check if token is a clause root (predicate)
     */
    private function isClauseRoot(array $token): bool
    {
        $predicateRelations = ['root', 'ccomp', 'xcomp', 'acl', 'acl:relcl', 'advcl', 'parataxis'];

        if (in_array($token['rel'], $predicateRelations)) {
            return true;
        }

        // Only consider conj if the token itself is a verb (coordinated clause)
        if ($token['rel'] === 'conj' && in_array($token['pos'], ['VERB', 'AUX'])) {
            return true;
        }

        return false;
    }

    /**
     * Get all token IDs that belong to a clause
     */
    private function getClauseTokens(int $rootId, array $tokens, array $allPredicates): array
    {
        $clauseTokens = [$rootId];
        $descendants = $this->getTokenDescendants($rootId, $tokens, $allPredicates);
        $clauseTokens = array_merge($clauseTokens, $descendants);

        return array_unique($clauseTokens);
    }

    /**
     * Get all descendants of a token, stopping at other predicates
     */
    private function getTokenDescendants(int $tokenId, array $tokens, array $stopAtPredicates): array
    {
        $descendants = [];

        foreach ($tokens as $token) {
            if ($token['parent'] === $tokenId) {
                // Don't include other predicates or their descendants
                if (in_array($token['id'], $stopAtPredicates) && $token['id'] !== $tokenId) {
                    continue;
                }

                $descendants[] = $token['id'];

                // Recursively get children
                $childDescendants = $this->getTokenDescendants($token['id'], $tokens, $stopAtPredicates);
                $descendants = array_merge($descendants, $childDescendants);
            }
        }

        return $descendants;
    }

    /**
     * Detect multiword expressions and mark for joining with ^
     */
    private function detectMultiwordExpressions(array &$tokens): void
    {
        $mweRelations = ['fixed', 'flat', 'flat:name', 'compound'];
        $mweGroups = [];
        $groupId = 0;

        // Find all MWE tokens
        foreach ($tokens as $token) {
            if (in_array($token['rel'], $mweRelations)) {
                $headId = $token['parent'];

                // Create or find MWE group
                $found = false;
                foreach ($mweGroups as $gid => $group) {
                    if (in_array($headId, $group) || in_array($token['id'], $group)) {
                        $mweGroups[$gid][] = $token['id'];
                        $mweGroups[$gid][] = $headId;
                        $mweGroups[$gid] = array_unique($mweGroups[$gid]);
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $groupId++;
                    $mweGroups[$groupId] = [$headId, $token['id']];
                }
            }
        }

        // Mark tokens in MWE groups
        foreach ($mweGroups as $gid => $tokenIds) {
            sort($tokenIds);
            foreach ($tokenIds as $idx => $tokenId) {
                $tokens[$tokenId]['mwe_group'] = $gid;
                $tokens[$tokenId]['join_with_next'] = ($idx < count($tokenIds) - 1);

                if ($idx < count($tokenIds) - 1) {
                    $this->stats['boundaries_inserted']['mwe']++;
                }
            }
        }
    }

    /**
     * Detect phrase boundaries within clauses
     */
    private function detectPhraseBoundaries(array &$tokens, array $clauses): void
    {
        foreach ($clauses as $clause) {
            $clauseTokens = array_filter($tokens, function($t) use ($clause) {
                return in_array($t['id'], $clause['token_ids']);
            });

            // Sort by token ID
            usort($clauseTokens, fn($a, $b) => $a['id'] <=> $b['id']);

            // Identify phrase boundaries
            foreach ($clauseTokens as $idx => $token) {
                $tokenId = $token['id'];
                $nextIdx = $idx + 1;

                // Skip if this is the last token in clause
                if ($nextIdx >= count($clauseTokens)) {
                    continue;
                }

                $nextToken = $clauseTokens[$nextIdx];

                // Don't add boundary if already marked with MWE join
                if ($tokens[$tokenId]['join_with_next'] ?? false) {
                    continue;
                }

                // Check if we should add a phrase boundary
                if ($this->shouldAddPhraseBoundary($token, $nextToken, $tokens, $clause)) {
                    $tokens[$tokenId]['boundary_after'] = '+';
                    $this->stats['boundaries_inserted']['phrase']++;
                }
            }
        }
    }

    /**
     * Determine if a phrase boundary should be added between two tokens
     */
    private function shouldAddPhraseBoundary(array $token, array $nextToken, array $tokens, array $clause): bool
    {
        // Skip punctuation
        if ($token['pos'] === 'PUNCT' || $nextToken['pos'] === 'PUNCT') {
            return false;
        }

        // Different dependency relations to clause root suggest phrase boundary
        $tokenHead = $this->findPhraseHead($token, $tokens, $clause);
        $nextTokenHead = $this->findPhraseHead($nextToken, $tokens, $clause);

        if ($tokenHead !== $nextTokenHead) {
            return true;
        }

        // Subject/object boundaries
        if (in_array($token['rel'], ['nsubj', 'obj', 'iobj']) &&
            !in_array($nextToken['rel'], ['case', 'det', 'amod', 'nummod'])) {
            return true;
        }

        // After prepositional phrases
        if ($token['pos'] === 'ADP' && !empty($token['children'])) {
            return true;
        }

        // After complete nominal phrases
        if (in_array($token['pos'], ['NOUN', 'PROPN', 'PRON']) &&
            $nextToken['parent'] !== $token['id'] &&
            !in_array($nextToken['rel'], ['case', 'mark'])) {
            return true;
        }

        // After complete verbal phrases
        if (in_array($token['pos'], ['VERB', 'AUX']) &&
            $nextToken['rel'] !== 'aux' &&
            !in_array($nextToken['rel'], ['xcomp', 'ccomp'])) {
            return true;
        }

        // Before coordinating conjunctions
        if ($nextToken['rel'] === 'cc') {
            return true;
        }

        return false;
    }

    /**
     * Find the phrase head for a token
     */
    private function findPhraseHead(array $token, array $tokens, array $clause): int
    {
        // Traverse up to find phrase head (stopping at clause root)
        $current = $token;
        $clauseRoot = $clause['root_token_id'];

        while ($current['parent'] > 0 && $current['parent'] !== $clauseRoot) {
            $parent = $tokens[$current['parent']];

            // Stop at certain relations that indicate phrase boundary
            if (in_array($current['rel'], ['nsubj', 'obj', 'iobj', 'obl', 'advmod'])) {
                return $current['id'];
            }

            $current = $parent;
        }

        return $current['id'];
    }

    /**
     * Detect clause boundaries
     */
    private function detectClauseBoundaries(array &$tokens, array $clauses): void
    {
        // Sort clauses by start position
        usort($clauses, fn($a, $b) => $a['span'][0] <=> $b['span'][0]);

        foreach ($clauses as $idx => $clause) {
            // Find last non-punctuation token in clause
            $lastTokenId = $this->findLastNonPunctToken($clause, $tokens);

            if ($lastTokenId === null) {
                continue;
            }

            // Check if this is the last clause
            $isLastClause = ($idx === count($clauses) - 1);

            if ($isLastClause) {
                // Last clause gets sentence boundary
                $tokens[$lastTokenId]['boundary_after'] = '.';
                $this->stats['boundaries_inserted']['sentence']++;
            } else {
                // Check if next clause is embedded (will be handled by interruption detection)
                $nextClause = $clauses[$idx + 1];
                if (!$this->isClauseEmbedded($nextClause, $clause, $tokens)) {
                    $tokens[$lastTokenId]['boundary_after'] = '#';
                    $this->stats['boundaries_inserted']['clause']++;
                }
            }
        }
    }

    /**
     * Find last non-punctuation token in clause
     */
    private function findLastNonPunctToken(array $clause, array $tokens): ?int
    {
        $clauseTokens = array_filter($tokens, function($t) use ($clause) {
            return in_array($t['id'], $clause['token_ids']) && $t['pos'] !== 'PUNCT';
        });

        if (empty($clauseTokens)) {
            return null;
        }

        usort($clauseTokens, fn($a, $b) => $b['id'] <=> $a['id']);
        return $clauseTokens[0]['id'];
    }

    /**
     * Check if a clause is embedded within another
     */
    private function isClauseEmbedded(array $childClause, array $parentClause, array $tokens): bool
    {
        $childMin = $childClause['span'][0];
        $childMax = $childClause['span'][1];
        $parentMin = $parentClause['span'][0];
        $parentMax = $parentClause['span'][1];

        // Check if child clause interrupts parent
        if ($childMin > $parentMin && $childMax < $parentMax) {
            // Child is inside parent's span - check if it splits parent's tokens
            $parentTokensBefore = array_filter($parentClause['token_ids'], fn($id) => $id < $childMin);
            $parentTokensAfter = array_filter($parentClause['token_ids'], fn($id) => $id > $childMax);

            if (!empty($parentTokensBefore) && !empty($parentTokensAfter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect interrupting/center-embedded clauses
     */
    private function detectInterruptions(array &$tokens, array $clauses): void
    {
        foreach ($clauses as $clause) {
            if ($clause['is_main']) {
                continue; // Only subordinate clauses can interrupt
            }

            // Only mark relative clauses as potential interruptions
            $rootToken = $tokens[$clause['root_token_id']];
            if (!in_array($rootToken['rel'], ['acl:relcl', 'acl'])) {
                continue;
            }

            // Find parent clause
            $parentClause = null;
            foreach ($clauses as $c) {
                if ($c['id'] === $clause['parent_clause_id']) {
                    $parentClause = $c;
                    break;
                }
            }

            if (!$parentClause) {
                continue;
            }

            // Check if this clause interrupts its parent
            if ($this->isClauseEmbedded($clause, $parentClause, $tokens)) {
                // Mark first token with interruption start
                $firstTokenId = min($clause['token_ids']);
                $lastTokenId = max($clause['token_ids']);

                $tokens[$firstTokenId]['interruption_start'] = true;
                $tokens[$lastTokenId]['interruption_end'] = true;

                // Remove any clause boundary marker
                if (($tokens[$lastTokenId]['boundary_after'] ?? null) === '#') {
                    $tokens[$lastTokenId]['boundary_after'] = null;
                    $this->stats['boundaries_inserted']['clause']--;
                }

                $this->stats['boundaries_inserted']['interruption']++;
            }
        }
    }

    /**
     * Construct the annotated sentence string
     */
    private function constructAnnotatedSentence(array $tokens): string
    {
        // Sort tokens by ID
        ksort($tokens);

        $parts = [];

        foreach ($tokens as $token) {
            // Skip punctuation except for our boundary markers
            if ($token['pos'] === 'PUNCT') {
                continue;
            }

            // Add interruption start marker
            if ($token['interruption_start'] ?? false) {
                $parts[] = '{';
            }

            // Add the word
            $parts[] = $token['word'];

            // Add interruption end marker
            if ($token['interruption_end'] ?? false) {
                $parts[] = '}';
            }

            // Add boundary marker or join character
            if ($token['join_with_next'] ?? false) {
                $parts[] = '^';
            } elseif (isset($token['boundary_after'])) {
                $parts[] = $token['boundary_after'];
            }
        }

        // Build final string
        $result = '';
        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];
            $result .= $part;

            // Add space after word (but not after ^, +, #, ., {, })
            if ($i < count($parts) - 1) {
                $nextPart = $parts[$i + 1];

                // Don't add space if next part is a marker or if current part is { or ^
                if (!in_array($nextPart, ['^', '+', '#', '.', '}']) &&
                    !in_array($part, ['{', '^'])) {
                    $result .= ' ';
                }
            }
        }

        // Clean up multiple spaces
        $result = preg_replace('/\s+/', ' ', $result);
        $result = trim($result);

        return $result;
    }

    /**
     * Parse input file and return array of sentences
     */
    private function parseInputFile(string $filePath): array
    {
        $sentences = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if (!empty($line)) {
                $sentences[] = [
                    'line' => $lineNum + 1,
                    'text' => $line,
                ];
            }
        }

        return $sentences;
    }

    /**
     * Validate Trankit service configuration
     */
    private function validateConfiguration(): bool
    {
        try {
            $testSentence = 'Teste.';
            $result = $this->trankit->getUDTrankit($testSentence, 1);

            if (empty($result->udpipe)) {
                throw new Exception('Empty parse result for test sentence');
            }

            return true;
        } catch (Exception $e) {
            $this->error('Cannot connect to Trankit service: ' . $e->getMessage());
            $this->line('URL: ' . config('udparser.trankit_url'));
            $this->line('Check if Trankit service is running');
            return false;
        }
    }

    /**
     * Write results to output file
     */
    private function writeOutputFile(string $outputPath, array $results): void
    {
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $content = implode("\n", $results) . "\n";
        file_put_contents($outputPath, $content);
    }

    /**
     * Display configuration at start
     */
    private function displayConfiguration(string $inputPath, string $outputPath): void
    {
        $this->info('Flat Syntax Stage 1 Annotation');
        $this->line(str_repeat('-', 70));
        $this->line('Configuration:');
        $this->line("  Input:  {$inputPath}");
        $this->line("  Output: {$outputPath}");
        $this->line('  Language: Portuguese (1)');
        $this->line('  Trankit: ' . config('udparser.trankit_url'));

        if ($this->isDryRun) {
            $this->warn('  Mode: DRY RUN - No output file will be written');
        }

        if ($this->debugMode) {
            $this->warn('  Debug: ENABLED - Detailed parse information will be shown');
        }

        $this->newLine();
    }

    /**
     * Display summary statistics
     */
    private function displaySummary(): void
    {
        $this->newLine();
        $this->info('Processing Summary:');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Sentences', $this->stats['total_sentences']],
                ['Successfully Processed', $this->stats['sentences_processed']],
                ['Failed to Parse', $this->stats['sentences_failed']],
            ]
        );

        $this->newLine();
        $this->info('Boundary Markers Inserted:');
        $this->table(
            ['Marker Type', 'Count'],
            [
                ['Phrase (+)', $this->stats['boundaries_inserted']['phrase']],
                ['Clause (#)', $this->stats['boundaries_inserted']['clause']],
                ['Sentence (.)', $this->stats['boundaries_inserted']['sentence']],
                ['Interruption ({})', $this->stats['boundaries_inserted']['interruption']],
                ['MWE (^)', $this->stats['boundaries_inserted']['mwe']],
            ]
        );

        if ($this->isDryRun) {
            $this->newLine();
            $this->warn('DRY RUN - No output file was written');
            $this->info('Run without --dry-run to save results');
        }
    }

    /**
     * Debug print UD tree for a sentence
     */
    private function debugPrintSentence(int $lineNum, string $sentence, array $tokens): void
    {
        $this->newLine();
        $this->line(str_repeat('=', 70));
        $this->line("SENTENCE {$lineNum}: {$sentence}");
        $this->line(str_repeat('-', 70));
        $this->newLine();

        $this->line('UD PARSE:');
        $this->table(
            ['ID', 'Word', 'POS', 'Rel', 'Parent'],
            array_map(fn($t) => [
                $t['id'],
                $t['word'],
                $t['pos'],
                $t['rel'],
                $t['parent'],
            ], $tokens)
        );

        $this->newLine();
    }
}
