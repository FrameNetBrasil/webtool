<?php

namespace App\Console\Commands\Ollama;

use App\Database\Criteria;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OllamaClassifyFramesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ollama:classify-frames
                            {--model= : Override Ollama model from .env}
                            {--limit= : Limit number of frames to process (useful for testing)}
                            {--resume : Resume from existing CSV file}
                            {--dry-run : Show prompt for first frame without calling API}
                            {--debug : Save raw Ollama responses to debug files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Classify frames into semantic namespaces using Ollama LLM with pre-classification';

    private array $namespaceDefinitions = [
        '@situation' => 'Catch-all namespace for frames that do not fit into the other namespaces. Used for scenario frames and image-schema frames. Variable structure.',
        '@eventive' => 'Eventive frames that do not have an explicitly defined agent, cause, or experiencer (e.g., natural phenomena). FE core ("event") incorporated by LU.',
        '@causative' => 'Eventive frames that have a cause or an agent. FE core "agent" or "cause" (in excludes relation).',
        '@inchoative' => 'Eventive frames that exhibit inchoative alternation. FE core indicating the affected element.',
        '@stative' => 'Frames that represent states. FE core for entity, FE core-unexpressed for state.',
        '@experience' => 'Eventive frames that profile the participant as an experiencer in an event. FE core for entity experimenting the event ("experiencer", "perceiver", etc).',
        '@transition' => 'Eventive frames that represent changes in situation (states, attributes, categories, etc.). FE core for entity under transition; FEs for initial/final state or condition.',
        '@attribute' => 'Frames that represent attributes or attribute values. FE core for attribute, FE core-unexpressed for attribute.',
        '@entity' => 'Frames that represent entities. FE core for entity, incorporated by LU.',
        '@relation' => 'Frames that represent relationships. FEs core for related concepts and FE core joining the concepts.',
        '@pragmatic' => 'Pragmatic frames. Variable structure.',
    ];

    private array $results = [];
    private array $errors = [];
    private int $processed = 0;
    private int $preClassified = 0;
    private int $llmClassified = 0;
    private array $processedIds = [];
    private ?string $resumeCsvPath = null;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Enhanced Frame Namespace Classification with Ollama');
        $this->newLine();

        // Configuration
        $baseUrl = env('OLLAMA_BASE_URL', 'http://localhost:11434');
        $model = $this->option('model') ?? env('OLLAMA_DEFAULT_MODEL', 'llama3.1:8b');
        $timeout = (int) env('OLLAMA_TIMEOUT', 30);
        $isDryRun = $this->option('dry-run');
        $isDebug = $this->option('debug');
        $isResume = $this->option('resume');

        $this->info("Configuration:");
        $this->line("  • Base URL: {$baseUrl}");
        $this->line("  • Model: {$model}");
        $this->line("  • Timeout: {$timeout}s");
        $this->line("  • Language: English (idLanguage=2)");
        if ($isDryRun) {
            $this->warn("  • DRY RUN MODE - No API calls will be made");
        }
        if ($isDebug) {
            $this->warn("  • DEBUG MODE - Raw responses will be saved");
        }
        if ($isResume) {
            $this->warn("  • RESUME MODE - Will skip already processed frames");
        }
        $this->newLine();

        // Handle resume mode
        if ($isResume) {
            $this->loadResumeData();
        }

        // Query frames from database
        $this->info('📊 Querying frames from database...');

        try {
            $frames = Criteria::table('view_frame')
                ->select('idFrame', 'name', 'description')
                ->where('idLanguage', '=', 2)
                ->orderBy('name')
                ->all();
        } catch (Exception $e) {
            $this->error("❌ Database query failed: " . $e->getMessage());
            return 1;
        }

        // Filter out already processed frames if resuming
        if ($isResume && !empty($this->processedIds)) {
            $frames = array_filter($frames, fn($frame) => !in_array($frame->idFrame, $this->processedIds));
            $frames = array_values($frames); // Re-index
            $this->info("  • Skipped " . count($this->processedIds) . " already processed frames");
        }

        // Apply limit if provided
        $limit = $this->option('limit');
        if ($limit && is_numeric($limit)) {
            $frames = array_slice($frames, 0, (int) $limit);
            $this->warn("  • LIMIT: Processing only first {$limit} frames");
        }

        $totalFrames = count($frames);
        $this->info("Found {$totalFrames} frames to classify");
        $this->newLine();

        // Dry run mode - show prompt for first frame and exit
        if ($isDryRun) {
            $this->info('🔍 DRY RUN - Showing prompt for first frame:');
            $this->newLine();

            // Find first frame with description that needs LLM
            $firstFrame = null;
            foreach ($frames as $frame) {
                $preClassification = $this->checkPreClassification($frame->idFrame);
                if (empty($preClassification) && !empty($frame->description)) {
                    $firstFrame = $frame;
                    break;
                }
            }

            if (!$firstFrame) {
                $this->error('❌ No frames with descriptions found that need LLM classification');
                return 1;
            }

            $coreFEs = $this->getCoreFEs($firstFrame->idFrame);
            $prompt = $this->buildPrompt($firstFrame->name, $firstFrame->description, $coreFEs);

            $this->line('─────────────────────────────────────────────');
            $this->line($prompt);
            $this->line('─────────────────────────────────────────────');
            $this->newLine();
            $this->info('✅ Dry run completed. Use without --dry-run to process all frames.');

            return 0;
        }

        // Process all frames
        $this->info('🔄 Processing frames...');
        $this->newLine();

        $this->withProgressBar($frames, function ($frame) use ($baseUrl, $model, $timeout, $isDebug) {
            $this->processFrame($frame, $baseUrl, $model, $timeout, $isDebug);
        });

        $this->newLine(2);

        // Display errors if any
        if (!empty($this->errors)) {
            $this->warn("⚠️  {$this->processed} frames processed, " . count($this->errors) . " errors occurred:");
            foreach (array_slice($this->errors, 0, 10) as $error) {
                $this->line("  • Frame {$error['idFrame']} ({$error['name']}): {$error['error']}");
            }
            if (count($this->errors) > 10) {
                $this->line("  • ... and " . (count($this->errors) - 10) . " more errors");
            }
            $this->newLine();
        }

        // Export to CSV
        $this->info('💾 Exporting results to CSV...');
        $csvPath = $this->exportToCsv($isResume);
        $this->info("✅ CSV exported to: {$csvPath}");
        $this->newLine();

        // Display statistics
        $this->displayStatistics();

        $this->newLine();
        $this->info("✅ Classification completed! Total processed: {$this->processed} (Pre-classified: {$this->preClassified}, LLM: {$this->llmClassified})");

        return 0;
    }

    private function loadResumeData(): void
    {
        // Find most recent CSV file
        $files = Storage::disk('local')->files('namespace-classifications');
        $csvFiles = array_filter($files, fn($file) => str_ends_with($file, '.csv'));

        if (empty($csvFiles)) {
            $this->warn('  • No existing CSV files found, starting fresh');
            return;
        }

        // Sort by timestamp in filename (most recent first)
        usort($csvFiles, function ($a, $b) {
            return strcmp($b, $a);
        });

        $this->resumeCsvPath = $csvFiles[0];
        $csvContent = Storage::disk('local')->get($this->resumeCsvPath);

        // Parse CSV to get processed frame IDs
        $lines = explode("\n", $csvContent);
        array_shift($lines); // Remove header

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $data = str_getcsv($line);
            if (!empty($data[0]) && is_numeric($data[0])) {
                $this->processedIds[] = (int) $data[0];
            }
        }

        $this->info("  • Resuming from: storage/app/{$this->resumeCsvPath}");
        $this->info("  • Found " . count($this->processedIds) . " already processed frames");
    }

    private function checkPreClassification(int $idFrame): ?string
    {
        try {
            $result = Criteria::table('view_frame_classification')
                ->select('name')
                ->where('idLanguage', '=', 2)
                ->where('relationType', '=', 'rel_framal_type')
                ->where('idFrame', '=', $idFrame)
                ->all();

            if (empty($result)) {
                return null;
            }

            $typeName = $result[0]->name;

            // Map pre-classification types to namespaces
            if (in_array($typeName, ['@ImageSchema', '@Scenario'])) {
                return '@situation';
            }

            if ($typeName === '@Pragmatic') {
                return '@pragmatic';
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function getCoreFEs(int $idFrame): array
    {
        try {
            $fes = Criteria::table('view_frameelement')
                ->select('name')
                ->whereIn('coreType', ['cty_core', 'cty_core-unexpressed'])
                ->where('idLanguage', '=', 2)
                ->where('idFrame', '=', $idFrame)
                ->all();

            return array_map(fn($fe) => $fe->name, $fes);
        } catch (Exception $e) {
            return [];
        }
    }

    private function processFrame(object $frame, string $baseUrl, string $model, int $timeout, bool $isDebug): void
    {
        try {
            // Check pre-classification first
            $preClassification = $this->checkPreClassification($frame->idFrame);

            if ($preClassification) {
                // Pre-classified frame
                $this->results[] = [
                    'idFrame' => $frame->idFrame,
                    'name' => $frame->name,
                    'namespace1' => $preClassification,
                    'confidence1' => 1.0,
                    'namespace2' => '',
                    'confidence2' => '',
                ];

                $this->processed++;
                $this->preClassified++;
                return;
            }

            // Skip frames without description
            if (empty($frame->description)) {
                throw new Exception("Frame has no description");
            }

            // Get core FEs
            $coreFEs = $this->getCoreFEs($frame->idFrame);

            // Build prompt and call LLM
            $prompt = $this->buildPrompt($frame->name, $frame->description, $coreFEs);

            $response = Http::timeout($timeout)->post("{$baseUrl}/api/chat", [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a linguistic expert specializing in FrameNet semantic analysis. Your task is to classify frames into semantic namespaces. Always respond with valid JSON only, no additional text.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'stream' => false,
                'options' => [
                    'temperature' => 0.3,
                ],
            ]);

            if (!$response->successful()) {
                throw new Exception("API request failed with status {$response->status()}");
            }

            $data = $response->json();
            $content = $data['message']['content'] ?? '';

            // Save debug output if requested
            if ($isDebug) {
                $debugFile = "debug/frame-{$frame->idFrame}-" . Carbon::now()->format('Y-m-d_H-i-s') . '.txt';
                Storage::disk('local')->put($debugFile, $content);
            }

            // Parse classifications from response
            $classifications = $this->parseClassificationsFromResponse($content);

            $this->results[] = [
                'idFrame' => $frame->idFrame,
                'name' => $frame->name,
                'namespace1' => $classifications[0]['namespace'] ?? '',
                'confidence1' => $classifications[0]['confidence'] ?? '',
                'namespace2' => $classifications[1]['namespace'] ?? '',
                'confidence2' => $classifications[1]['confidence'] ?? '',
            ];

            $this->processed++;
            $this->llmClassified++;

        } catch (Exception $e) {
            $this->errors[] = [
                'idFrame' => $frame->idFrame,
                'name' => $frame->name,
                'error' => $e->getMessage(),
            ];

            // Add error result to maintain CSV completeness
            $this->results[] = [
                'idFrame' => $frame->idFrame,
                'name' => $frame->name,
                'namespace1' => 'ERROR',
                'confidence1' => '',
                'namespace2' => '',
                'confidence2' => '',
            ];
        }
    }

    private function buildPrompt(string $frameName, ?string $frameDefinition, array $coreFEs): string
    {
        $namespaceTable = "Available Namespaces:\n\n";
        foreach ($this->namespaceDefinitions as $namespace => $description) {
            $namespaceTable .= "• {$namespace}: {$description}\n";
        }

        $frameDefinition = $frameDefinition ?? 'No definition available';
        $coreFEsList = !empty($coreFEs) ? implode(', ', $coreFEs) : 'None';

        return <<<PROMPT
You are a FrameNet expert. Your task is to classify the following semantic frame into namespaces based on its definition and core frame elements.

{$namespaceTable}

Frame to classify:
Name: {$frameName}
Definition: {$frameDefinition}
Core Frame Elements: {$coreFEsList}

Instructions:
1. Read the frame name, definition, and core FEs carefully
2. The core FEs are particularly important for determining the namespace
3. Choose the MOST APPROPRIATE namespace (required)
4. Optionally provide a SECOND namespace if the frame has characteristics of multiple namespaces
5. Provide a confidence score (0.0 to 1.0) for each classification
6. Respond with ONLY a JSON object in this exact format:

For ONE classification:
{"classifications": [{"namespace": "@namespace_name", "confidence": 0.95}]}

For TWO classifications:
{"classifications": [{"namespace": "@primary_namespace", "confidence": 0.85}, {"namespace": "@secondary_namespace", "confidence": 0.40}]}

7. Do not include any explanation, additional text, or markdown formatting

Your response (JSON only):
PROMPT;
    }

    private function parseClassificationsFromResponse(string $content): array
    {
        // Try to extract JSON from response
        $content = trim($content);

        // Remove markdown code blocks if present
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);

        // Find JSON object
        $jsonStart = strpos($content, '{');
        $jsonEnd = strrpos($content, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception("No JSON object found in response");
        }

        $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON: " . json_last_error_msg());
        }

        if (!isset($data['classifications']) || !is_array($data['classifications'])) {
            throw new Exception("Missing or invalid 'classifications' field in JSON response");
        }

        $classifications = [];
        foreach ($data['classifications'] as $classification) {
            if (!isset($classification['namespace']) || !isset($classification['confidence'])) {
                continue;
            }

            $namespace = $classification['namespace'];

            // Validate namespace
            if (!array_key_exists($namespace, $this->namespaceDefinitions)) {
                throw new Exception("Invalid namespace: {$namespace}");
            }

            $classifications[] = [
                'namespace' => $namespace,
                'confidence' => (float) $classification['confidence'],
            ];

            // Limit to 2 classifications
            if (count($classifications) >= 2) {
                break;
            }
        }

        if (empty($classifications)) {
            throw new Exception("No valid classifications found in response");
        }

        return $classifications;
    }

    private function exportToCsv(bool $isResume): string
    {
        if ($isResume && $this->resumeCsvPath) {
            // Append to existing file
            $stream = fopen('php://temp', 'r+');

            // Write data rows (no header for append)
            foreach ($this->results as $result) {
                fputcsv($stream, [
                    $result['idFrame'],
                    $result['name'],
                    $result['namespace1'],
                    $result['confidence1'],
                    $result['namespace2'],
                    $result['confidence2'],
                ]);
            }

            // Get CSV content
            rewind($stream);
            $newContent = stream_get_contents($stream);
            fclose($stream);

            // Append to existing file
            $existingContent = Storage::disk('local')->get($this->resumeCsvPath);
            Storage::disk('local')->put($this->resumeCsvPath, $existingContent . $newContent);

            return "storage/app/{$this->resumeCsvPath}";
        } else {
            // Create new file
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "namespace-classifications/frames_namespaces_{$timestamp}.csv";

            // Ensure directory exists
            Storage::disk('local')->makeDirectory('namespace-classifications');

            // Build CSV using streams for proper escaping
            $stream = fopen('php://temp', 'r+');

            // Write header
            fputcsv($stream, ['idFrame', 'name', 'namespace1', 'confidence1', 'namespace2', 'confidence2']);

            // Write data rows
            foreach ($this->results as $result) {
                fputcsv($stream, [
                    $result['idFrame'],
                    $result['name'],
                    $result['namespace1'],
                    $result['confidence1'],
                    $result['namespace2'],
                    $result['confidence2'],
                ]);
            }

            // Get CSV content
            rewind($stream);
            $csv = stream_get_contents($stream);
            fclose($stream);

            // Save to storage
            Storage::disk('local')->put($filename, $csv);

            return "storage/app/{$filename}";
        }
    }

    private function displayStatistics(): void
    {
        $this->info('📈 Classification Statistics:');
        $this->newLine();

        // Count by primary namespace
        $stats = [];
        foreach ($this->results as $result) {
            $namespace = $result['namespace1'];
            if (!isset($stats[$namespace])) {
                $stats[$namespace] = 0;
            }
            $stats[$namespace]++;
        }

        // Sort by count descending
        arsort($stats);

        // Prepare table data
        $tableData = [];
        foreach ($stats as $namespace => $count) {
            $percentage = $this->processed > 0 ? round(($count / $this->processed) * 100, 1) : 0;
            $tableData[] = [
                $namespace,
                $count,
                "{$percentage}%",
            ];
        }

        $this->table(
            ['Namespace', 'Count', 'Percentage'],
            $tableData
        );

        $this->newLine();
        $this->info("Classification Method Breakdown:");
        $this->line("  • Pre-classified (from database): {$this->preClassified}");
        $this->line("  • LLM classified: {$this->llmClassified}");
        $this->line("  • Errors: " . count($this->errors));
    }
}
