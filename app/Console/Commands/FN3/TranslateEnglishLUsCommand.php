<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TranslateEnglishLUsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:translate-english-lus
                            {--model= : Ollama model to use (default: from .env OLLAMA_DEFAULT_MODEL)}
                            {--base-url= : Ollama API base URL (default: from .env OLLAMA_BASE_URL)}
                            {--limit= : Limit number of LUs to process (for testing)}
                            {--offset=0 : Starting offset}
                            {--dry-run : Preview without writing CSV}
                            {--batch-size=10 : Number of LUs to translate before saving batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate English LUs to Portuguese using Ollama and export non-existing translations to CSV';

    /**
     * Statistics tracking
     */
    private array $stats = [
        'total' => 0,
        'translated_with_lemma' => 0,
        'translated_without_lemma' => 0,
        'skipped_exists' => 0,
        'errors' => 0,
    ];

    /**
     * CSV data buffer - with idLemma found
     */
    private array $csvDataWithLemma = [];

    /**
     * CSV data buffer - without idLemma (needs review)
     */
    private array $csvDataWithoutLemma = [];

    /**
     * Counter for incremental CSV saves
     */
    private int $processedCount = 0;

    /**
     * CSV file counter for chunked exports
     */
    private int $csvFileCounter = 1;

    /**
     * Ollama configuration
     */
    private string $baseUrl;

    private string $model;

    private int $timeout;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting English LU Translation to Portuguese...');
        $this->newLine();

        // Initialize Ollama configuration
        $this->baseUrl = $this->option('base-url') ?? env('OLLAMA_BASE_URL', 'http://localhost:11434');
        $this->model = $this->option('model') ?? env('OLLAMA_DEFAULT_MODEL', 'llama3.1:8b');
        $this->timeout = (int) env('OLLAMA_TIMEOUT', 60);

        $limit = $this->option('limit');
        $offset = (int) $this->option('offset');
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        $this->info("Base URL: {$this->baseUrl}");
        $this->info("Using model: {$this->model}");
        $this->info("Timeout: {$this->timeout}s");
        $this->info("Batch size: {$batchSize}");
        if ($limit) {
            $this->info("Limit: {$limit}");
        }
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No CSV will be written');
        }
        $this->newLine();

        try {
            // Test Ollama connection
            $this->testOllamaConnection();

            // Get English LUs
            $query = Criteria::table('view_lu')
                ->where('idLanguage', 2) // English
                ->select('idLU', 'lemmaName', 'senseDescription', 'idFrame', 'frameName', 'idPOS');

            if ($limit) {
                $query->limit($limit);
                if ($offset > 0) {
                    $query->offset($offset);
                }
            } elseif ($offset > 0) {
                // If offset is specified without limit, use a very large limit
                $query->limit(999999)->offset($offset);
            }

            $englishLUs = $query->get();
            $this->stats['total'] = $englishLUs->count();

            if ($this->stats['total'] === 0) {
                $this->warn('⚠️  No English LUs found to process');

                return 0;
            }

            $this->info("📚 Processing {$this->stats['total']} English LUs...");
            $this->newLine();

            // Process LUs with progress bar
            $this->withProgressBar($englishLUs, function ($lu) use ($isDryRun) {
                $this->processLU($lu);
                $this->processedCount++;

                // Save incremental CSV every 100 records
                if (! $isDryRun && $this->processedCount % 100 === 0) {
                    $this->saveIncrementalCsv();
                }
            });

            $this->newLine(2);

            // Write final CSV files if not dry run (any remaining data)
            if (! $isDryRun) {
                $this->saveIncrementalCsv(); // Save any remaining data
            }

            // Display statistics
            $this->displayStatistics();

            $this->info('✅ Translation process completed successfully!');

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return 1;
        }
    }

    /**
     * Test Ollama server connection
     */
    private function testOllamaConnection(): void
    {
        try {
            $response = Http::timeout($this->timeout)->get("{$this->baseUrl}/api/version");

            if ($response->successful()) {
                $version = $response->json('version') ?? 'unknown';
                $this->line("✅ Ollama server is running (version: {$version})");
                $this->newLine();
            } else {
                throw new Exception("Server returned status {$response->status()}");
            }
        } catch (Exception $e) {
            $this->error("❌ Cannot connect to Ollama server at {$this->baseUrl}");
            $this->line('Make sure Ollama is running with: ollama serve');
            throw $e;
        }
    }

    /**
     * Process a single LU
     */
    private function processLU(object $lu): void
    {
        try {
            // Translate lemmaName (e.g., "cause" -> "causar")
            // Handle null values by providing empty string
            $translatedLemma = $this->translateText($lu->lemmaName ?? '');

            // Translate senseDescription
            // Handle null values by providing empty string
            $translatedDescription = $this->translateText($lu->senseDescription ?? '');

            // Check if Portuguese LU already exists for this frame with the translated lemma and same POS
            $existsCount = Criteria::table('view_lu')
                ->where('idLanguage', 1) // Portuguese
                ->where('lemmaName', $translatedLemma)
                ->where('idPOS', $lu->idPOS) // Same POS
                ->where('idFrame', $lu->idFrame)
                ->count();

            if ($existsCount > 0) {
                // Portuguese LU already exists
                $this->stats['skipped_exists']++;
            } else {
                // Try to find Portuguese lemma with the translated name and same POS
                $portugueseLemma = Criteria::table('view_lu')
                    ->where('idLanguage', 1)
                    ->where('lemmaName', $translatedLemma)
                    ->where('idPOS', $lu->idPOS)
                    ->select('idLemma')
                    ->first();

                $idLemmaPt = $portugueseLemma->idLemma ?? null;

                // Fallback: If idPOS=86 (verb) and no lemma found, try idPOS=83 (noun)
                if ($idLemmaPt === null && $lu->idPOS == 86) {
                    $portugueseLemmaNoun = Criteria::table('view_lu')
                        ->where('idLanguage', 1)
                        ->where('lemmaName', $translatedLemma)
                        ->where('idPOS', 83) // noun
                        ->select('idLemma')
                        ->first();

                    $idLemmaPt = $portugueseLemmaNoun->idLemma ?? null;
                }

                // Prepare row data
                $rowData = [
                    'idLU' => $lu->idLU,
                    'lemmaName_en' => $lu->lemmaName,
                    'senseDescription_en' => $lu->senseDescription,
                    'idFrame' => $lu->idFrame,
                    'frameName' => $lu->frameName,
                    'idPOS' => $lu->idPOS,
                    'lemmaName_pt' => $translatedLemma,
                    'senseDescription_pt' => $translatedDescription,
                    'idLemma_pt' => $idLemmaPt,
                ];

                // Split into two arrays based on whether idLemma was found
                if ($idLemmaPt !== null) {
                    $this->csvDataWithLemma[] = $rowData;
                    $this->stats['translated_with_lemma']++;
                } else {
                    $this->csvDataWithoutLemma[] = $rowData;
                    $this->stats['translated_without_lemma']++;
                }
            }

        } catch (Exception $e) {
            $this->stats['errors']++;
            // Optionally log the error (silently during progress bar)
        }
    }

    /**
     * Translate text from English to Portuguese using Ollama
     */
    private function translateText(string $text): string
    {
        if (empty(trim($text))) {
            return '';
        }

        try {
            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/chat", [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional English to Portuguese translator specializing in linguistic terminology. Translate the following text from English to Portuguese. Return ONLY the Portuguese translation without any explanations, notes, or additional text.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Translate to Portuguese: {$text}",
                    ],
                ],
                'stream' => false,
                'options' => [
                    'temperature' => 0.3, // Low temperature for consistent translation
                ],
            ]);

            if (! $response->successful()) {
                throw new Exception("Translation API failed: {$response->body()}");
            }

            $data = $response->json();
            $translation = $data['message']['content'] ?? '';

            // Clean up the translation (remove quotes, extra whitespace)
            $translation = trim($translation, " \t\n\r\0\x0B\"'");

            return $translation;

        } catch (Exception $e) {
            throw new Exception("Failed to translate '{$text}': ".$e->getMessage());
        }
    }

    /**
     * Write CSV data to file
     */
    private function writeCsv(array $csvData, string $type, ?int $chunkNumber = null): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $chunkSuffix = $chunkNumber ? "_chunk{$chunkNumber}" : '';
        $filename = "lus_imported_from_english_{$type}{$chunkSuffix}_{$timestamp}.csv";
        $directory = 'exports';
        $fullPath = "{$directory}/{$filename}";

        // Ensure directory exists
        Storage::makeDirectory($directory);

        // Get the full system path
        $systemPath = Storage::path($fullPath);

        // Open file for writing
        $handle = fopen($systemPath, 'w');

        if (! $handle) {
            throw new Exception("Failed to create CSV file at {$systemPath}");
        }

        // Write header
        fputcsv($handle, [
            'idLU',
            'lemmaName(en)',
            'senseDescription(en)',
            'idFrame',
            'frameName',
            'idPOS',
            'lemmaName(pt)',
            'senseDescription(pt)',
            'idLemma(pt)',
        ]);

        // Write data rows
        foreach ($csvData as $row) {
            fputcsv($handle, [
                $row['idLU'],
                $row['lemmaName_en'],
                $row['senseDescription_en'],
                $row['idFrame'],
                $row['frameName'],
                $row['idPOS'],
                $row['lemmaName_pt'],
                $row['senseDescription_pt'],
                $row['idLemma_pt'] ?? '',
            ]);
        }

        fclose($handle);

        return $fullPath;
    }

    /**
     * Save incremental CSV files
     */
    private function saveIncrementalCsv(): void
    {
        if (! empty($this->csvDataWithLemma)) {
            $csvPathWithLemma = $this->writeCsv($this->csvDataWithLemma, 'with_lemma', $this->csvFileCounter);
            // Clear the buffer after saving
            $this->csvDataWithLemma = [];
        }

        if (! empty($this->csvDataWithoutLemma)) {
            $csvPathWithoutLemma = $this->writeCsv($this->csvDataWithoutLemma, 'needs_review', $this->csvFileCounter);
            // Clear the buffer after saving
            $this->csvDataWithoutLemma = [];
        }

        // Increment file counter for next batch
        $this->csvFileCounter++;
    }

    /**
     * Display statistics table
     */
    private function displayStatistics(): void
    {
        $this->info('📊 Translation Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total English LUs Processed', number_format($this->stats['total'])],
                ['Translated (with idLemma) ✅', number_format($this->stats['translated_with_lemma'])],
                ['Translated (needs review) ⚠️', number_format($this->stats['translated_without_lemma'])],
                ['Skipped (Already Exists)', number_format($this->stats['skipped_exists'])],
                ['Errors', number_format($this->stats['errors'])],
            ]
        );
        $this->newLine();
    }
}
