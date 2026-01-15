<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Enum\AnnotationSetStatus;
use App\Enum\Status;
use App\Services\AppService;
use \Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnnotationSet
{
    public static function listTargetsForDocumentSentence(array $idDocumentSentences): Collection
    {
        $idLanguage = AppService::getCurrentIdLanguage();
//        debug("docsen",$idDocumentSentences);
        return Criteria::table("view_annotationset as a")
            ->join("view_annotation_text_target as gl", "a.idAnnotationSet", "=", "gl.idAnnotationSet")
            ->join("lu","a.idLU","=", "lu.idLU")
            ->join("view_frame as f","lu.idFrame","=","f.idFrame")
            ->select('a.idDocumentSentence', 'gl.startChar', 'gl.endChar', 'a.idAnnotationSet','f.name as frameName','a.idLU')
            ->whereIn("a.idDocumentSentence", $idDocumentSentences)
            ->where("f.idLanguage", $idLanguage)
            ->orderby("gl.startChar")
            ->get();
    }

    public static function listTargetsForAnnotationSet(array $idAnnotationSet): Collection
    {
        $idLanguage = AppService::getCurrentIdLanguage();
//        debug("docsen",$idDocumentSentences);
        return Criteria::table("view_annotationset as a")
            ->join("view_annotation_text_target as gl", "a.idAnnotationSet", "=", "gl.idAnnotationSet")
            ->join("lu","a.idLU","=", "lu.idLU")
            ->join("view_frame as f","lu.idFrame","=","f.idFrame")
            ->select('a.idDocumentSentence', 'gl.startChar', 'gl.endChar', 'a.idAnnotationSet','f.name as frameName','a.idLU')
            ->whereIn("a.idAnnotationSet", $idAnnotationSet)
            ->where("f.idLanguage", $idLanguage)
            ->orderby("gl.startChar")
            ->get();
    }
    public static function getTargets(int $idDocumentSentence): array
    {
        return Criteria::table("view_annotationset as a")
            ->join("view_annotation_text_target as gl", "a.idAnnotationSet", "=", "gl.idAnnotationSet")
            ->select('a.idDocumentSentence', 'gl.startChar', 'gl.endChar', 'a.idAnnotationSet')
            ->where("a.idDocumentSentence", $idDocumentSentence)
            ->orderby("gl.startChar")
            ->all();
    }

    public static function getTargetsByIdSentence(int $idSentence): array
    {
        return Criteria::table("view_annotationset as a")
            ->join("view_annotation_text_target as gl", "a.idAnnotationSet", "=", "gl.idAnnotationSet")
            ->select('a.idSentence', 'gl.startChar', 'gl.endChar', 'a.idAnnotationSet')
            ->where("a.idSentence", $idSentence)
            ->orderby("gl.startChar")
            ->all();
    }

    public static function getTargetsByIdLU(int $idLU): array
    {
        return Criteria::table("view_annotationset as a")
            ->join("view_annotation_text_target as gl", "a.idAnnotationSet", "=", "gl.idAnnotationSet")
            ->join("view_sentence as s", "a.idSentence", "=", "s.idSentence")
            ->select('a.idSentence', 'gl.startChar', 'gl.endChar', 'a.idAnnotationSet','a.idDocumentSentence')
            ->where("a.idLU", $idLU)
            ->orderby("gl.startChar")
            ->all();
    }

    public static function getTargetsByIdAnnotationSet(int $idAnnotationSet): array
    {
        return Criteria::table("view_annotationset as a")
            ->join("view_annotation_text_target as gl", "a.idAnnotationSet", "=", "gl.idAnnotationSet")
            ->join("view_sentence as s", "a.idSentence", "=", "s.idSentence")
            ->select('a.idSentence', 'gl.startChar', 'gl.endChar', 'a.idAnnotationSet','a.idDocumentSentence')
            ->where("a.idAnnotationSet", $idAnnotationSet)
            ->orderby("gl.startChar")
            ->all();
    }

    public static function getWordsChars(string $text): object
    {
        $array = array();
        $punctuation = " .,;:?/'][\{\}\"!@#$%&*\(\)-_+=“”";
        mb_internal_encoding("UTF-8"); // this IS A MUST!! PHP has trouble with multibyte when no internal encoding is set!
        $last = mb_substr($text, -1);
        if (mb_strpos($punctuation, $last) === false) {
            $text .= '.';
        }
        $i = 0;
        for ($j = 0; $j < mb_strlen($text); $j++) {
            $char = mb_substr($text, $j, 1);
            $break = (mb_strpos($punctuation, $char) !== false);
            if ($break) {
                $word = mb_substr($text, $i, $j - $i);
                $array[$i] = $word;
                $array[$j] = $char;
                $i = $j + 1;
            }
        }
        $words = [];
        $chars = [];
        $order = 1;
        foreach ($array as $startChar => $wordForm) {
            $endChar = $startChar + mb_strlen($wordForm) - 1;
            $lWordForm = $wordForm;//str_replace("'", "\\'", $wordForm);
            $words[(string)$order] = [
                'order' => $order,
                'word' => $lWordForm,
                'startChar' => $startChar,
                'endChar' => $endChar,
                'isPunct' => (mb_strpos($punctuation, $lWordForm) !== false)
            ];
            for ($pos = (int)$startChar; $pos <= $endChar; $pos++) {
                $o = $pos - $startChar;
                $char = mb_substr($wordForm, $o, 1);
                $chars[$pos] = [
                    'offset' => (string)$o,
                    'char' => $char, // tf8_encode($wordForm{$o}), //str_replace("'", "\\'", $wordForm{$o}),
                    'order' => $order,
                    'isPunct' => (mb_strpos($punctuation, $char) !== false)
                ];
            }
            ++$order;
        }
        $wordsChars = new \StdClass();
        $wordsChars->words = $words;
        $wordsChars->chars = $chars;
        return $wordsChars;
    }

    public static function getLayers(int $idAnnotationSet): array
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $cmd = <<<HERE
select ts.idAnnotationSet,
       lt.layerOrder,
       lt.idLayerType,
       lt.entry as layerTypeEntry,
       lt.name as layerTypeName,
       coalesce(ts.startChar,-1) AS startChar,
       coalesce(ts.endChar,-1) AS endChar,
       coalesce(gl.idEntity, fe.idEntity, ce.idEntity) AS idEntity,
       coalesce(gl.name, fe.name, ce.name) AS name,
       coalesce(gl.idColor, fe.idColor, ce.idColor) AS idColor,
       ts.idTextSpan,
       ts.idInstantiationType,
       it.name instantiationType
from annotation a
join textspan ts on (a.idTextSpan = ts.idTextSpan)
join view_layertype lt on (ts.idLayerType = lt.idLayerType)
left join view_frameelement fe on (a.idEntity = fe.idEntity)
left join genericlabel gl on (a.idEntity = gl.idEntity)
left join view_constructionelement ce on (a.idEntity = ce.idEntity)
left join view_instantiationtype it ON (ts.idInstantiationType = it.idTypeInstance)
        WHERE (ts.idAnnotationSet = {$idAnnotationSet})
          and (lt.idLanguage = {$idLanguage})
          and (a.status <> 'DELETED')
            AND ((fe.idLanguage = {$idLanguage}) or (fe.idLanguage is null))
            AND ((gl.idLanguage = {$idLanguage}) or (gl.idLanguage is null))
            AND ((ce.idLanguage = {$idLanguage}) or (ce.idLanguage is null))
            AND ((it.idLanguage = {$idLanguage}) or (it.idLanguage is null))
        ORDER BY ts.idAnnotationSet, ts.idLayerType, ts.startChar
HERE;

        return DB::select($cmd);
    }

    public static function listFECEByAS(array|int $idAnnotationSet)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        if (!is_array($idAnnotationSet)) {
            $idAnnotationSet = [$idAnnotationSet];
        }
        $set = implode(',', $idAnnotationSet);
        $cmd = <<<HERE
 SELECT ds.idSentence, fece.startChar, fece.endChar, fece.idInstantiationType, fece.idColor, fece.name feName, fece.layerTypeEntry, it.name instantiationType
 from view_annotationset a
 join view_document_sentence ds on (a.idDocumentSentence = ds.idDocumentSentence)
left join (
     select idAnnotationSet, name, idColor, layerTypeEntry, startChar, endChar,idInstantiationType
     from view_annotation_text_fe afe
     where (idAnnotationSet IN ({$set})) and (idLanguage = 1)
     union
     select idAnnotationSet, name, idColor, layerTypeEntry, startChar, endChar,idInstantiationType
     from view_annotation_text_ce ace
     where (idAnnotationSet IN ({$set})) and  (idLanguage = {$idLanguage})
     union
     select idAnnotationSet, name, idColor, layerTypeEntry, startChar, endChar,idInstantiationType
     from view_annotation_text_gl agl
     where (idAnnotationSet IN ({$set})) and  (idLanguage = {$idLanguage}) and (layerTypeEntry = 'lty_target')
) fece on (a.idAnnotationSet = fece.idAnnotationSet)
left join view_instantiationtype it on (fece.idInstantiationType = it.idInstantiationType)
 where (a.idAnnotationSet IN ({$set}))
 and ((it.idLanguage = {$idLanguage}) or (it.idLanguage is null))
        ORDER BY ds.idSentence,fece.startChar

HERE;
        return collect(DB::select($cmd))->groupBy('idSentence')->all();
    }

    public static function listSentencesByAS(int|array $idAnnotationSet)
    {
        $criteria = Criteria::table("view_annotationset as a")
            ->join("view_document_sentence as ds", "a.idDocumentSentence", "=", "ds.idDocumentSentence")
            ->join("view_sentence as s", "ds.idSentence", "=", "s.idSentence")
            ->select('a.idAnnotationSet', 'ds.idDocumentSentence', 's.idSentence', 's.text');
        if (is_array($idAnnotationSet)) {
            $criteria->where('idAnnotationSet', 'IN', $idAnnotationSet);
        } else {
            $criteria->where('idAnnotationSet', '=', $idAnnotationSet);
        }
        $criteria->orderBy('idAnnotationSet');
//        $criteria->where('entries.idLanguage','=', AppService::getCurrentIdLanguage());
        return $criteria->all();
    }

    public static function createForLU(int $idDocumentSentence, int $idLU, int $startChar, int $endChar): ?int
    {
        DB::beginTransaction();
        try {
            $idUser = AppService::getCurrentIdUser();
            $documentSentence = Criteria::byId("view_document_sentence","idDocumentSentence",$idDocumentSentence);
            $lu = Criteria::byFilter("view_lu_full", ['idLU', '=', $idLU])->first();
            $lu->frame = Frame::byId($lu->idFrame);
            $ti = Criteria::byId("typeinstance", "entry", AnnotationSetStatus::UNANNOTATED->value);
            $annotationSet = [
                //'idAnnotationObjectRelation' => $idDocumentSentence,
                'idDocumentSentence' => $idDocumentSentence,
                'idEntityRelated' => $lu->idEntity,
                'idLU' => $lu->idLU,
                'idAnnotationStatus' => $ti->idTypeInstance,
                'idUser' => $idUser,
                'status' => Status::CREATED->value
            ];
            $idAnnotationSet = Criteria::create("annotationset", $annotationSet);
            Timeline::addTimeline('annotationset',$idAnnotationSet,'C');

            // versão 4.2: bypassing layer - using layerType
            $ti = Criteria::byId("typeinstance", "entry", 'int_normal');
            $idLayerType = Criteria::byId("layertype","entry", 'lty_target')->idLayerType;
            $target = Criteria::table("genericlabel")
                ->where("name", "Target")
                ->where("idLanguage", AppService::getCurrentIdLanguage())
                ->first();
            $textspan = json_encode([
                'startChar' => $startChar,
                'endChar' => $endChar,
                'multi' => 0,
                'idInstantiationType' => $ti->idTypeInstance,
                'idLayerType' => $idLayerType,
                'idAnnotationSet' => $idAnnotationSet,
                'idSentence' => $documentSentence->idSentence,
            ]);
            $idTextSpan = Criteria::function("textspan_char_create(?)", [$textspan]);
            $data = json_encode([
                'idTextSpan' => $idTextSpan,
                'idEntity' => $target->idEntity,
                'idUser' => $idUser
            ]);
            Criteria::function("annotation_create(?)", [$data]);
            DB::commit();
            return $idAnnotationSet;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception($e->getMessage());
        }

    }

    public static function createForFlex(int $idDocumentSentence): object
    {
        DB::beginTransaction();
        try {
            $idUser = AppService::getCurrentIdUser();
            $ti = Criteria::byId("typeinstance", "entry", AnnotationSetStatus::UNANNOTATED->value);
            $annotationSet = [
                'idDocumentSentence' => $idDocumentSentence,
                'idAnnotationStatus' => $ti->idTypeInstance,
                'idUser' => $idUser,
                'status' => Status::CREATED->value
            ];
            $idAnnotationSet = Criteria::create("annotationset", $annotationSet);
            Timeline::addTimeline('annotationset',$idAnnotationSet,'C');
            $layerTypes = ['lty_phrasal_ce','lty_clausal_ce','lty_sentential_ce'];
            foreach($layerTypes as $layerType) {
                $idLayerType = Criteria::byId("layertype", "entry", $layerType)->idLayerType;
                Criteria::create("layer", [
                    'rank' => 0,
                    'idLayerType' => $idLayerType,
                    'idAnnotationSet' => $idAnnotationSet,
                ]);
            }
            DB::commit();
            return Criteria::byId("annotationSet","idAnnotationSet", $idAnnotationSet);
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception($e->getMessage());
        }

    }
    public static function delete(int $idAnnotationSet): int
    {
        DB::beginTransaction();
        try {
            $idAnnotationSet = Criteria::function("annotationset_delete(?,?)",[$idAnnotationSet, AppService::getCurrentIdUser()]);
            DB::commit();
            return $idAnnotationSet;
        } catch (\Exception $e) {
            DB::rollback();
            debug($e->getMessage());
            throw new \Exception("Operation denied. Check if AS has spans or comments.");
        }
    }

    public static function updateStatusField(int $idAnnotationSet, string $status): void {
        Criteria::table("annotationset")
            ->where('idAnnotationSet', $idAnnotationSet)
            ->update(['status' => $status]);
    }

    public static function updateAST(int $idAnnotationSet, string $astEntry): object {
        $ast = Criteria::table("view_annotationset_status")
            ->where('entry', $astEntry)
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->first();
        Criteria::table("annotationset")
            ->where('idAnnotationSet', $idAnnotationSet)
            ->update(['idAnnotationStatus' => $ast->idTypeInstance]);
        return $ast;
    }
    public static function updateStatus(object $annotationSet, array $annotations, array $feCore): object {

        $feAnnotated = [];
        foreach($annotations['nis'] ?? [] as $niFEs) {
            foreach($niFEs as $niFE) {
                $feAnnotated[$niFE->idEntity] = $niFE;
            }
        }
        foreach($annotations['lty_fe'] ?? [] as $fes) {
            foreach($fes as $fe) {
                $feAnnotated[$fe->idEntity] = $fe;
            }
        }
        $match = 0;
        foreach($feCore as $idEntityFE) {
            if(isset($feAnnotated[$idEntityFE])) {
                ++$match;
            }
        }
        $result = AnnotationSetStatus::UNANNOTATED;
        if ($match >= count($feCore)) {
            $result = AnnotationSetStatus::COMPLETE;
        } else if ($match > 0) {
            $result = AnnotationSetStatus::PARTIAL;
        }
        $ast = self::updateAST($annotationSet->idAnnotationSet, $result->value);
        return $ast ?? (object)[];
    }


}
