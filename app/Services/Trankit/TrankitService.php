<?php

namespace App\Services\Trankit;

use GuzzleHttp\Client;

class TrankitService
{
    private string $url;

    public function init(string $url)
    {
        $this->url = $url;
    }

    private function getClient()
    {
        return new Client([
            'base_uri' => $this->url,
            'timeout' => 300.0,
        ]);
    }

    public function handlePunct(string $sentence): string
    {
        $tokens = explode(' ', $sentence);
        $processed = [];
        foreach ($tokens as $token) {
            $output_array = [];
            if (preg_match('/\d(\d*[,|\.]\d+)+\d/', $token, $output_array)) {
                $processed[] = str_replace($output_array[0], ' '.$output_array[0].' ', $token);
            } else {
                $processed[] = str_replace(['.', ',', '-', ':', ';', '?', '!'], [' . ', ' , ', ' - ', ' : ', ' ; ', ' ? ', ' ! '], $token);
            }
        }
        $sentence = implode(' ', $processed);
        while (str_contains($sentence, '  ')) {
            $sentence = str_replace('  ', ' ', $sentence);
        }

        return trim($sentence);
    }

    public function handleContractions(string $sentence): string
    {
        $fileName = __DIR__.'/files/contractions.php';
        // $fileName = "../../Offline/ud/contractions.php";
        $contractions = include $fileName;
        $tokens = explode(' ', $sentence);
        $words = [];
        foreach ($tokens as $token) {
            if (isset($contractions[$token])) {
                $words[] = $contractions[$token][0].' '.$contractions[$token][1];
            } else {
                $words[] = $token;
            }
        }

        return implode(' ', $words);
    }

    /**
     * Normalize hyphens to commas for better UD parser recognition
     *
     * The UD parser doesn't always recognize hyphens as punctuation.
     * This method replaces standalone hyphens with commas before parsing.
     *
     * Examples:
     * - "word - word" → "word , word"
     * - "dash-separated" → "dash-separated" (preserves compound words)
     *
     * @param  string  $sentence  Input sentence
     * @return string Sentence with normalized punctuation
     */
    public function normalizeHyphens(string $sentence): string
    {
        // Replace standalone hyphens (with spaces around them) with commas
        // Pattern: space + hyphen + space → space + comma + space
        $sentence = preg_replace('/\s+-\s+/', ' , ', $sentence);

        // Also handle hyphen at start/end with only one space
        $sentence = preg_replace('/^-\s+/', ', ', $sentence);
        $sentence = preg_replace('/\s+-$/', ' ,', $sentence);

        return $sentence;
    }

    public function handleSentence(string $sentence): string
    {
        $sentence = $this->normalizeHyphens($sentence);
        $sentence = $this->handlePunct($sentence);
        $sentence = $this->handleContractions($sentence);

        return $sentence;
    }

    public function parseSentenceRaw(string $sentence, int $idLanguage = 1)
    {
        $grapher = (object) [
            'nodes' => [],
            'links' => [],
        ];
        $result = $this->getUDTrankit($sentence, $idLanguage);

        return $result->udpipe;
    }

    public function parseSentenceFilled(string $sentence, int $idLanguage = 1)
    {
        //        mdump(':: parseSentenceFilled');
        $sentence = $this->fillSentence($sentence);
        $result = $this->getUDTrankit($sentence, $idLanguage);

        //        mdump($result);
        return $result->udpipe;
    }

    public function parseSentenceRCN(string $sentence, int $idLanguage = 1)
    {
        $sentence = $this->fillSentence($sentence);
        print_r($sentence."\n");
        $result = $this->getUDTrankit($sentence, $idLanguage);

        return $result->udpipe;
    }

    public function parseSentence(string $sentence, int $idLanguage = 1)
    {
        $pos = [
            'NOUN' => 1,
            'PROPN' => 1,
            'VERB' => 1,
            'AUX' => 1,
            'ADJ' => 1,
            'PRON' => 1,
            'CCONJ' => 1,
            'SCONJ' => 1,
            'PUNCT' => 1,
        ];
        $fwords = include_once realpath(dirname(__DIR__, 2).'/Offline/ud/function_words.php');
        $grapher = (object) [
            'nodes' => [],
            'links' => [],
        ];
        //        $initialResult = $this->getUDTrankit($sentence, $idLanguage);
        //        $nodes = $initialResult->udpipe;
        //        $filtered = [];
        //        foreach($nodes as $node) {
        //            $word = $node['word'];
        //            if (isset($fwords[strtolower($word)])) {
        //                if (isset($pos[$node['pos']])) {
        //                    $filtered[] = $word;
        //                }
        //            } else {
        //                $filtered[] = $word;
        //            }
        //
        //        }
        //        $sentence = implode(' ', $filtered);
        $tokens = explode(' ', $sentence);
        $result = $this->getUDTrankitTokens($tokens, $idLanguage);
        $nodes = $result->udpipe;
        $changed = true;
        while ($changed) {
            $changed = false;
            $count = count($nodes);
            foreach ($nodes as $id => $node) {
                if ($node['rel'] == 'conj') {
                    $parent = $node['parent'];
                    $nodeParent = $nodes[$parent];
                    if ($nodeParent['pos'] != 'SET') {
                        $isset = false;
                        $nodeParentParent = $nodes[$nodeParent['parent']] ?? null;
                        if ($nodeParentParent) {
                            //                            mdump($id . ' - ' . $nodeParent['id'] . ' - ' . $nodeParentParent['id'] . ' - ' . $nodeParentParent['pos']);
                            if ($nodeParentParent['pos'] == 'SET') {
                                $nodes[$id]['parent'] = $nodeParentParent['id'];
                                $isset = true;
                            }
                        }
                        if (! $isset) {
                            $nodeSet = [
                                'id' => $count,
                                'word' => 'SET',
                                'pos' => 'SET',
                                'parent' => $nodeParent['parent'],
                                'rel' => $nodeParent['rel'],
                            ];
                            $nodes[$id]['parent'] = $count;
                            $nodes[$parent]['parent'] = $count;
                            $nodes[$parent]['rel'] = 'conj';
                            $nodes[$count] = $nodeSet;
                            $changed = true;
                            break;
                        }
                    }
                }
            }
            //            if ($changed) {
            //                mdump('changed');
            //                $nodes = $new;
            //            }
            //            break;
        }
        //        mdump($nodes);
        foreach ($nodes as $node) {
            $grapher->nodes[] = [
                'id' => $node['id'],
                'name' => $node['word']."  [{$node['pos']}]"."  [{$node['rel']}]",
            ];
        }
        foreach ($nodes as $link) {
            if ($link['parent'] > 0) {
                $grapher->links[] = [
                    'id' => ($link['id'] * 1000) + $link['parent'],
                    'source' => $link['id'],
                    'target' => $link['parent'],
                    'relation' => 'rel_dependency',
                ];
            }
        }

        return $grapher;
    }

    public function parseSentenceRawTokens(string $sentence, int $idLanguage = 1)
    {
        $result = $this->getUDTrankit($sentence, $idLanguage, true);

        return $result->udpipe;
    }

    public function processTrankit($sentence, $idLanguage = 1)
    {
        // Normalize hyphens to commas for better UD parser recognition
        $sentence = $this->normalizeHyphens($sentence);

        debug($sentence);
        $client = $this->getClient();
        try {
            //            mdump('calling trankit ' . time());
            $model = [
                1 => 'portuguese',
                2 => 'english',
            ];

            $response = $client->request('post', 'tkparser', [
                'headers' => [
                    // 'Accept' => 'application/hal+json',
                    'Accept' => 'application/text',
                ],
                'body' => json_encode([
                    'articles' => [
                        ['text' => $sentence],
                    ],
                    'tokens' => [],
                    'model' => $model[$idLanguage],
                ]),
            ]);
            //            mdump('called trankit ' . time());

            $body = json_decode($response->getBody());

            // debug($body);
            return $body->result->sentences[0];
        } catch (\Exception $e) {
            debug($e->getMessage());

            return '';
        }
    }

    public function processTrankitTokens($tokens, $idLanguage = 1)
    {
        // debug($sentence);
        $client = $this->getClient();
        try {
            //            mdump('calling trankit ' . time());
            $model = [
                1 => 'portuguese',
                2 => 'english',
            ];

            debug(json_encode([
                'articles' => [],
                'tokens' => $tokens,
                'model' => $model[$idLanguage],
            ]));

            $response = $client->request('post', 'tkbytoken', [
                'headers' => [
                    // 'Accept' => 'application/hal+json',
                    'Accept' => 'application/text',
                ],
                'body' => json_encode([
                    'articles' => [],
                    'tokens' => $tokens,
                    'model' => $model[$idLanguage],
                ]),
            ]);

            //            mdump('called trankit ' . time());

            $body = json_decode($response->getBody());

            //            debug($body);
            return $body->result;
        } catch (\Exception $e) {
            //            mdump($e->getMessage());
            return '';
        }
    }

    public function getUDTrankit($sentence, $idLanguage = 1, $tokens = false)
    {
        try {
            $ud = [];
            if ($tokens) {
                $tokens = explode(' ', $this->handlePunct($sentence));
                foreach ($tokens as $i => $token) {
                    $tokens[$i] = str_replace('_', ' ', $token);
                }
                $result = $this->processTrankitTokens($tokens, $idLanguage);
            } else {
                $result = $this->processTrankit($sentence, $idLanguage);
            }
            //            debug($result);
            // debug($result);
            // mdump($result->result->sentences[0]->tokens);
            $array = [];
            // $dict = $result->result->sentences[0]->tokens;
            $dict = $result->tokens;
            foreach ($dict as $node) {
                if (isset($node->expanded)) {
                    foreach ($node->expanded as $expanded) {
                        $array[] = $expanded;
                    }
                } else {
                    $array[] = $node;
                }
            }
            $parent = [];
            $children = [];
            foreach ($array as $j => $node) {
                $id = $node->id;
                $head = $node->head;
                if ($id != $head) {
                    $parent[$id] = $head;
                    $children[$head][] = $id;
                }

            }
            foreach ($array as $j => $node) {
                $feats = [];
                if (isset($node->feats)) {
                    $f = explode('|', $node->feats);
                    foreach ($f as $f0) {
                        [$feat, $value] = explode('=', $f0);
                        $feats[$feat] = $value;
                    }
                }
                $ud[$j + 1] = [
                    'id' => $node->id,
                    'word' => $node->text,
                    'pos' => $node->upos,
                    'ud' => '',
                    'morph' => $feats,
                    'lemma' => $node->lemma ?? '',
                    'rel' => $node->deprel,
                    'parent' => $parent[$node->id] ?? 0,
                    'children' => $children[$node->id] ?? [],
                ];
            }

            return (object) ['udpipe' => $ud];
        } catch (\Exception $e) {
            debug($e->getMessage());

            return (object) ['udpipe' => []];
        }
    }

    /**
     * Tokenize a plain sentence into an array of tokens.
     *
     * This method converts a plain text sentence into an array of tokens
     * that can be passed to the /tkbytoken API endpoint. It handles:
     * - Punctuation separation
     * - Contraction expansion (optional based on config)
     * - Hyphen normalization
     *
     * @param  string  $sentence  The plain text sentence to tokenize
     * @param  bool  $expandContractions  Whether to expand contractions (default: true)
     * @return array Array of token strings
     */
    public function tokenizeSentence(string $sentence, bool $expandContractions = false): array
    {
        // Normalize hyphens first
        $sentence = $this->normalizeHyphens($sentence);

        // Handle punctuation
        $sentence = $this->handlePunct($sentence);

        // Handle contractions if requested
        if ($expandContractions) {
            $sentence = $this->handleContractions($sentence);
        }

        // Split into tokens and clean up
        $tokens = explode(' ', $sentence);
        $tokens = array_filter($tokens, fn ($token) => trim($token) !== '');

        return array_values($tokens);
    }

    public function getUDTrankitTokens($tokens, $idLanguage = 1)
    {
        try {
            $ud = [];
            $result = $this->processTrankitTokens($tokens, $idLanguage);
            // API response structure: {result: {tokens: [...], lang: "portuguese"}}
            $array = [];
            $dict = $result->tokens;
            foreach ($dict as $node) {
                if (isset($node->expanded)) {
                    foreach ($node->expanded as $expanded) {
                        $array[] = $expanded;
                    }
                } else {
                    $array[] = $node;
                }
            }
            $parent = [];
            $children = [];
            foreach ($array as $j => $node) {
                $id = $node->id;
                $head = $node->head;
                if ($id != $head) {
                    $parent[$id] = $head;
                    $children[$head][] = $id;
                }

            }
            foreach ($array as $j => $node) {
                $feats = [];
                if (isset($node->feats)) {
                    $f = explode('|', $node->feats);
                    foreach ($f as $f0) {
                        if (str_contains($f0, '=')) {
                            [$feat, $value] = explode('=', $f0);
                            $feats[$feat] = $value;
                        }
                    }
                }
                $ud[$j + 1] = [
                    'id' => $node->id,
                    'word' => $node->text,
                    'pos' => $node->upos,
                    'ud' => '',
                    'morph' => $feats,
                    'lemma' => $node->lemma ?? '',
                    'rel' => $node->deprel,
                    'parent' => $parent[$node->id] ?? 0,
                    'children' => $children[$node->id] ?? [],
                ];
            }

            return (object) ['udpipe' => $ud];
        } catch (\Exception $e) {
            return (object) ['udpipe' => []];
        }
    }

    /**
     * Get UD parse from pre-tokenized input, preserving original tokens in output.
     *
     * Unlike getUDTrankitTokens() which expands contractions (e.g., "pelo" -> "por" + "o"),
     * this method preserves the original token text while still providing full dependency info.
     *
     * This is critical for MWE detection after parsing because:
     * 1. MWEs are stored with original forms (e.g., "pelo menos", not "por o menos")
     * 2. Dependency relations are needed to disambiguate MWE candidates
     *
     * Example:
     * Tokens: ["pelo", "menos"]
     * - getUDTrankitTokens() returns: [{word: "por", ...}, {word: "o", ...}, {word: "menos", ...}]
     * - getUDTrankitTokensPreserved() returns: [{word: "pelo", deprel: ..., head: ...}, {word: "menos", ...}]
     *
     * @param  array  $tokens  Pre-tokenized array of strings (with contractions preserved)
     * @param  int  $idLanguage  Language ID (1=Portuguese, 2=English)
     * @return object Object with 'udpipe' array containing token data
     */
    public function getUDTrankitTokensPreserved(array $tokens, int $idLanguage = 1): object
    {
        try {
            $ud = [];
            $result = $this->processTrankitTokens($tokens, $idLanguage);
            // API response structure: {result: {tokens: [...], lang: "portuguese"}}
            $array = [];
            $dict = $result->tokens;
            foreach ($dict as $node) {
                // Do NOT expand contractions - keep the original text
                // This allows proper MWE (Multi-Word Expression) identification
                if (isset($node->expanded)) {
                    // For contracted tokens, use the original text
                    // but we need to create a simplified node structure
                    $contractedNode = (object) [
                        'id' => is_array($node->id) ? $node->id[0] : $node->id,
                        'text' => $node->text, // This is the original contracted form like "pelo"
                        'upos' => $node->expanded[0]->upos ?? '', // Use first expanded token's POS
                        'lemma' => $node->text, // Keep original as lemma for MWE matching
                        'deprel' => $node->expanded[0]->deprel ?? '',
                        'head' => $node->expanded[0]->head ?? 0,
                        'feats' => $node->expanded[0]->feats ?? '',
                    ];
                    $array[] = $contractedNode;
                } else {
                    $array[] = $node;
                }
            }
            $parent = [];
            $children = [];
            foreach ($array as $j => $node) {
                $id = $node->id;
                $head = $node->head;
                if ($id != $head) {
                    $parent[$id] = $head;
                    $children[$head][] = $id;
                }

            }
            foreach ($array as $j => $node) {
                $feats = [];
                if (isset($node->feats)) {
                    $f = explode('|', $node->feats);
                    foreach ($f as $f0) {
                        if (str_contains($f0, '=')) {
                            [$feat, $value] = explode('=', $f0);
                            $feats[$feat] = $value;
                        }
                    }
                }
                $ud[$j + 1] = [
                    'id' => $node->id,
                    'word' => $node->text,
                    'pos' => $node->upos,
                    'ud' => '',
                    'morph' => $feats,
                    'lemma' => $node->lemma ?? '',
                    'rel' => $node->deprel,
                    'parent' => $parent[$node->id] ?? 0,
                    'children' => $children[$node->id] ?? [],
                ];
            }

            return (object) ['udpipe' => $ud];
        } catch (\Exception $e) {
            return (object) ['udpipe' => []];
        }
    }

    /**
     * Get UD parse from Trankit preserving original text of contractions.
     *
     * This method differs from getUDTrankit() by NOT expanding contractions.
     * For example, "pelo" remains as "pelo" instead of being split into "por" + "o".
     *
     * This is crucial for correct MWE (Multi-Word Expression) identification,
     * as MWEs like "pelo menos" need the original contracted form to be recognized
     * in the database. If contractions are expanded first, MWE matching fails.
     *
     * Use this method BEFORE MWE processing, then use standard getUDTrankit()
     * after MWE identification for full syntactic analysis.
     *
     * Example:
     * Sentence: "O carro atropelou pelo menos 5 pessoas."
     * - getUDTrankit() returns: [..., "por", "o", "menos", ...]  // MWE "pelo menos" not found
     * - getUDTrankitText() returns: [..., "pelo", "menos", ...]  // MWE "pelo menos" found!
     *
     * @param  string  $sentence  The sentence to parse
     * @param  int  $idLanguage  Language ID (1=Portuguese, 2=English)
     * @return object Object with 'udpipe' array containing token data
     */
    public function getUDTrankitText(string $sentence, int $idLanguage = 1): object
    {
        try {
            $ud = [];
            $result = $this->processTrankit($sentence, $idLanguage);
            // Process tokens without expanding contractions
            // This preserves the original text like "pelo" instead of splitting to "por" + "o"
            $array = [];
            $dict = $result->tokens;
            foreach ($dict as $node) {
                // Do NOT expand contractions - keep the original text
                // This allows proper MWE (Multi-Word Expression) identification
                if (isset($node->expanded)) {
                    // For contracted tokens, use the original text
                    // but we need to create a simplified node structure
                    $contractedNode = (object) [
                        'id' => is_array($node->id) ? $node->id[0] : $node->id,
                        'text' => $node->text, // This is the original contracted form like "pelo"
                        'upos' => $node->expanded[0]->upos ?? '', // Use first expanded token's POS
                        'lemma' => $node->text, // Keep original as lemma for MWE matching
                        'deprel' => $node->expanded[0]->deprel ?? '',
                        'head' => $node->expanded[0]->head ?? 0,
                        'feats' => $node->expanded[0]->feats ?? '',
                    ];
                    $array[] = $contractedNode;
                } else {
                    $array[] = $node;
                }
            }
            $parent = [];
            $children = [];
            foreach ($array as $j => $node) {
                $id = $node->id;
                $head = $node->head;
                if ($id != $head) {
                    $parent[$id] = $head;
                    $children[$head][] = $id;
                }

            }
            foreach ($array as $j => $node) {
                $feats = [];
                if (isset($node->feats)) {
                    $f = explode('|', $node->feats);
                    foreach ($f as $f0) {
                        if (str_contains($f0, '=')) {
                            [$feat, $value] = explode('=', $f0);
                            $feats[$feat] = $value;
                        }
                    }
                }
                $ud[$j + 1] = [
                    'id' => $node->id,
                    'word' => $node->text, // Original text without expansion
                    'pos' => $node->upos,
                    'ud' => '',
                    'morph' => $feats,
                    'lemma' => $node->lemma ?? '',
                    'rel' => $node->deprel,
                    'parent' => $parent[$node->id] ?? 0,
                    'children' => $children[$node->id] ?? [],
                ];
            }

            return (object) ['udpipe' => $ud];
        } catch (\Exception $e) {
            debug($e->getMessage());

            return (object) ['udpipe' => []];
        }
    }
}
