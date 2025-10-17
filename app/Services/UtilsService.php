<?php

namespace App\Services;

use App\Data\Utils\ImportFullTextData;
use App\Database\Criteria;

class UtilsService
{

    public static function importFullText(ImportFullTextData $data): void
    {
        $idLanguage = $data->idLanguage;
        try {
            $paragraphNum = $sentenceNum = 0;
            $filename = $data->file->getRealPath();//    //storeAs('texts', $fileName);
            debug($filename);
//            $filename = (is_object($file) ? $file->getTmpName() : $file);
            $rows = file($filename);
            foreach ($rows as $row) {
                debug($row);
                $row = str_replace("\t", " ", $row);
                $row = str_replace("\n", " ", $row);
                $row = trim($row);
                if ($row == '') {
                    continue;
                }
//                $paragraph = $this->createParagraph(++$paragraphNum); // cada linha do arquivo é um paragrafo
                $words = preg_split('/ /', $row);
                $wordsSize = count($words);
                if ($wordsSize == 0) {
                    continue;
                }
                $text = ''; // texto de cada sentença
//                // $break = false;
                foreach ($words as $word) {
                    if ($word == '$START') {
                        continue;
                    }
                    $word = str_replace('"', "'", str_replace('<', '', str_replace('>', '', str_replace('=', ' ', str_replace('$', '', $word)))));
                    $text .= $word;
                    if (preg_match("/\.|\?|!/", $word)) { // quebra de sentença
                    } else {
                        $text .= ' ';
                    }
                }
                if (trim($text) != '') {
                    $newSentence = json_encode([
                        'text' => $text,
                        'idDocument' => $data->idDocument,
                        'idLanguage' => $data->idLanguage,
                        'idUser' => $data->idUser,
                    ]);
                    $idSentence = Criteria::function("sentence_create(?)", [$newSentence]);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Error importing fulltext. " . $e->getMessage());
        }

    }

}
