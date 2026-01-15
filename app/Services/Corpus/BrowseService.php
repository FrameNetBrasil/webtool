<?php

namespace App\Services\Corpus;

use App\Data\Corpus\SearchData;
use App\Database\Criteria;
use App\Services\AppService;

class BrowseService
{
    public static int $limit = 300;

    public static function browseAllCorpus(): array
    {
        $result = [];
        $corpus = Criteria::byFilterLanguage('view_corpus', ['name', 'startswith', ''])
            ->orderBy('name')
            ->limit(self::$limit)
            ->all();

        foreach ($corpus as $c) {
            $result[$c->idCorpus] = [
                'id' => $c->idCorpus,
                'type' => 'corpus',
                'text' => $c->name,
                'leaf' => false, // Corpus can be expanded to show documents
                'state' => 'closed',
            ];
        }

        return $result;
    }

    public static function browseCorpusBySearch(SearchData $search, bool $leaf = false): array
    {
        $result = [];
        if ($search->corpus != '') {
            $corpus = Criteria::byFilterLanguage('view_corpus', ['name', 'startswith', $search->corpus])
                ->orderBy('name')
                ->limit(self::$limit)
                ->all();

            foreach ($corpus as $c) {
                $result[$c->idCorpus] = [
                    'id' => $c->idCorpus,
                    'type' => 'corpus',
                    'text' => $c->name,
                    'leaf' => $leaf,
                    'state' => 'closed',
                ];
            }
        }

        return $result;
    }

    public static function browseDocumentBySearch(SearchData $search): array
    {
        $result = [];
        if ($search->document != '' && strlen($search->document) > 2) {
            $criteria = Criteria::byFilterLanguage('view_document', ['name', 'contains', $search->document])
                ->select('idDocument', 'name', 'corpusName', 'idCorpus')
                ->orderBy('corpusName')->orderBy('name')
                ->limit(self::$limit);

            if ($search->corpus != '') {
                $criteria = $criteria->where('corpusName', 'startswith', $search->corpus);
            }

            $documents = $criteria->all();

            foreach ($documents as $document) {
                $result[$document->idDocument] = [
                    'id' => $document->idDocument,
                    'type' => 'document',
                    'text' => $document->corpusName.' / '.$document->name,
                    'leaf' => true,
                    'state' => 'open',
                ];
            }
        }

        return $result;
    }

    public static function browseDocumentsByCorpus(SearchData $search): array
    {
        $result = [];
        $idCorpus = $search->idCorpus ?? (is_numeric($search->id) ? (int) $search->id : 0);

        if ($idCorpus > 0) {
            $documents = Criteria::table('view_document')
                ->select('idDocument', 'name', 'corpusName')
                ->where('idCorpus', $idCorpus)
                ->where('idLanguage', AppService::getCurrentIdLanguage())
                ->orderBy('name')
                ->limit(self::$limit)
                ->all();

            foreach ($documents as $document) {
                $result[$document->idDocument] = [
                    'id' => $document->idDocument,
                    'type' => 'document',
                    'text' => $document->name,
                    'leaf' => true,
                    'state' => 'open',
                ];
            }
        }

        return $result;
    }

    public static function browseCorpusDocumentBySearch(SearchData $search): array
    {
        $result = [];

        // Determine if we have a valid corpus ID
        $hasCorpusId = $search->idCorpus > 0 || (is_numeric($search->id) && (int) $search->id > 0);

        // Handle tree expansion: if type is 'corpus' and id is provided, return documents for that corpus
        if ($search->type === 'corpus' && $hasCorpusId) {
            $result = self::browseDocumentsByCorpus($search);
        }
        // If searching for specific corpus ID (legacy behavior), return its documents
        elseif ($hasCorpusId && $search->type === '') {
            $result = self::browseDocumentsByCorpus($search);
        } else {
            // If searching by document name, return matching documents
            if ($search->document != '') {
                $result = self::browseDocumentBySearch($search);
            } else {
                // If searching by corpus name, return filtered corpus
                if ($search->corpus != '') {
                    $result = self::browseCorpusBySearch($search);
                } else {
                    // Show all corpus by default
                    $result = self::browseAllCorpus();
                }
            }
        }

        return $result;
    }
}
