<?php

namespace App\Jobs;

use App\Repositories\Parser\TypeGraphRepository;
use App\Services\Parser\TypeGraphBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Rebuild Type Graph Job
 *
 * Asynchronously rebuilds the Type Graph for a grammar after construction changes.
 * This ensures the Type Graph stays synchronized with construction definitions
 * without blocking user operations.
 */
class RebuildTypeGraphJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $idGrammarGraph
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("RebuildTypeGraphJob: Starting rebuild for grammar {$this->idGrammarGraph}");

        $startTime = microtime(true);

        try {
            $builder = app(TypeGraphBuilder::class);
            $repository = app(TypeGraphRepository::class);

            // Build Type Graph
            $typeGraph = $builder->buildForGrammar($this->idGrammarGraph);

            // Save to database (upsert)
            $repository->save($typeGraph);

            $duration = round(microtime(true) - $startTime, 2);

            Log::info("RebuildTypeGraphJob: Completed for grammar {$this->idGrammarGraph} in {$duration}s", [
                'nodes' => count($typeGraph->nodes),
                'edges' => count($typeGraph->edges),
                'mandatoryElements' => count($typeGraph->mandatoryElements),
            ]);
        } catch (\Exception $e) {
            Log::error("RebuildTypeGraphJob: Failed for grammar {$this->idGrammarGraph}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // 30s, 1m, 2m
    }
}
