<?php

namespace App\Services\XmlExport;


/**
 * Export progress tracker
 */
class ExportProgressTracker
{
    private array $progress = [];
    private string $sessionId;

    public function __construct(?string $sessionId = null)
    {
        $this->sessionId = $sessionId ?? uniqid('export_', true);
        $this->progress = [
            'session_id' => $this->sessionId,
            'start_time' => microtime(true),
            'current_step' => '',
            'total_items' => 0,
            'processed_items' => 0,
            'current_item' => '',
            'status' => 'initialized',
            'errors' => [],
            'warnings' => []
        ];
    }

    /**
     * Start a new step
     */
    public function startStep(string $stepName, int $totalItems = 0): void
    {
        $this->progress['current_step'] = $stepName;
        $this->progress['total_items'] = $totalItems;
        $this->progress['processed_items'] = 0;
        $this->progress['status'] = 'running';
    }

    /**
     * Update progress
     */
    public function updateProgress(string $currentItem = '', int $increment = 1): void
    {
        $this->progress['current_item'] = $currentItem;
        $this->progress['processed_items'] += $increment;
    }

    /**
     * Add error
     */
    public function addError(string $error, ?string $context = null): void
    {
        $this->progress['errors'][] = [
            'message' => $error,
            'context' => $context,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Add warning
     */
    public function addWarning(string $warning, ?string $context = null): void
    {
        $this->progress['warnings'][] = [
            'message' => $warning,
            'context' => $context,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Mark as completed
     */
    public function complete(): void
    {
        $this->progress['status'] = 'completed';
        $this->progress['end_time'] = microtime(true);
        $this->progress['duration'] = $this->progress['end_time'] - $this->progress['start_time'];
    }

    /**
     * Mark as failed
     */
    public function fail(string $reason): void
    {
        $this->progress['status'] = 'failed';
        $this->progress['failure_reason'] = $reason;
        $this->progress['end_time'] = microtime(true);
        $this->progress['duration'] = $this->progress['end_time'] - $this->progress['start_time'];
    }

    /**
     * Get current progress
     */
    public function getProgress(): array
    {
        $progress = $this->progress;

        // Calculate percentage
        if ($progress['total_items'] > 0) {
            $progress['percentage'] = round(($progress['processed_items'] / $progress['total_items']) * 100, 2);
        } else {
            $progress['percentage'] = 0;
        }

        // Calculate ETA
        if ($progress['processed_items'] > 0 && isset($progress['start_time'])) {
            $elapsed = microtime(true) - $progress['start_time'];
            $rate = $progress['processed_items'] / $elapsed;
            $remaining = $progress['total_items'] - $progress['processed_items'];
            $progress['eta_seconds'] = $remaining > 0 ? round($remaining / $rate) : 0;
        }

        return $progress;
    }

    /**
     * Get session ID
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }
}

