<?php

namespace App\Repositories;

use App\Data\LU\ReframingData;
use App\Data\LU\UpdateData;
use App\Database\Criteria;
use App\Services\AppService;
use Illuminate\Support\Facades\DB;

class LU
{
    public static function byId(int $id): object
    {
        $lu = Criteria::byFilter("view_lu", ['idLU', '=', $id])->first();
        $lu->frame = Frame::byId($lu->idFrame);
        return $lu;
    }

    public static function listForEvent(string $name = ''): array
    {
        $result = [];
        $idLanguage = AppService::getCurrentIdLanguage();
        $name = (strlen($name) > 2) ? $name : '-none';
        $criteria = Criteria::table("view_lu as lu")
            ->join("pos", "lu.idPOS", "=", "pos.idPOS")
            ->select('lu.idLU')
            ->selectRaw("concat(lu.frameName,'.',lu.name) as name")
            ->where("lu.idLanguage", "=", $idLanguage)
            ->where("pos.POS", "=", "V")
            ->whereRaw("upper(lu.name) LIKE upper('{$name}%')")
            ->orderBy('lu.frameName')
            ->orderBy("lu.name");
        $partial1 = $criteria->get()->keyBy('idLU')->all();
        foreach ($partial1 as $lu) {
            $result[] = $lu;
        }
        $criteria = Criteria::table("topframe")
            ->join("view_frame as frame", "topframe.frameBase", "=", "frame.entry")
            ->join("lu", "lu.idFrame", "=", "frame.idFrame")
            ->select('lu.idLU')
            ->selectRaw("concat(frame.name,'.',lu.name) as name")
            ->distinct()
            ->where('frameTop', 'NOT IN', ['frm_entity', 'frm_attributes'])
            ->where("frame.idLanguage", "=", $idLanguage)
            ->where("lu.name", "startswith", $name)
            ->orderBy('frame.name')
            ->orderBy('name');
        $partial2 = $criteria->get()->keyBy('idLU')->all();
        foreach ($partial2 as $lu) {
            if (!isset($partial1[$lu->idLU])) {
                $result[] = $lu;
            }
        }
        return $result;
    }

    public static function update(UpdateData $object)
    {
        Criteria::table("lu")
            ->where("idLU", "=", $object->idLU)
            ->update([
                'senseDescription' => $object->senseDescription,
                'incorporatedFE' => $object->incorporatedFE,
                'idFrame' => $object->idFrame
            ]);
    }

    public static function reframing(ReframingData $data): void
    {
        DB::transaction(function () use ($data) {
            debug($data);
            $lu = LU::byId($data->idLU);
            $exists = Criteria::table("lu")
                ->select("idLU","idEntity")
                ->where("idLemma",$lu->idLemma)
                ->where("idFrame",$data->idNewFrame)
                ->first();
            $alreadyExists = (!is_null($exists));
            if (!$alreadyExists) {
                Criteria::table("lu")
                    ->where("idLU", "=", $data->idLU)
                    ->update([
                        'senseDescription' => $data->senseDescription,
                        'incorporatedFE' => $data->incorporatedFE,
                        'idFrame' => $data->idNewFrame
                    ]);
            }
            if (!is_null($data->idEntityFE)) {
                foreach ($data->idEntityFE as $i => $idEntityFE) {
                    // recover the annotations for this LU/FE

                    $as = Criteria::table("view_annotationset as a")
                        ->where("a.idLU", $data->idLU)
                        ->select("a.idAnnotationSet")
                        ->all();
                    $idAS = collect($as)->pluck("idAnnotationSet")->toArray();
                    $annotations = Criteria::table("view_annotation_text_fe as afe")
                        ->whereIN("afe.idAnnotationSet", $idAS)
                        ->where("afe.idEntity", $idEntityFE)
                        ->distinct()
                        ->select("afe.idAnnotation","afe.idTextSpan")
                        ->all();
//                    $annotations = Criteria::table("view_frameelement as fe")
//                        ->join("view_annotation_text_fe as afe", "fe.idFrameElement", "=", "afe.idFrameElement")
//                        ->join("view_annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
//                        ->where("a.idLU", $data->idLU)
//                        ->where("afe.idLanguage", AppService::getCurrentIdLanguage())
//                        ->where("fe.idLanguage", AppService::getCurrentIdLanguage())
//                        ->select("afe.idAnnotation")
//                        ->all();
                    foreach ($annotations as $annotation) {
                        if ($data->changeToFE[$i] == -1) {
                            Criteria::deleteById("annotation", "idAnnotation", $annotation->idAnnotation);
                            Criteria::deleteById("textspan", "idTextSpan", $annotation->idTextSpan);
                        } else {
                            $fe = Criteria::byId("frameelement", "idFrameElement", $data->changeToFE[$i]);
                            Criteria::table("annotation")
                                ->where("idAnnotation", $annotation->idAnnotation)
                                ->update([
                                    "idEntity" => $fe->idEntity
                                ]);
                        }
                    }
                }
            }
            if ($alreadyExists) {
                // change the annotations from given LU to the existing LU and delete given LU
                $annotationSets = Criteria::table("annotationset as a")
                    ->select("a.idAnnotationSet")
                    ->where("a.idLU", $data->idLU)
                    ->all();
                foreach ($annotationSets as $annotationSet) {
                    debug($annotationSet->idAnnotationSet);
                    Criteria::table("annotationset")
                        ->where("idAnnotationSet", $annotationSet->idAnnotationSet)
                        ->update([
                            "idLU" => $exists->idLU,
                            "idEntityRelated" => $exists->idEntity
                        ]);
                }
                $annotations = Criteria::table("annotation as a")
                    ->select("a.idAnnotation")
                    ->where("a.idEntity", $lu->idEntity)
                    ->all();
                foreach ($annotations as $annotation) {
                    Criteria::table("annotation")
                        ->where("idAnnotation", $annotation->idAnnotation)
                        ->update([
                            "idEntity" => $exists->idEntity
                        ]);
                }
                Criteria::function('lu_delete(?, ?)', [
                    $data->idLU,
                    AppService::getCurrentIdUser()
                ]);
            }
        });
    }

}
