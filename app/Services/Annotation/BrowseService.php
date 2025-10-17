<?php

namespace App\Services\Annotation;

use App\Database\Criteria;
use App\Repositories\AnnotationSet;
use App\Repositories\Project;
use App\Services\AppService;

class BrowseService
{
    public static function hasTimespan(int $idDocument): bool
    {
        $timespan = Criteria::table('document_sentence as ds')
            ->join('view_sentence_timespan as ts', 'ds.idSentence', '=', 'ts.idSentence')
            ->where('ds.idDocument', $idDocument)
            ->first();

        return !is_null($timespan);
    }

    public static function getRowNumber(int $idDocument, int $idDocumentSentence): int
    {
        $sentences = Criteria::table('sentence')
            ->join('document_sentence as ds', 'sentence.idSentence', '=', 'ds.idSentence')
            ->join('view_sentence_timespan as ts', 'ds.idSentence', '=', 'ts.idSentence')
            ->where('ds.idDocument', $idDocument)
            ->selectRaw('ROW_NUMBER() OVER (order by `ts`.`startTime` asc, `ds`.`idDocumentSentence` asc) AS `rowNumber`, ds.idDocumentSentence')
            ->keyBy('idDocumentSentence')
            ->all();

        return $sentences[$idDocumentSentence]->rowNumber;
    }
    /**
     * Versão 4.2
     */

    public static function browseCorpusDocumentBySearch(object $search, array $projects = [], string $taskGroup = '')
    {
        $corpusIcon = view('components.icon.corpus')->render();
        $documentIcon = view('components.icon.document')->render();
        $data = [];

        $allowed = Project::getAllowedDocsForUser($projects, $taskGroup);
        $allowedCorpus = collect($allowed)->pluck('idCorpus')->all();
        $allowedDocuments = collect($allowed)->pluck('idDocument')->all();
        if ($search->document == '') {
            $corpus = Criteria::byFilterLanguage("view_corpus", ["name", "startswith", $search->corpus])
                ->whereIn("idCorpus", $allowedCorpus)
                ->orderBy("name")->get()->keyBy("idCorpus")->all();
            $ids = array_keys($corpus);
            $documents = Criteria::byFilterLanguage("view_document", ["idCorpus", "IN", $ids])
                ->whereIn("idDocument", $allowedDocuments)
                ->orderBy("name")
                ->get()->groupBy("idCorpus")
                ->toArray();
            foreach ($corpus as $c) {
                $children = array_map(fn($item) => [
                    'id' => $item->idDocument,
                    'text' => $documentIcon . $item->name,
                    'state' => 'closed',
                    'type' => 'document'
                ], $documents[$c->idCorpus] ?? []);
                $data[] = [
                    'id' => $c->idCorpus,
                    'text' => $corpusIcon . $c->name,
                    'state' => 'closed',
                    'type' => 'corpus',
                    'children' => $children
                ];
            }
        } else {
            $documents = Criteria::byFilterLanguage("view_document", ["name", "startswith", $search->document])
                ->select('idDocument', 'name', 'corpusName')
                ->whereIn("idDocuments", $allowedDocuments)
                ->orderBy("corpusName")->orderBy("name")->all();
            $data = array_map(fn($item) => [
                'id' => $item->idDocument,
                'text' => $documentIcon . $item->corpusName . ' / ' . $item->name,
                'state' => 'closed',
                'type' => 'document'
            ], $documents);
        }
        return $data;
    }

    /**
     * Versão 4.2
     */
    private static function getIdDocument(int $idDocumentSentence): int
    {
        return Criteria::table('document_sentence')
            ->where('idDocumentSentence', $idDocumentSentence)
            ->pluck('idDocument')[0];
    }

    public static function decorateSentenceTarget($text, $spans): string
    {
        $decorated = '';
        $ni = '';
        $i = 0;
        foreach ($spans as $span) {
            // $style = 'background-color:#' . $label['rgbBg'] . ';color:#' . $label['rgbFg'] . ';';
            if ($span->startChar >= 0) {
                $decorated .= mb_substr($text, $i, $span->startChar - $i);
                $decorated .= "<span class='color_target'>" . mb_substr($text, $span->startChar, $span->endChar - $span->startChar + 1) . '</span>';
                $i = $span->endChar + 1;
            }
        }
        $decorated = $decorated . mb_substr($text, $i);

        return $decorated;
    }
    public static function getPrevious(int $idDocumentSentence): ?int
    {
        $idDocument = self::getIdDocument($idDocumentSentence);
        $hasTimespan = self::hasTimespan($idDocument);
        if ($hasTimespan) {
            $rowNumber = self::getRowNumber($idDocument, $idDocumentSentence);
            $sentences = Criteria::table('sentence')
                ->join('document_sentence as ds', 'sentence.idSentence', '=', 'ds.idSentence')
                ->join('view_sentence_timespan as ts', 'ds.idSentence', '=', 'ts.idSentence')
                ->where('ds.idDocument', $idDocument)
                ->selectRaw('ROW_NUMBER() OVER (order by `ts`.`startTime` asc, `ds`.`idDocumentSentence` asc) AS `rowNumber`, ds.idDocumentSentence')
                ->keyBy('rowNumber')
                ->all();

            return isset($sentences[$rowNumber - 1]) ? $sentences[$rowNumber - 1]->idDocumentSentence : null;
        } else {
            $i = Criteria::table('document_sentence')
                ->where('idDocument', '=', $idDocument)
                ->where('idDocumentSentence', '<', $idDocumentSentence)
                ->max('idDocumentSentence');

            return $i ?? null;
        }
    }

    public static function getNext(int $idDocumentSentence): ?int
    {
        $idDocument = self::getIdDocument($idDocumentSentence);
        $hasTimespan = self::hasTimespan($idDocument);
        if ($hasTimespan) {
            $rowNumber = self::getRowNumber($idDocument, $idDocumentSentence);
            $sentences = Criteria::table('sentence')
                ->join('document_sentence as ds', 'sentence.idSentence', '=', 'ds.idSentence')
                ->join('view_sentence_timespan as ts', 'ds.idSentence', '=', 'ts.idSentence')
                ->where('ds.idDocument', $idDocument)
                ->selectRaw('ROW_NUMBER() OVER (order by `ts`.`startTime` asc, `ds`.`idDocumentSentence` asc) AS `rowNumber`, ds.idDocumentSentence')
                ->keyBy('rowNumber')
                ->all();

            return isset($sentences[$rowNumber + 1]) ? $sentences[$rowNumber + 1]->idDocumentSentence : null;
        } else {
            $i = Criteria::table('document_sentence')
                ->where('idDocument', '=', $idDocument)
                ->where('idDocumentSentence', '>', $idDocumentSentence)
                ->min('idDocumentSentence');

            return $i ?? null;
        }
    }

    public static function getPreviousNext(int $idDocumentSentence): object
    {
        $idDocument = self::getIdDocument($idDocumentSentence);
        $hasTimespan = self::hasTimespan($idDocument);
        if ($hasTimespan) {
            $rowNumber = self::getRowNumber($idDocument, $idDocumentSentence);
            $sentences = Criteria::table('sentence')
                ->join('document_sentence as ds', 'sentence.idSentence', '=', 'ds.idSentence')
                ->join('view_sentence_timespan as ts', 'ds.idSentence', '=', 'ts.idSentence')
                ->where('ds.idDocument', $idDocument)
                ->selectRaw('ROW_NUMBER() OVER (order by `ts`.`startTime` asc, `ds`.`idDocumentSentence` asc) AS `rowNumber`, ds.idDocumentSentence')
                ->keyBy('rowNumber')
                ->all();
            $previous = isset($sentences[$rowNumber - 1]) ? $sentences[$rowNumber - 1]->idDocumentSentence : null;
            $next = isset($sentences[$rowNumber + 1]) ? $sentences[$rowNumber + 1]->idDocumentSentence : null;
        } else {
            $previous = Criteria::table('document_sentence')
                ->where('idDocument', '=', $idDocument)
                ->where('idDocumentSentence', '<', $idDocumentSentence)
                ->max('idDocumentSentence') ?? null;
            $next = Criteria::table('document_sentence')
                ->where('idDocument', '=', $idDocument)
                ->where('idDocumentSentence', '>', $idDocumentSentence)
                ->min('idDocumentSentence') ?? null;
        }

        return (object)[
            'previous' => $previous,
            'next' => $next,
        ];
    }

    public static function browseCorpusBySearch(object $search, array $projects = [], string $taskGroupName = '', bool $leaf = false): array
    {
        $corpusIcon = view('components.icon.corpus')->render();
        $data = [];
        debug("browseCorpusBySearch", $taskGroupName);
        $allowed = Project::getAllowedDocsForUser($projects, $taskGroupName);
        $allowedCorpus = array_keys(collect($allowed)->groupBy('idCorpus')->toArray());
        $corpus = Criteria::byFilterLanguage('view_corpus', ['name', 'startswith', $search->corpus])
            ->whereIn('idCorpus', $allowedCorpus)
            ->orderBy('name')->all();
        foreach ($corpus as $c) {
            $data[] = [
                'id' => $c->idCorpus,
                'text' => $corpusIcon . $c->name,
                'type' => 'corpus',
                'leaf' => $leaf,
            ];
        }

        return $data;
    }

    public static function browseDocumentsByCorpus(int $idCorpus, array $projects = [], string $taskGroupName = '', bool $leaf = false): array
    {
        $allowed = Project::getAllowedDocsForUser($projects, $taskGroupName, $idCorpus);
        $allowedDocuments = collect($allowed)->pluck('idDocument')->all();
        $documents = Criteria::table('view_document')
            ->select('idDocument', 'name as document', 'corpusName')
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->whereIn('idDocument', $allowedDocuments)
            ->orderBy('corpusName')->orderBy('name')->all();
        $data = array_map(fn($item) => [
            'id' => $item->idDocument,
            'text' => view('Annotation.partials.document', (array)$item)->render(),
            'type' => 'document',
            'leaf' => $leaf,
        ], $documents);
        return $data;
    }

    public static function browseDocumentBySearch(object $search, array $projects = [], string $taskGroupName = '', bool $leaf = false): array
    {
        $documentIcon = view('components.icon.document')->render();
        $allowed = Project::getAllowedDocsForUser($projects, $taskGroupName);
        if ($search->document != '') {
            $data = [];
            if (strlen($search->document) > 2) {
                // $allowedCorpus = array_keys(collect($allowed)->groupBy('idCorpus')->toArray());
                $allowedDocuments = array_keys(
                    collect($allowed)
                        ->groupBy('idDocument')
                        ->toArray()
                );
                $documents = Criteria::byFilterLanguage('view_document', ['name', 'contains', $search->document])
                    ->select('idDocument', 'name', 'corpusName', 'idCorpus')
                    ->orderBy('corpusName')->orderBy('name')->all();
                foreach ($documents as $document) {
                    if ((isset($allowedDocuments[$document->idDocument]))) {
                        $data[] = [
                            'id' => $document->idDocument,
                            'text' => $documentIcon . $document->corpusName . ' / ' . $document->name,
                            'type' => 'document',
                            'leaf' => $leaf,
                        ];
                    }
                }
            }
        } elseif ($search->idCorpus != '') {
            $documentsByCorpus = (collect($allowed)->groupBy('idCorpus')->toArray())[$search->idCorpus];
            $allowedDocuments = collect($documentsByCorpus)->pluck('idDocument')->all();
            $documents = Criteria::byFilterLanguage('view_document', ['name', 'startswith', $search->document])
                ->select('idDocument', 'name', 'corpusName')
                ->whereIn('idDocument', $allowedDocuments)
                ->orderBy('corpusName')->orderBy('name')->all();
            $data = array_map(fn($item) => [
                'id' => $item->idDocument,
                'text' => $documentIcon . $item->name,
                'type' => 'document',
                'leaf' => $leaf,
            ], $documents);
        }

        return $data;
    }

    public static function browseSentences(array $sentences): array
    {
        $data = [];
        foreach ($sentences as $sentence) {
            $data[] = [
                'id' => $sentence->idDocumentSentence,
                'formatedId' => '[#' . $sentence->idDocumentSentence . ']',
                'extra' => (isset($sentence->startTime) ? '<span class="text-time"><i class="material icon">schedule</i>' . $sentence->startTime . '</span>' : ''),
                'text' => $sentence->text,
                'type' => 'sentence',
                'leaf' => true,
            ];
        }

        return $data;
    }

    public static function browseSentence(int $idDocumentSentence): array
    {
        return self::browseSentences(self::getSentence($idDocumentSentence));
    }

    public static function browseSentencesByDocument(int $idDocument): array
    {
        return self::browseSentences(self::listSentences($idDocument));
    }

    public static function getSentence(int $idDocumentSentence): array
    {
        $document = Criteria::table('document_sentence as ds')
            ->where('ds.idDocumentSentence', $idDocumentSentence)
            ->first();
        $idDocument = $document->idDocument;
        $hasTimespan = self::hasTimespan($idDocument);
        if ($hasTimespan) {
            $sentences = Criteria::table('sentence')
                ->join('document_sentence as ds', 'sentence.idSentence', '=', 'ds.idSentence')
                ->join('view_sentence_timespan as ts', 'ds.idSentence', '=', 'ts.idSentence')
                ->join('document as d', 'ds.idDocument', '=', 'd.idDocument')
                ->where('d.idDocument', $idDocument)
                ->where('ds.idDocumentSentence', $idDocumentSentence)
                ->select('sentence.idSentence', 'sentence.text', 'ds.idDocumentSentence', 'ts.startTime', 'ts.endTime')
                ->orderBy('ts.startTime')
                ->orderBy('ds.idDocumentSentence')
                ->limit(1000)
                ->get()->keyBy('idDocumentSentence')->all();
        } else {
            $sentences = Criteria::table('sentence')
                ->join('document_sentence as ds', 'sentence.idSentence', '=', 'ds.idSentence')
                ->join('document as d', 'ds.idDocument', '=', 'd.idDocument')
                ->where('d.idDocument', $idDocument)
                ->where('ds.idDocumentSentence', $idDocumentSentence)
                ->select('sentence.idSentence', 'sentence.text', 'ds.idDocumentSentence')
                ->orderBy('ds.idDocumentSentence')
                ->limit(1000)
                ->get()->keyBy('idDocumentSentence')->all();
        }
        if (!empty($sentences)) {
            $targets = collect(AnnotationSet::listTargetsForDocumentSentence(array_keys($sentences)))->groupBy('idDocumentSentence')->toArray();
            foreach ($targets as $idDocumentSentence => $spans) {
                $sentences[$idDocumentSentence]->text = self::decorateSentenceTarget($sentences[$idDocumentSentence]->text, $spans);
            }
        }

        return $sentences;
    }

    public static function listSentences(int $idDocument): array
    {
        $hasTimespan = self::hasTimespan($idDocument);
        if ($hasTimespan) {
            $sentences = Criteria::table('view_sentence as s')
                ->join('view_document_sentence as ds', 's.idSentence', '=', 'ds.idSentence')
                ->join('view_sentence_timespan as ts', 'ds.idSentence', '=', 'ts.idSentence')
                ->join('document as d', 'ds.idDocument', '=', 'd.idDocument')
                ->where('d.idDocument', $idDocument)
                ->select('s.idSentence', 's.text', 'ds.idDocumentSentence', 'ts.startTime', 'ts.endTime')
                ->orderBy('ts.startTime')
                ->orderBy('ds.idDocumentSentence')
                ->limit(1000)
                ->get()->keyBy('idDocumentSentence')->all();
        } else {
            $sentences = Criteria::table('view_sentence as s')
                ->join('view_document_sentence as ds', 's.idSentence', '=', 'ds.idSentence')
                ->join('document as d', 'ds.idDocument', '=', 'd.idDocument')
                ->where('d.idDocument', $idDocument)
                ->select('s.idSentence', 's.text', 'ds.idDocumentSentence')
                ->orderBy('ds.idDocumentSentence')
                ->limit(1000)
                ->get()->keyBy('idDocumentSentence')->all();
        }
        if (!empty($sentences)) {
            if ( session("corpusAnnotationType") != 'cxn') {
                $targets = collect(AnnotationSet::listTargetsForDocumentSentence(array_keys($sentences)))->groupBy('idDocumentSentence')->toArray();
//            debug($targets);
                foreach ($targets as $idDocumentSentence => $spans) {
                    $sentences[$idDocumentSentence]->text = self::decorateSentenceTarget($sentences[$idDocumentSentence]->text, $spans);
                }
            }
        }

        return $sentences;
    }
}
