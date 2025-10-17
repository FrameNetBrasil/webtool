<?php

namespace App\Services\XmlExport;

/**
 * Batch export manager for handling large datasets
 */
class BatchExportManager
{
    private int $batchSize;
    private string $outputDir;
    private array $statistics;

    public function __construct(int $batchSize = 100, string $outputDir = 'exports')
    {
        $this->batchSize = $batchSize;
        $this->outputDir = $outputDir;
        $this->statistics = [
            'processed' => 0,
            'errors' => 0,
            'files_created' => 0,
            'start_time' => time()
        ];
    }

    /**
     * Process items in batches
     */
    public function processBatch(array $items, callable $processor): array
    {
        $batches = array_chunk($items, $this->batchSize);
        $results = [];

        foreach ($batches as $batchIndex => $batch) {
            $batchResults = [];

            foreach ($batch as $item) {
                try {
                    $result = $processor($item);
                    $batchResults[] = $result;
                    $this->statistics['processed']++;

                    if ($result['success'] ?? false) {
                        $this->statistics['files_created']++;
                    }
                } catch (\Exception $e) {
                    $this->statistics['errors']++;
                    $batchResults[] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'item_id' => $item->id ?? 'unknown'
                    ];
                }
            }

            $results["batch_{$batchIndex}"] = $batchResults;

            // Optional: Add memory cleanup
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return $results;
    }

    /**
     * Get export statistics
     */
    public function getStatistics(): array
    {
        $this->statistics['duration'] = time() - $this->statistics['start_time'];
        return $this->statistics;
    }

    /**
     * Reset statistics
     */
    public function resetStatistics(): void
    {
        $this->statistics = [
            'processed' => 0,
            'errors' => 0,
            'files_created' => 0,
            'start_time' => time()
        ];
    }
}
