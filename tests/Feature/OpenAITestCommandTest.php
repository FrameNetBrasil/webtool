<?php

namespace Tests\Feature;

use Tests\TestCase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Testing\ClientFake;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Responses\Models\ListResponse;

class OpenAITestCommandTest extends TestCase
{
    public function test_command_fails_without_api_key(): void
    {
        config(['openai.api_key' => null]);
        
        $this->artisan('openai:test')
            ->expectsOutput('❌ OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.')
            ->assertExitCode(1);
    }

    public function test_command_runs_successfully_with_mocked_responses(): void
    {
        config(['openai.api_key' => 'test-key']);

        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Laravel is a PHP web framework known for its elegant syntax and developer-friendly features.',
                        ],
                    ],
                ],
                'usage' => [
                    'total_tokens' => 25,
                ],
            ]),
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'A practical example would be the "Commerce" frame which includes roles like Buyer, Seller, Goods, and Money.',
                        ],
                    ],
                ],
                'usage' => [
                    'total_tokens' => 35,
                ],
            ]),
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Semantic roles define the relationship between predicates and their arguments in sentences.',
                        ],
                    ],
                ],
                'usage' => [
                    'total_tokens' => 20,
                ],
            ]),
            ListResponse::fake([
                'data' => [
                    [
                        'id' => 'gpt-4o-mini',
                        'object' => 'model',
                    ],
                    [
                        'id' => 'gpt-4o',
                        'object' => 'model',
                    ],
                    [
                        'id' => 'gpt-3.5-turbo',
                        'object' => 'model',
                    ],
                ],
            ]),
        ]);

        $this->artisan('openai:test')
            ->expectsOutput('🚀 Testing OpenAI Laravel Integration...')
            ->expectsOutput('Using model: gpt-4o-mini')
            ->expectsOutput('🔸 Test 1: Simple Chat Completion')
            ->expectsOutput('Response: Laravel is a PHP web framework known for its elegant syntax and developer-friendly features.')
            ->expectsOutput('Tokens used: 25')
            ->expectsOutput('🔸 Test 2: Multi-turn Conversation')
            ->expectsOutput('Multi-turn response: A practical example would be the "Commerce" frame which includes roles like Buyer, Seller, Goods, and Money.')
            ->expectsOutput('Tokens used: 35')
            ->expectsOutput('🔸 Test 3: Text Generation with System Message')
            ->expectsOutput('System-guided response: Semantic roles define the relationship between predicates and their arguments in sentences.')
            ->expectsOutput('Tokens used: 20')
            ->expectsOutput('🔸 Test 4: Available Models')
            ->expectsOutput('Available GPT models (first 5):')
            ->expectsOutput('  • gpt-4o-mini')
            ->expectsOutput('  • gpt-4o')
            ->expectsOutput('  • gpt-3.5-turbo')
            ->expectsOutput('✅ All OpenAI tests completed successfully!')
            ->assertExitCode(0);
    }

    public function test_command_handles_openai_exceptions(): void
    {
        config(['openai.api_key' => 'test-key']);

        OpenAI::fake([
            new \Exception('OpenAI API error'),
        ]);

        $this->artisan('openai:test')
            ->expectsOutput('❌ Error: OpenAI API error')
            ->assertExitCode(1);
    }

    public function test_command_accepts_custom_model_option(): void
    {
        config(['openai.api_key' => 'test-key']);

        OpenAI::fake([
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Test response',
                        ],
                    ],
                ],
                'usage' => [
                    'total_tokens' => 10,
                ],
            ]),
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Test response 2',
                        ],
                    ],
                ],
                'usage' => [
                    'total_tokens' => 10,
                ],
            ]),
            CreateResponse::fake([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Test response 3',
                        ],
                    ],
                ],
                'usage' => [
                    'total_tokens' => 10,
                ],
            ]),
            ListResponse::fake([
                'data' => [
                    [
                        'id' => 'gpt-4o',
                        'object' => 'model',
                    ],
                ],
            ]),
        ]);

        $this->artisan('openai:test', ['--model' => 'gpt-4o'])
            ->expectsOutput('Using model: gpt-4o')
            ->assertExitCode(0);
    }
}
