<?php

namespace App\Services\CLN;

use App\Models\CLN\Binding;
use App\Models\CLN\FunctionalColumn;
use App\Models\CLN\PendingConstruction;

/**
 * Result of parsing a single word.
 */
class WordParseResult {
    public string $word;
    public int $time;
    /** @var FunctionalColumn[] */
    public array $activatedColumns = [];
    /** @var Binding[] */
    public array $newBindings = [];
    /** @var PendingConstruction[] */
    public array $newPendingConstructions = [];
    /** @var string[] */
    public array $events = [];            // Human-readable event log
}
