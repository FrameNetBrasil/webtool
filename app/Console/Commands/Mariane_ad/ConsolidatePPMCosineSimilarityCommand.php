<?php

namespace App\Console\Commands\Mariane_ad;

use Illuminate\Console\Command;

class ConsolidatePPMCosineSimilarityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cosine:consolidate-ppm
                            {--prefix=* : PPM prefix to process (e.g., PPM_1, PPM_7). If not specified, processes all found prefixes}
                            {--output-dir= : Output directory for consolidated CSV files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consolidate cosine similarity CSV files (COM/SEM/VIDEO) for PPM datasets';

    private array $stats = [
        'processed' => 0,
        'total_rows' => 0,
        'mismatches' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📊 PPM Cosine Similarity Consolidation');
        $this->newLine();

        $baseDir = app_path('Console/Commands/Mariane_ad');
        $outputDir = $this->option('output-dir') ?? $baseDir;

        // Get prefixes to process
        $prefixes = $this->option('prefix');
        if (empty($prefixes)) {
            $prefixes = $this->discoverPrefixes($baseDir);
        }

        if (empty($prefixes)) {
            $this->error('❌ No PPM prefixes found to process');

            return self::FAILURE;
        }

        $this->info('📄 Found '.count($prefixes).' prefix(es) to process: '.implode(', ', $prefixes));
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($prefixes));
        $progressBar->start();

        foreach ($prefixes as $prefix) {
            $this->processPrefix($prefix, $baseDir, $outputDir);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        // Display statistics
        $this->displayStatistics();

        return self::SUCCESS;
    }

    /**
     * Discover available PPM prefixes in the directory
     */
    private function discoverPrefixes(string $baseDir): array
    {
        $prefixes = [];
        $files = glob("{$baseDir}/PPM_*_COM_SEM.csv");

        foreach ($files as $file) {
            $basename = basename($file);
            // Extract prefix from filename like "PPM_1_COM_SEM.csv" -> "PPM_1"
            if (preg_match('/^(PPM_\d+)_COM_SEM\.csv$/', $basename, $matches)) {
                $prefixes[] = $matches[1];
            }
        }

        return $prefixes;
    }

    /**
     * Process a single PPM prefix
     */
    private function processPrefix(string $prefix, string $baseDir, string $outputDir): void
    {
        $comSemPath = "{$baseDir}/{$prefix}_COM_SEM.csv";
        $comVideoPath = "{$baseDir}/{$prefix}_COM_VIDEO.csv";
        $semVideoPath = "{$baseDir}/{$prefix}_SEM_VIDEO.csv";

        // Check if all three files exist
        if (! file_exists($comSemPath) || ! file_exists($comVideoPath) || ! file_exists($semVideoPath)) {
            if ($this->option('verbose')) {
                $this->line("⚠️  Missing cosine files for prefix: {$prefix}");
            }

            return;
        }

        // Read all three CSV files
        $comSemData = $this->readCosineCSV($comSemPath);
        $comVideoData = $this->readCosineCSV($comVideoPath);
        $semVideoData = $this->readCosineCSV($semVideoPath);

        // Consolidate data by matching video IDs
        $consolidated = $this->consolidateData($comSemData, $comVideoData, $semVideoData);

        // Export consolidated CSV
        $outputPath = "{$outputDir}/Consolidated_{$prefix}.csv";
        $this->exportConsolidatedCSV($consolidated, $outputPath);

        $this->stats['processed']++;
        $this->stats['total_rows'] += count($consolidated);

        if ($this->option('verbose')) {
            $this->line("✓ {$prefix}: ".count($consolidated).' consolidated rows');
        }
    }

    /**
     * Read cosine similarity CSV file
     */
    private function readCosineCSV(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return $data;
        }

        // Skip header
        fgets($handle);

        while (($line = fgets($handle)) !== false) {
            $row = str_getcsv($line);
            if (count($row) >= 2) {
                $video = trim($row[0]);
                $data[$video] = [
                    'video' => $video,
                    'cosine' => $row[1],
                ];
            }
        }

        fclose($handle);

        return $data;
    }

    /**
     * Consolidate data from three sources by matching video IDs
     */
    private function consolidateData(array $comSem, array $comVideo, array $semVideo): array
    {
        $consolidated = [];

        // Get all unique video IDs
        $allVideos = array_unique(array_merge(
            array_keys($comSem),
            array_keys($comVideo),
            array_keys($semVideo)
        ));

        sort($allVideos); // Sort video IDs for consistent output

        foreach ($allVideos as $videoId) {
            $comSemRow = $comSem[$videoId] ?? null;
            $comVideoRow = $comVideo[$videoId] ?? null;
            $semVideoRow = $semVideo[$videoId] ?? null;

            if ($videoId === null || $videoId === '') {
                $this->stats['mismatches']++;

                continue;
            }

            $consolidated[] = [
                'video' => $videoId,
                'cosine_COM_SEM' => $comSemRow['cosine'] ?? null,
                'cosine_COM_VIDEO' => $comVideoRow['cosine'] ?? null,
                'cosine_SEM_VIDEO' => $semVideoRow['cosine'] ?? null,
            ];
        }

        return $consolidated;
    }

    /**
     * Export consolidated data to CSV
     */
    private function exportConsolidatedCSV(array $data, string $outputPath): void
    {
        $handle = fopen($outputPath, 'w');

        // Write header
        $header = [
            'video',
            'cosine_COM_SEM',
            'cosine_COM_VIDEO',
            'cosine_SEM_VIDEO',
        ];
        fputcsv($handle, $header);

        // Write data
        foreach ($data as $row) {
            fputcsv($handle, [
                $row['video'],
                $row['cosine_COM_SEM'] ?? '',
                $row['cosine_COM_VIDEO'] ?? '',
                $row['cosine_SEM_VIDEO'] ?? '',
            ]);
        }

        fclose($handle);
    }

    /**
     * Display statistics
     */
    private function displayStatistics(): void
    {
        $this->info('=== Statistics ===');
        $this->newLine();

        $avgRows = $this->stats['processed'] > 0
            ? round($this->stats['total_rows'] / $this->stats['processed'], 2)
            : 0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Prefixes Processed', $this->stats['processed']],
                ['Total Consolidated Rows', $this->stats['total_rows']],
                ['Avg Rows per Prefix', $avgRows],
                ['Mismatches/Missing IDs', $this->stats['mismatches']],
            ]
        );
    }
}
