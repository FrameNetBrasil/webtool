<?php

namespace App\Services\Trankit;

use GuzzleHttp\Client;

class TrankitService
{
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
                $processed[] = str_replace($output_array[0], ' ' . $output_array[0] . ' ', $token);
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
        $fileName = __DIR__ . "/files/contractions.php";
        //$fileName = "../../Offline/ud/contractions.php";
        $contractions = include $fileName;
        $tokens = explode(' ', $sentence);
        $words = [];
        foreach ($tokens as $token) {
            if (isset($contractions[$token])) {
                $words[] = $contractions[$token][0] . ' ' . $contractions[$token][1];
            } else {
                $words[] = $token;
            }
        }
        return implode(' ', $words);
    }

    public function handleSentence(string $sentence): string
    {
        $sentence = $this->handlePunct($sentence);
        $sentence = $this->handleContractions($sentence);
        return $sentence;
    }

    public function parseSentenceRaw(string $sentence, int $idLanguage = 1)
    {
        $grapher = (object)[
            'nodes' => [],
            'links' => []
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
        print_r($sentence . "\n");
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
            'PUNCT' => 1
        ];
        $fwords = include_once realpath(dirname(__DIR__, 2) . "/Offline/ud/function_words.php");
        $grapher = (object)[
            'nodes' => [],
            'links' => []
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
                        if (!$isset) {
                            $nodeSet = [
                                'id' => $count,
                                'word' => 'SET',
                                'pos' => 'SET',
                                'parent' => $nodeParent['parent'],
                                'rel' => $nodeParent['rel']
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
                'name' => $node['word'] . "  [{$node['pos']}]" . "  [{$node['rel']}]"
            ];
        }
        foreach ($nodes as $link) {
            if ($link['parent'] > 0) {
                $grapher->links[] = [
                    'id' => ($link['id'] * 1000) + $link['parent'],
                    'source' => $link['id'],
                    'target' => $link['parent'],
                    'relation' => 'rel_dependency'
                ];
            }
        }
        return $grapher;
    }

    public function parseSentenceRawTokens(string $sentence, int $idLanguage = 1)
    {
        $result = $this->getUDTrankit($sentence, $idLanguage,true);
        return $result->udpipe;
    }

    public function processTrankit($sentence, $idLanguage = 1)
    {
        debug($sentence);
        $client = $this->getClient();
        try {
//            mdump('calling trankit ' . time());
            $model = [
                1 => "portuguese",
                2 => "english"
            ];

            $response = $client->request('post', 'tkparser', [
                'headers' => [
                    //'Accept' => 'application/hal+json',
                    'Accept' => 'application/text',
                ],
                'body' => json_encode([
                    'articles' => [
                        ['text' => $sentence]
                    ],
                    'tokens' => [],
                    "model" => $model[$idLanguage]
                ])
            ]);
//            mdump('called trankit ' . time());

            $body = json_decode($response->getBody());
            //debug($body);
            return $body->result->sentences[0];
        } catch (\Exception $e) {
            debug($e->getMessage());
            return '';
        }
    }

    public function processTrankitTokens($tokens, $idLanguage = 1)
    {
        //debug($sentence);
        $client = $this->getClient();
        try {
//            mdump('calling trankit ' . time());
            $model = [
                1 => "portuguese",
                2 => "english"
            ];

            debug(json_encode([
                'articles' => [
                    ['text' => '']
                ],
                'tokens' => $tokens,
                "model" => $model[$idLanguage]
            ]));

            $response = $client->request('post', 'tkbytoken', [
                'headers' => [
                    //'Accept' => 'application/hal+json',
                    'Accept' => 'application/text',
                ],
                'body' => json_encode([
                    'articles' => [
                        ['text' => '']
                    ],
                    'tokens' => $tokens,
                    "model" => $model[$idLanguage]
                ])
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
                foreach($tokens as $i => $token) {
                    $tokens[$i] = str_replace('_', ' ', $token);
                }
                $result = $this->processTrankitTokens($tokens, $idLanguage);
            } else {
                $result = $this->processTrankit($sentence, $idLanguage);
            }
//            debug($result);
            //debug($result);
            //mdump($result->result->sentences[0]->tokens);
            $array = [];
            //$dict = $result->result->sentences[0]->tokens;
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
                    'children' => $children[$node->id] ?? []
                ];
            }
            return (object)['udpipe' => $ud];
        } catch (\Exception $e) {
            debug($e->getMessage());
            return (object)['udpipe' => []];
        }
    }

    public function getUDTrankitTokens($tokens, $idLanguage = 1)
    {
        try {
            $ud = [];
            $result = $this->processTrankitTokens($tokens, $idLanguage);
            //mdump($result->result->sentences[0]->tokens);
            $array = [];
            $dict = $result->result->tokens;
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
                $ud[$j + 1] = [
                    'id' => $node->id,
                    'word' => $node->text,
                    'pos' => $node->upos,
                    'ud' => '',
                    'morph' => $node->feats ?? '',
                    'rel' => $node->deprel,
                    'parent' => $parent[$node->id] ?? 0,
                    'children' => $children[$node->id] ?? []
                ];
            }
            return (object)['udpipe' => $ud];
        } catch (\Exception $e) {
            return (object)['udpipe' => []];
        }
    }

}
