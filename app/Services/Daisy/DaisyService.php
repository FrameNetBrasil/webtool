<?php

namespace App\Services\Daisy;

use App\Data\Daisy\DaisyInputData;
use App\Data\Daisy\DaisyOutputData;
use App\Services\Trankit\TrankitService;

/**
 * DaisyService - Main Semantic Disambiguation Orchestrator
 *
 * Coordinates the complete Daisy pipeline:
 * 1. Universal Dependencies parsing (Trankit)
 * 2. GRID window creation
 * 3. Lexical unit matching
 * 4. Semantic network construction
 * 5. Spreading activation
 * 6. Winner selection
 */
class DaisyService
{
    private TrankitService $trankitService;

    private GridService $gridService;

    private LexicalUnitService $lexicalUnitService;

    private SemanticNetworkService $semanticNetworkService;

    private SpreadingActivationService $spreadingActivationService;

    private WinnerSelectionService $winnerSelectionService;

    public function __construct(
        TrankitService $trankitService,
        GridService $gridService
    ) {
        $this->trankitService = $trankitService;
        $this->gridService = $gridService;

        // Initialize Trankit with URL
        $trankitUrl = config('daisy.trankitUrl', 'http://localhost:8405');
        $this->trankitService->init($trankitUrl);
    }

    /**
     * Process sentence through complete Daisy pipeline
     *
     * @param  DaisyInputData  $input  Input parameters
     * @return DaisyOutputData Disambiguation results with graph data
     */
    public function disambiguate(DaisyInputData $input): DaisyOutputData
    {
        // Initialize services with language-specific parameters
        $this->lexicalUnitService = new LexicalUnitService($input->idLanguage);
        $this->semanticNetworkService = new SemanticNetworkService(
            $input->idLanguage,
            $input->searchType,
            $input->level
        );
        $this->spreadingActivationService = new SpreadingActivationService;
        $this->winnerSelectionService = new WinnerSelectionService($input->gregnetMode);

        // Step 1: Parse sentence with Trankit (UD parsing)
        $udParsed = $this->parseWithTrankit($input->sentence, $input->idLanguage);

        // Step 2: Create GRID windows
        $gridResult = $this->gridService->processToWindows($udParsed);
        // debug($gridResult);
        $windows = $gridResult['windows'];
        $lemmas = $gridResult['lemmas'];
        //        debug("===========");
        //        debug($windows);
        //        debug("===========");

        // Step 3: Match lexical units
        $windows = $this->lexicalUnitService->matchLexicalUnits($windows, $lemmas);

        // Step 4: Build semantic networks
        $windows = $this->semanticNetworkService->buildSemanticNetworks($windows);

        // Step 5: Apply spreading activation
        $windows = $this->spreadingActivationService->processSpreadingActivation($windows);

        // Step 6: Select winners
        debug('=== DaisyService: Before generateWinners ===');
        debug('Windows count:', count($windows));
        debug('Windows type:', gettype($windows));

        // Debug: Check structure of first window
        if (! empty($windows)) {
            $firstWindowKey = array_key_first($windows);
            $firstWindow = $windows[$firstWindowKey];
            debug("First window key: {$firstWindowKey}, type:", gettype($firstWindow));

            if (is_array($firstWindow) && ! empty($firstWindow)) {
                $firstWordKey = array_key_first($firstWindow);
                $firstWordFrames = $firstWindow[$firstWordKey];
                debug("  First word: '{$firstWordKey}', type:", gettype($firstWordFrames), 'count:', is_countable($firstWordFrames) ? count($firstWordFrames) : 'N/A');

                if (is_array($firstWordFrames) && ! empty($firstWordFrames)) {
                    $firstFrameKey = array_key_first($firstWordFrames);
                    $firstFrame = $firstWordFrames[$firstFrameKey];
                    debug("    First frame: '{$firstFrameKey}', type:", gettype($firstFrame));
                    if (is_object($firstFrame)) {
                        debug('    Frame class:', get_class($firstFrame));
                        debug('    Frame has energy?', isset($firstFrame->energy), 'value =', $firstFrame->energy ?? 'N/A');
                        debug('    Frame has iword?', isset($firstFrame->iword));
                        debug('    Frame pool size:', is_array($firstFrame->pool) ? count($firstFrame->pool) : 'N/A');
                        if (! empty($firstFrame->pool)) {
                            debug('    Pool keys:', array_keys($firstFrame->pool));
                        } else {
                            debug('    WARNING: Pool is EMPTY - no semantic network built!');
                        }
                    }
                }
            }
        }

        $winnerResult = $this->winnerSelectionService->generateWinners($windows);

        debug('=== DaisyService: After generateWinners ===');
        debug('Winner result keys:', array_keys($winnerResult));
        debug('Winners count:', count($winnerResult['winners'] ?? []));
        debug('Weights count:', count($winnerResult['weights'] ?? []));
        $winners = $winnerResult['winners'];
        $weights = $winnerResult['weights'];

        // Format results
        $result = $this->winnerSelectionService->formatWinners($winners, $windows);

        // Generate graph visualization data
        $graph = $this->generateGraphData($windows, $winners, $udParsed);

        return new DaisyOutputData(
            result: $result,
            graph: $graph,
            sentenceUD: $udParsed,
            windows: $windows,
            weights: $weights
        );
    }

    /**
     * Parse sentence using Trankit
     */
    private function parseWithTrankit(string $sentence, int $idLanguage): array
    {
        // Use TrankitService to get UD parse
        $result = $this->trankitService->getUDTrankit($sentence, $idLanguage);

        return $result->udpipe ?? [];
    }

    /**
     * Generate graph data for visualization
     */
    private function generateGraphData(array $windows, array $winners, array $udParsed): array
    {
        $nodes = [];
        $links = [];

        // Step 1: Create word nodes from UD parse
        foreach ($udParsed as $wordData) {
            $wordId = 'word_'.$wordData['id'];
            $nodes[$wordId] = [
                'name' => $wordData['word'],
                'type' => 'word',
                'pos' => $wordData['pos'] ?? '',
                'shape' => 'ellipse',  // Different from frames
            ];
        }

        // Step 2: Create frame nodes from winners and evokes links
        foreach ($winners as $iword => $winnerFrames) {
            if (! empty($winnerFrames)) {
                foreach ($winnerFrames as $winner) {
                    $frameId = 'frame_'.$winner['frame'];

                    // Add frame node (only once even if multiple words evoke it)
                    if (! isset($nodes[$frameId])) {
                        $nodes[$frameId] = [
                            'name' => $winner['frame'],
                            'type' => 'frame',
                            'energy' => number_format($winner['value'], 3),  // 3 decimals
                            'shape' => 'rectangle',
                        ];
                    }

                    // Create evokes link (word → frame)
                    $wordId = 'word_'.$iword;
                    if (! isset($links[$wordId])) {
                        $links[$wordId] = [];
                    }
                    $links[$wordId][$frameId] = [
                        'relationEntry' => 'evokes',
                        'type' => 'wf',
                        'energy' => $winner['value'],
                    ];
                }
            }
        }

        // Step 3: Add immediate frame relations (only for winner frames)
        $winnerFrameNames = [];
        foreach ($winners as $winnerFrames) {
            foreach ($winnerFrames as $winner) {
                $winnerFrameNames[] = $winner['frame'];
            }
        }

        foreach ($windows as $idWindow => $words) {
            foreach ($words as $word => $frames) {
                foreach ($frames as $frameEntry => $frame) {
                    // Only process if this frame is a winner
                    if (! in_array($frameEntry, $winnerFrameNames)) {
                        continue;
                    }

                    $frameId = 'frame_'.$frameEntry;

                    // Add immediate pool relations only (level 1)
                    if (isset($frame->pool) && is_array($frame->pool)) {
                        foreach ($frame->pool as $poolFrameName => $poolObject) {
                            if ($poolFrameName === $frameEntry) {
                                continue;
                            } // Skip self
                            if ($poolObject->level > 1) {
                                continue;
                            } // Only immediate relations

                            $relatedFrameId = 'frame_'.$poolFrameName;

                            // Only add if related frame is also a winner
                            if (in_array($poolFrameName, $winnerFrameNames) && isset($nodes[$relatedFrameId])) {
                                if (! isset($links[$frameId])) {
                                    $links[$frameId] = [];
                                }
                                $links[$frameId][$relatedFrameId] = [
                                    'relationEntry' => 'related',
                                    'type' => 'ff',
                                    'factor' => $poolObject->factor,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
        ];
    }
}
