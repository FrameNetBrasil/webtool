<?php

namespace App\Console\Commands\OpenAI;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CorrectImportedTranslationsCommand extends Command
{
    protected $signature = 'openai:correct-imported-translations
                            {--input= : Input CSV file path (default: app/Console/Commands/FN3/Data/lus_imported_from_english_merged.csv)}
                            {--output= : Output CSV file path (default: app/Console/Commands/FN3/Data/lus_imported_from_english_corrected.csv)}
                            {--batch-size=100 : Number of rows to process per run}
                            {--resume : Resume from last processed row}
                            {--dry-run : Show what would be corrected without saving}
                            {--model=gpt-4o : OpenAI model to use}';

    protected $description = 'Evaluate and correct Portuguese translations of imported lexical units using OpenAI';

    /** @var array<int, string> */
    private array $posMap = [
        83 => 'NOUN',
        86 => 'VERB',
        84 => 'ADJ',
        89 => 'ADV',
    ];

    private string $progressFile;

    public function handle(): int
    {
        $inputPath = $this->option('input')
            ?? app_path('Console/Commands/FN3/Data/lus_imported_from_english_merged.csv');
        $outputPath = $this->option('output')
            ?? app_path('Console/Commands/FN3/Data/lus_imported_from_english_corrected.csv');
        $batchSize = (int) $this->option('batch-size');
        $resume = $this->option('resume');
        $dryRun = $this->option('dry-run');
        $model = $this->option('model');

        $this->progressFile = dirname($outputPath).'/imported_correction_progress.json';

        $apiKey = env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            $this->error('OPENAI_API_KEY is not set in .env file');

            return 1;
        }

        if (! file_exists($inputPath)) {
            $this->error("Input file not found: {$inputPath}");

            return 1;
        }

        $this->info("Input: {$inputPath}");
        $this->info("Output: {$outputPath}");
        $this->info("Model: {$model}");
        $this->info("Batch size: {$batchSize}");
        $this->newLine();

        try {
            $rows = $this->readCsv($inputPath);
            $totalRows = count($rows);
            $this->info("Total rows: {$totalRows}");

            $startIndex = 0;
            $processedData = [];

            if ($resume && file_exists($this->progressFile)) {
                $progress = json_decode(file_get_contents($this->progressFile), true);
                $startIndex = $progress['lastProcessedIndex'] ?? 0;
                $processedData = $progress['processedData'] ?? [];
                $this->info("Resuming from row {$startIndex}");
            }

            $endIndex = min($startIndex + $batchSize, $totalRows);
            $this->info("Processing rows {$startIndex} to ".($endIndex - 1));
            $this->newLine();

            $bar = $this->output->createProgressBar($endIndex - $startIndex);
            $bar->start();

            $corrections = 0;
            $errors = 0;

            for ($i = $startIndex; $i < $endIndex; $i++) {
                $row = $rows[$i];

                try {
                    $result = $this->evaluateTranslation($row, $model, $apiKey);

                    if ($result['corrected']) {
                        $corrections++;
                        $processedData[$i] = [
                            'original' => $row,
                            'corrected_lemma' => $result['corrected_lemma'],
                            'reason' => $result['reason'],
                        ];

                        if ($dryRun) {
                            $this->newLine();
                            $this->warn("Row {$i}: {$row['lemmaName(en)']} [{$row['frameName']}]");
                            $this->line("  Original: {$row['lemmaName(pt)']}");
                            $this->line("  Corrected: {$result['corrected_lemma']}");
                            $this->line("  Reason: {$result['reason']}");
                        }
                    } else {
                        $processedData[$i] = [
                            'original' => $row,
                            'corrected_lemma' => $row['lemmaName(pt)'],
                            'reason' => 'No correction needed',
                        ];
                    }
                } catch (Exception $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Error processing row {$i}: ".$e->getMessage());

                    $processedData[$i] = [
                        'original' => $row,
                        'corrected_lemma' => $row['lemmaName(pt)'],
                        'reason' => 'Error: '.$e->getMessage(),
                    ];
                }

                $bar->advance();

                // Rate limiting - wait between requests
                usleep(200000); // 200ms delay
            }

            $bar->finish();
            $this->newLine(2);

            // Save progress
            $this->saveProgress($endIndex, $processedData);

            // Generate output CSV if not dry run
            if (! $dryRun) {
                $this->generateOutputCsv($outputPath, $rows, $processedData);
                $this->info("Output saved to: {$outputPath}");
            }

            $this->newLine();
            $this->info('Processed: '.($endIndex - $startIndex).' rows');
            $this->info("Corrections made: {$corrections}");
            $this->info("Errors: {$errors}");

            if ($endIndex < $totalRows) {
                $this->newLine();
                $this->warn('More rows remaining. Run with --resume to continue.');
            } else {
                $this->info('All rows processed!');
                // Clean up progress file
                if (file_exists($this->progressFile)) {
                    unlink($this->progressFile);
                }
            }

            return 0;
        } catch (Exception $e) {
            $this->error('Fatal error: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new Exception("Cannot open file: {$path}");
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            throw new Exception('Cannot read CSV headers');
        }

        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? '';
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, string>  $row
     * @return array{corrected: bool, corrected_lemma: string, reason: string}
     */
    private function evaluateTranslation(array $row, string $model, string $apiKey): array
    {
        $lemmaEn = $row['lemmaName(en)'];
        $lemmaPt = $row['lemmaName(pt)'];
        $idPos = (int) $row['idPOS'];
        $frameName = $row['frameName'];
        $senseDescriptionEn = $row['senseDescription(en)'];
        $senseDescriptionPt = $row['senseDescription(pt)'];

        $posName = $this->posMap[$idPos] ?? 'UNKNOWN';

        $prompt = $this->buildPrompt($lemmaEn, $lemmaPt, $posName, $frameName, $senseDescriptionEn, $senseDescriptionPt);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a linguistic expert specializing in English-Portuguese translations for FrameNet lexical units. You must respond ONLY with valid JSON, no additional text.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.3,
            'max_tokens' => 200,
        ]);

        if (! $response->successful()) {
            throw new Exception('API request failed: '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        return $this->parseResponse($content, $lemmaPt);
    }

    private function buildPrompt(
        string $lemmaEn,
        string $lemmaPt,
        string $posName,
        string $frameName,
        string $senseDescriptionEn,
        string $senseDescriptionPt
    ): string {
        return <<<PROMPT
Evaluate this Portuguese translation of an English lexical unit:

English lemma: "{$lemmaEn}"
Current Portuguese translation: "{$lemmaPt}"
Expected Part of Speech: {$posName}
Frame: "{$frameName}"
English sense description: "{$senseDescriptionEn}"
Portuguese sense description: "{$senseDescriptionPt}"

Check if the Portuguese translation is correct by verifying:
1. The word matches the meaning described in the sense descriptions
2. The word has the correct part of speech ({$posName})
3. The translation makes sense within the "{$frameName}" frame context

Respond with JSON only:
{
  "correct": true/false,
  "corrected_lemma": "the corrected Portuguese word if incorrect, or the original if correct",
  "reason": "brief explanation if correction was needed, or 'OK' if correct"
}
PROMPT;
    }

    /**
     * @return array{corrected: bool, corrected_lemma: string, reason: string}
     */
    private function parseResponse(string $content, string $originalLemma): array
    {
        // Clean up potential markdown code blocks
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: {$content}");
        }

        $isCorrect = $data['correct'] ?? true;
        $correctedLemma = $data['corrected_lemma'] ?? $originalLemma;
        $reason = $data['reason'] ?? 'Unknown';

        return [
            'corrected' => ! $isCorrect,
            'corrected_lemma' => $correctedLemma,
            'reason' => $reason,
        ];
    }

    /**
     * @param  array<int, array{original: array<string, string>, corrected_lemma: string, reason: string}>  $processedData
     */
    private function saveProgress(int $lastIndex, array $processedData): void
    {
        $progress = [
            'lastProcessedIndex' => $lastIndex,
            'processedData' => $processedData,
            'timestamp' => now()->toIso8601String(),
        ];

        file_put_contents($this->progressFile, json_encode($progress, JSON_PRETTY_PRINT));
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<int, array{original: array<string, string>, corrected_lemma: string, reason: string}>  $processedData
     */
    private function generateOutputCsv(string $outputPath, array $rows, array $processedData): void
    {
        $handle = fopen($outputPath, 'w');

        if ($handle === false) {
            throw new Exception("Cannot create output file: {$outputPath}");
        }

        // Write header - same structure as input
        $headers = ['idLU', 'lemmaName(en)', 'senseDescription(en)', 'idFrame', 'frameName', 'idPOS', 'lemmaName(pt)', 'senseDescription(pt)', 'idLemma(pt)'];
        fputcsv($handle, $headers);

        // Write rows
        foreach ($rows as $index => $row) {
            $correctedLemma = $processedData[$index]['corrected_lemma'] ?? $row['lemmaName(pt)'];

            fputcsv($handle, [
                $row['idLU'],
                $row['lemmaName(en)'],
                $row['senseDescription(en)'],
                $row['idFrame'],
                $row['frameName'],
                $row['idPOS'],
                $correctedLemma,
                $row['senseDescription(pt)'],
                $row['idLemma(pt)'],
            ]);
        }

        fclose($handle);
    }
}
