<?php

namespace App\Services\Sentence;

use App\Data\Sentence\SearchData;
use App\Database\Criteria;
use App\Services\AppService;

class BrowseService
{
    public static int $limit = 300;

    public static function browseSentenceBySearch(SearchData $search, bool $leaf = false): array
    {
        $result = [];
        $sentences = Criteria::table('view_sentence as s')
            ->join("document_sentence as ds","ds.idSentence","=","s.idSentence")
            ->leftJoin("view_document as d","ds.idDocument","=","d.idDocument")
            ->where('s.text', 'contains', $search->sentence)
            ->where("d.idLanguage","left", AppService::getCurrentIdLanguage())
            ->orderBy('s.idSentence','desc')
            ->limit(self::$limit)
            ->select("s.idSentence", "s.text", "d.idDocument", "d.name as documentName","d.corpusName")
            ->all();
        foreach ($sentences as $s) {
            $result[$s->idSentence] = [
                'id' => $s->idSentence,
                'type' => 'sentence',
                'text' => $s->text,
                'documentName' => $s->corpusName . '/' . $s->documentName,
            ];
        }
        return $result;
    }

}
