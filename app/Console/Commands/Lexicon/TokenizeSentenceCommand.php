<?php

namespace App\Console\Commands\Lexicon;

use App\Services\Daisy\TokenizerService;
use Illuminate\Console\Command;

class TokenizeSentenceCommand extends Command
{
    protected $signature = 'lexicon:tokenize
                            {sentence : The sentence to tokenize}
                            {--language=1 : Language ID (default: 1 for Portuguese)}';

    protected $description = 'Tokenize a sentence considering Multi-Word Expressions (MWEs) with lemma-based matching';

    public function handle(): int
    {
        $sentence = $this->argument('sentence');
        $idLanguage = (int) $this->option('language');

        $this->info("Tokenizing: \"{$sentence}\"");
        $this->info("Language ID: {$idLanguage}");
        $this->newLine();

        // Create tokenizer service and tokenize
        $tokenizer = new TokenizerService(idLanguage: $idLanguage);
        $tokenizer->initialize();

        $this->info("Loaded {$this->formatNumber($tokenizer->getMweCount())} MWE first-lemma entries");
        $this->newLine();

        $tokens = $tokenizer->tokenize($sentence);

        // Display results
        $this->displayTokens($tokens);

        return Command::SUCCESS;
    }

    private function displayTokens(array $tokens): void
    {
        $this->info('Tokens:');
        $this->newLine();

        $tableData = [];
        foreach ($tokens as $token) {
            $lemmaStr = empty($token->idLemmas)
                ? '(none)'
                : implode(', ', $token->idLemmas);

            $tableData[] = [
                $token->position,
                $token->form,
                $token->isMwe ? 'Yes' : 'No',
                $lemmaStr,
            ];
        }

        $this->table(['#', 'Form', 'MWE?', 'Lemma IDs'], $tableData);

        $this->newLine();
        $this->info('JSON output:');
        $this->line(json_encode(
            array_map(fn ($t) => $t->toArray(), $tokens),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ));
    }

    private function formatNumber(int $number): string
    {
        return number_format($number);
    }
}
