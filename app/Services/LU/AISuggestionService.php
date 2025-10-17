<?php

namespace App\Services\LU;

use App\Data\LU\AISuggestionData;
use App\Database\Criteria;
use App\Repositories\Frame;
use App\Services\AppService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

class AISuggestionService extends AppService
{
    public static function handle(AISuggestionData $data): array
    {
        $frameId = $data->idFrame;
        $targetPos = $data->pos;
        $model = ($data->model == 'openai') ? 'gpt-4o' : 'llama';
        $targetN = ($model == 'llama') ? 15 : 15;
        $dryRun = false;

        // Validate POS types
        $validPos = self::validatePosTypes($targetPos);

        //        $this->info("🚀 Generating LUs for Frame ID: {$frameId}");
        //        $this->info("📊 Target suggestions: {$targetN}");
        //        $this->info("🏷️  Target POS: {$targetPos}");
        //        $this->info("🤖 Model: {$model}");

        //        if ($dryRun) {
        //            $this->warn("🧪 DRY RUN MODE - No API calls will be made");
        //        }
        //
        //        $this->newLine();

        try {
            // Validate frame exists
            $frame = self::validateFrame($frameId);

            // Get current LUs for this frame
            $currentLUs = self::getCurrentLUs($frameId, $validPos);

            // Load prompt template

            if ($model == 'gpt-4o') {
                $promptTemplate = self::loadPromptTemplate();
                // Build complete prompt
                $pos = implode(",", $targetPos);
                $fullPrompt = self::buildPrompt($promptTemplate, $frame, $currentLUs, $targetN, $pos);
                if ($dryRun) {
                    //                $this->displayDryRun($fullPrompt);
                    //                return 0;
                }
                // Check API key
                if (empty(config('openai.api_key'))) {
                    throw new \Exception('❌ OpenAI API key not configured. Please set OPENAI_API_KEY in your .env file.');
                }
                // Call OpenAI API
//                $response = self::callOpenAI($fullPrompt, $model);
//                // Process and display results
//                $results = self::processResponse($response, $frame);
            } else {
                $response = self::callLLama($frame, $currentLUs, $targetN, $targetPos);
            }
            $results = self::processResponse($response, $frame);

//            $results = [];

            // Check for existing LUs
            $results = self::checkExistingLU($results, $frameId);

            // Export results
            // self::exportResults($results, $frameId, $frame->name, $targetPos);

            //            $this->newLine();
            //            $this->info('✅ LU generation completed successfully!');
            //
            //            return 0;
            return $results;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private static function loadPromptTemplate(): string
    {
        $promptPath = 'prompts/lu_prompt.txt';

        if (! Storage::disk('local')->exists($promptPath)) {
            throw new Exception("Prompt template not found at storage/app/{$promptPath}");
        }

        $template = Storage::disk('local')->get($promptPath);

        if (empty($template)) {
            throw new Exception('Prompt template is empty');
        }

        //        $this->info("📄 Loaded prompt template from storage");
        return $template;
    }

    private static function validateFrame(int $frameId): object
    {
        try {
            $frame = Frame::byId($frameId);

            if (empty($frame)) {
                throw new Exception("Frame with ID {$frameId} not found");
            }

            //            $this->info("🎯 Frame: {$frame->name}");
            return $frame;

        } catch (Exception $e) {
            throw new Exception("Invalid frame ID {$frameId}: ".$e->getMessage());
        }
    }

    private static function validatePosTypes(array $targetPos): array
    {
        $validPosTypes = ['VERB', 'NOUN', 'ADJ'];
        foreach ($targetPos as $pos) {
            if (! in_array($pos, $validPosTypes)) {
                throw new Exception("Invalid POS type '{$pos}'. Valid types are: VERB, NOUN, ADJ");
            }
        }

        return $targetPos;
    }

    private static function getCurrentLUs(int $frameId, array $validPos): array
    {
        $lus = Criteria::table('view_lu as lu')
            ->join('udpos', 'lu.idUDPOS', '=', 'udpos.idUDPOS')
            ->select(['lu.name as lemma', 'udpos.POS', 'lu.senseDescription','lu.lemmaName'])
            ->where('lu.idFrame', $frameId)
            ->where('lu.idLanguage', 1) // Portuguese
            ->whereIn('udpos.POS', $validPos) // Filter by valid POS types
            ->orderBy('lu.name')
            ->limit(20)
            ->get()
            ->all();

        //        $this->info("📋 Found " . count($lus) . " existing LUs for this frame");

        return $lus;
    }

    private static function buildPrompt(string $template, object $frame, array $currentLUs, int $targetN, string $targetPos): string
    {
        // Format current LUs
        $lusText = '';
        foreach ($currentLUs as $lu) {
            $lusText .= "{$lu->lemmaName},";
//            $lusText .= "- {$lu->lemma}.{$lu->POS}";
//            if (! empty($lu->senseDescription)) {
//                $lusText .= " - {$lu->senseDescription}";
//            }
//            $lusText .= "\n";
        }

        if (empty($lusText)) {
            $lusText = '[No existing LUs for this frame]';
        }

        // Replace template variables
        $prompt = str_replace('<TARGET_FRAME>', $frame->name, $template);
        $prompt = str_replace('<TARGET_N>', (string) $targetN, $prompt);
        $prompt = str_replace('<TARGET_POS>', $targetPos, $prompt);
        $prompt = str_replace('<CURRENT_LUS>', trim($lusText), $prompt);
        $prompt = str_replace('<FRAME_DEFINITION>', $frame->description ?? '[No description available]', $prompt);

        // Save the parsed prompt to storage/prompts
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "parsed_prompt_frame_{$frame->idFrame}_{$timestamp}.txt";

            Storage::disk('local')->put("prompts/{$filename}", $prompt);
        } catch (Exception $e) {
            // Log error but don't break the flow
            // Could optionally log this error if logging is configured
        }

        return $prompt;
    }

    private static function displayDryRun(string $prompt): void
    {
        //        $this->newLine();
        //        $this->info('📝 Generated Prompt:');
        //        $this->line('═══════════════════════════════════════════════════════════════');
        //        $this->line($prompt);
        //        $this->line('═══════════════════════════════════════════════════════════════');
    }

    private static function callOpenAI(string $prompt, string $model): string
    {
        //        $this->info("🤖 Calling OpenAI API with model: {$model}");

        $response = OpenAI::chat()->create([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => 2000,
            'temperature' => 0.7,
        ]);

        $content = $response->choices[0]->message->content;
        //        $this->info("💫 Received response ({$response->usage->totalTokens} tokens)");

        // Save raw response for debugging if requested
        //        if ($this->option('debug')) {
        //            $debugFile = 'debug/openai-response-' . Carbon::now()->format('Y-m-d_H-i-s') . '.txt';
        //            Storage::disk('local')->put($debugFile, $content);
        //            $this->warn("🐛 Raw response saved to: storage/app/{$debugFile}");
        //        }

        return $content;
    }

    private static function callLLama($frame, $currentLUs, $targetN, $targetPos): string
    {
        $content = '';
        $exclusion_list = [];
        foreach ($currentLUs as $lu) {
            $exclusion_list[] = $lu->lemmaName;
        }
        $parameters = [
            'frame' => $frame->name,
            'frame_definition' => $frame->description,
            'target_count' => $targetN,
            'exclusion_list' => $exclusion_list,
            'acceptable_pos' => $targetPos,
            'temperature' => 0.1,
            'max_tokens'=> 2048
        ];
        debug($parameters);
        $client = new Client([
            'timeout' => 300.0,
        ]);

        try {
            $response = $client->post('http://server5.frame.net.br:5000/generate', [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json' => $parameters
            ]);
            $content = $response->getBody();
            debug(json_decode($content));
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
        }
        return $content;
    }

    private static function processResponse(string $response, object $frame): array
    {
        // Try to extract JSON from response
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            //            $this->error("Raw OpenAI response:");
            //            $this->line($response);
            throw new Exception('No JSON found in OpenAI response');
        }

        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $results = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            //            $this->error("JSON parsing failed. Raw JSON extracted:");
            //            $this->line($jsonString);
            //            $this->newLine();
            //            $this->error("Full OpenAI response:");
            //            $this->line($response);
            throw new Exception('Invalid JSON in OpenAI response: '.json_last_error_msg());
        }

        if (! isset($results['items']) || ! is_array($results['items'])) {
            throw new Exception("Invalid response format: 'items' array not found");
        }

        //        $this->info("✅ Generated " . count($results['items']) . " LU suggestions");

        // Display results table
        //        $this->displayResults($results);

        return $results;
    }

    private static function displayResults(array $results): void
    {
        //        $this->newLine();
        //        $this->info("📋 Generated LU Suggestions:");

        $tableData = [];
        foreach ($results['items'] as $item) {
            $confidence = $item['confidence'] ?? 0;
            $confidenceColor = $confidence > 0.8 ? 'green' : ($confidence > 0.6 ? 'yellow' : 'red');

            $tableData[] = [
                $item['lemma'] ?? '',
                $item['pos'] ?? '',
                substr($item['gloss_pt'] ?? '', 0, 50).(strlen($item['gloss_pt'] ?? '') > 50 ? '...' : ''),
                number_format($confidence, 2),
                substr($item['rationale_short'] ?? '', 0, 60).(strlen($item['rationale_short'] ?? '') > 60 ? '...' : ''),
            ];
        }

        //        $this->table(
        //            ['Lemma', 'POS', 'Gloss (PT)', 'Confidence', 'Rationale'],
        //            $tableData
        //        );

        //        if (!empty($results['excluded_notes'])) {
        //            $this->newLine();
        //            $this->warn("ℹ️  Notes: " . $results['excluded_notes']);
        //        }
    }

    private static function checkExistingLU(array $results, int $idFrame): array
    {
        foreach ($results['items'] as $i => $item) {
            $lu = Criteria::table('view_lu as lu')
                ->join('udpos', 'lu.idUDPOS', '=', 'udpos.idUDPOS')
                ->select('lu.idLU')
                ->where('lu.idFrame', $idFrame)
                ->where('lu.idLanguage', 1) // Portuguese
                ->where('udpos.POS', $item['pos']) // Filter by valid POS types
                ->whereRaw("LOWER(lu.lemmaName) = LOWER('{$item['lemma']}')")
                ->first();
            $results['items'][$i]['idLU'] = $lu?->idLU ?? null;
        }

        return $results;
    }

    private static function exportResults(array $results, int $frameId, string $frameName, string $targetPos): void
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "lu-suggestions/frame-{$frameId}_{$frameName}_{$timestamp}.json";

        // Ensure directory exists
        Storage::disk('local')->makeDirectory(dirname($filename));

        // Prepare export data
        $exportData = [
            'meta' => [
                'frame_id' => $frameId,
                'frame_name' => $frameName,
                'target_pos' => $targetPos,
                'generated_at' => Carbon::now()->toISOString(),
                'model' => 'gpt-4o',
                'target_n' => 5,
            ],
            'results' => $results,
        ];

        Storage::disk('local')->put($filename, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        //        $this->info("💾 Results exported to: storage/app/{$filename}");
    }

    // ========================================================================
    // OpenAI Usage and Billing Monitoring Methods
    // ========================================================================
    // These methods provide functionality to monitor OpenAI API usage and
    // spending, similar to the Python examples in openai_credit.md
    //
    // Note: OpenAI billing limits are not accessible via API keys - they
    // require browser session authentication. These methods focus on usage
    // tracking with optional custom limits for budget management.
    // ========================================================================

    /**
     * Get OpenAI API usage for a specific date range
     *
     * @param  string  $startDate  Date in Y-m-d format
     * @param  string  $endDate  Date in Y-m-d format
     * @return array Usage data including total usage in USD
     *
     * @throws Exception
     */
    public static function getUsage(string $startDate, string $endDate): array
    {
        try {
            $apiKey = config('openai.api_key');
            if (empty($apiKey)) {
                throw new Exception('OpenAI API key not configured');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->get('https://api.openai.com/v1/usage', [
                'date' => $startDate,
            ]);

            if (! $response->successful()) {
                throw new Exception('OpenAI API request failed: '.$response->body());
            }

            $data = $response->json();
            $totalUsage = $data['total_usage'] ?? 0;

            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_usage_cents' => $totalUsage,
                'total_usage_usd' => $totalUsage / 100,
                'raw_response' => $data,
            ];

        } catch (Exception $e) {
            throw new Exception('Failed to retrieve OpenAI usage data: '.$e->getMessage());
        }
    }

    /**
     * Get OpenAI API billing information including limits
     * Note: OpenAI billing limits are not directly accessible via API keys.
     * This method provides usage information and guidance for monitoring.
     *
     * @return array Information about monitoring billing and usage
     *
     * @throws Exception
     */
    public static function getBilling(): array
    {
        try {
            // Since billing limits are not accessible via API, we provide usage-based information
            $apiKey = config('openai.api_key');
            if (empty($apiKey)) {
                throw new Exception('OpenAI API key not configured');
            }

            return [
                'note' => 'OpenAI billing limits are not accessible via API keys. Use the web dashboard at https://platform.openai.com/account/billing/overview',
                'suggestion' => 'Monitor usage regularly and set internal limits based on your budget',
                'dashboard_url' => 'https://platform.openai.com/account/billing/overview',
                'usage_url' => 'https://platform.openai.com/account/usage',
                'warning' => 'Always monitor your usage to avoid unexpected charges',
                'accessible_data' => [
                    'usage_by_date_range' => 'Available via getUsage() method',
                    'token_counts' => 'Available in API responses',
                    'model_specific_usage' => 'Available via Usage API endpoints',
                ],
            ];

        } catch (Exception $e) {
            throw new Exception('Failed to provide billing information: '.$e->getMessage());
        }
    }

    /**
     * Check current usage status with available information
     * Note: Since billing limits are not accessible via API, this provides usage data only
     *
     * @param  float|null  $customLimit  Optional custom spending limit in USD
     * @return array Usage status information
     *
     * @throws Exception
     */
    public static function checkCredits(?float $customLimit = null): array
    {
        try {
            // Get current month date range
            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $now->format('Y-m-d');

            // Get usage data
            $usage = self::getUsage($startOfMonth, $endDate);
            $currentUsage = $usage['total_usage_usd'];

            $result = [
                'current_usage_usd' => round($currentUsage, 2),
                'period_start' => $startOfMonth,
                'period_end' => $endDate,
                'checked_at' => $now->toISOString(),
                'usage_data' => $usage,
                'billing_note' => 'Billing limits not accessible via API. Monitor usage at https://platform.openai.com/account/billing/overview',
            ];

            // If custom limit is provided, calculate remaining credits and percentage
            if ($customLimit !== null && $customLimit > 0) {
                $remainingCredits = $customLimit - $currentUsage;
                $result['custom_limit_usd'] = round($customLimit, 2);
                $result['remaining_credits_usd'] = round($remainingCredits, 2);
                $result['has_available_credits'] = $remainingCredits > 0;
                $result['usage_percentage'] = round(($currentUsage / $customLimit) * 100, 2);
            } else {
                $result['has_available_credits'] = true; // Conservative assumption
                $result['note'] = 'No custom limit set. Cannot determine remaining credits.';
            }

            return $result;

        } catch (Exception $e) {
            throw new Exception('Failed to check OpenAI usage: '.$e->getMessage());
        }
    }

    /**
     * Simple check if sufficient credits are available
     *
     * @param  float|null  $customLimit  Optional custom spending limit in USD
     * @return bool True if credits are available, false otherwise
     */
    public static function hasAvailableCredits(?float $customLimit = null): bool
    {
        try {
            $credits = self::checkCredits($customLimit);

            return $credits['has_available_credits'];
        } catch (Exception $e) {
            // Conservative approach: return false if unable to check
            return false;
        }
    }
}
