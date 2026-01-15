<?php

namespace App\Services\UD;

use App\Data\UD\ParseInputData;
use App\Services\Trankit\TrankitService;

class ParserService
{
    /**
     * Parse a sentence using Trankit UD parser and return graph structure for JointJS visualization.
     */
    public static function parse(ParseInputData $data): array
    {
        // Initialize Trankit service
        $trankitService = new TrankitService;
        $trankitService->init(config('udparser.trankit_url'));

        // Get default language from config (can be extended to accept language parameter)
        $idLanguage = config('udparser.default_language', 1);

        // Parse sentence using Trankit
        // Step 1: Tokenize
        $tokens = $trankitService->tokenizeSentence($data->sentence, false);
        // Step 2: Parse with pre-tokenized input
        $result = $trankitService->getUDTrankitTokens($tokens, $idLanguage);
        //        $result = $trankitService->getUDTrankit($data->sentence, $idLanguage);
        debug($result);
        // Check if parsing was successful
        if (empty($result->udpipe)) {
            return [
                'nodes' => [],
                'links' => [],
            ];
        }

        // Transform flat token array to graph structure (nodes and links)
        return self::buildGraph($result->udpipe);
    }

    /**
     * Build graph structure (nodes and links) from flat token array.
     * This follows the same pattern as RelationService::listFrameRelationsForGraph
     */
    private static function buildGraph(array $tokens): array
    {
        $nodes = [];
        $links = [];

        // Create nodes for each token
        foreach ($tokens as $token) {
            $nodes[$token['id']] = [
                'type' => 'word',
                'name' => self::formatNodeName($token),
                'word' => $token['word'],
                'pos' => $token['pos'],
                'deprel' => $token['rel'],
            ];
        }

        // Create links based on dependency relations
        foreach ($tokens as $token) {
            if ($token['parent'] > 0) {
                // Link from child to parent (following dependency direction)
                $links[$token['id']][$token['parent']] = [
                    'type' => 'ud',
                    'relationEntry' => $token['rel'],
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
        ];
    }

    /**
     * Format node name for visualization.
     * Format: "word [POS] [deprel]"
     */
    private static function formatNodeName(array $token): string
    {
        return sprintf(
            '%s [%s] [%s]',
            $token['word'],
            $token['pos'],
            $token['rel']
        );
    }
}
