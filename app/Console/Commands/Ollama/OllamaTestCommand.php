<?php

namespace App\Console\Commands\Ollama;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Exception;

class OllamaTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ollama:test
                            {--model= : The Ollama model to use (default: from .env)}
                            {--base-url= : Ollama API base URL (default: from .env)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Ollama integration with various examples including chat completions and text generation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Testing Ollama Integration...');
        $this->newLine();

        $baseUrl = $this->option('base-url') ?? env('OLLAMA_BASE_URL', 'http://localhost:11434');
        $model = $this->option('model') ?? env('OLLAMA_DEFAULT_MODEL', 'llama3.1:8b');
        $timeout = (int) env('OLLAMA_TIMEOUT', 30);

        $this->info("Base URL: {$baseUrl}");
        $this->info("Using model: {$model}");
        $this->info("Timeout: {$timeout}s");
        $this->newLine();

        try {
            // Test 0: Check Ollama server is running
            $this->testConnection($baseUrl, $timeout);

            // Test 1: Simple Chat Completion
            $this->testChatCompletion($baseUrl, $model, $timeout);

            // Test 2: Conversation Flow
            $this->testConversationFlow($baseUrl, $model, $timeout);

            // Test 3: Text Generation with System Message
            $this->testTextGenerationWithSystem($baseUrl, $model, $timeout);

            // Test 4: List Available Models
            $this->testListModels($baseUrl, $timeout);

        } catch (Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('✅ All Ollama tests completed successfully!');
        return 0;
    }

    private function testConnection(string $baseUrl, int $timeout): void
    {
        $this->info('🔸 Test 0: Check Ollama Server Connection');

        try {
            $response = Http::timeout($timeout)->get("{$baseUrl}/api/version");

            if ($response->successful()) {
                $version = $response->json('version') ?? 'unknown';
                $this->line("✅ Ollama server is running (version: {$version})");
            } else {
                throw new Exception("Server returned status {$response->status()}");
            }
        } catch (Exception $e) {
            $this->error("❌ Cannot connect to Ollama server at {$baseUrl}");
            $this->line("Make sure Ollama is running with: ollama serve");
            throw $e;
        }

        $this->newLine();
    }

    private function testChatCompletion(string $baseUrl, string $model, int $timeout): void
    {
        $this->info('🔸 Test 1: Simple Chat Completion');

        $response = Http::timeout($timeout)->post("{$baseUrl}/api/chat", [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => 'Hello! Can you tell me what is Laravel in one sentence?'],
            ],
            'stream' => false,
        ]);

        if (!$response->successful()) {
            throw new Exception("Chat completion failed: {$response->body()}");
        }

        $data = $response->json();
        $content = $data['message']['content'] ?? 'No response';

        $this->line("Response: {$content}");
        $this->newLine();
    }

    private function testConversationFlow(string $baseUrl, string $model, int $timeout): void
    {
        $this->info('🔸 Test 2: Multi-turn Conversation');

        $response = Http::timeout($timeout)->post("{$baseUrl}/api/chat", [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => 'What is FrameNet?'],
                ['role' => 'assistant', 'content' => 'FrameNet is a lexical database of English that is both a lexicon and a thesaurus, organized around semantic frames.'],
                ['role' => 'user', 'content' => 'Can you give me a practical example?'],
            ],
            'stream' => false,
        ]);

        if (!$response->successful()) {
            throw new Exception("Conversation flow failed: {$response->body()}");
        }

        $data = $response->json();
        $content = $data['message']['content'] ?? 'No response';

        $this->line("Multi-turn response: {$content}");
        $this->newLine();
    }

    private function testTextGenerationWithSystem(string $baseUrl, string $model, int $timeout): void
    {
        $this->info('🔸 Test 3: Text Generation with System Message');

        $response = Http::timeout($timeout)->post("{$baseUrl}/api/chat", [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant specialized in linguistic annotation and FrameNet. Provide concise, technical responses.'
                ],
                [
                    'role' => 'user',
                    'content' => 'Explain semantic roles in one sentence.'
                ],
            ],
            'stream' => false,
            'options' => [
                'temperature' => 0.7,
            ],
        ]);

        if (!$response->successful()) {
            throw new Exception("Text generation with system failed: {$response->body()}");
        }

        $data = $response->json();
        $content = $data['message']['content'] ?? 'No response';

        $this->line("System-guided response: {$content}");
        $this->newLine();
    }

    private function testListModels(string $baseUrl, int $timeout): void
    {
        $this->info('🔸 Test 4: Available Models');

        $response = Http::timeout($timeout)->get("{$baseUrl}/api/tags");

        if (!$response->successful()) {
            throw new Exception("List models failed: {$response->body()}");
        }

        $data = $response->json();
        $models = $data['models'] ?? [];

        if (empty($models)) {
            $this->line('No models found. Pull a model with: ollama pull llama3.1:8b');
        } else {
            $this->line('Available models:');
            foreach ($models as $modelInfo) {
                $name = $modelInfo['name'] ?? 'unknown';
                $size = $this->formatBytes($modelInfo['size'] ?? 0);
                $modified = isset($modelInfo['modified_at'])
                    ? date('Y-m-d H:i', strtotime($modelInfo['modified_at']))
                    : 'unknown';

                $this->line("  • {$name} ({$size}, modified: {$modified})");
            }
        }

        $this->newLine();
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
