<?php

namespace App\Services;

use App\Data\Frame\UpdateClassificationData;
use App\Data\Relation\CreateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Frame;
use App\Repositories\Relation;
use App\Repositories\SemanticType;

class RelationService extends Controller
{
//    public static function delete(int $id)
//    {
//        Relation::delete($id);
//    }
//
//    public static function newRelation(CreateData $data): ?int
//    {
//        return Relation::save($data);
//    }

    static public function create(string $relationTypeEntry, int $idEntity1, int $idEntity2, ?int $idEntity3 = null, ?int $idRelation = null): ?int
    {
        $user = AppService::getCurrentUser();
        $data = json_encode([
            'relationType' => $relationTypeEntry,
            'idEntity1' => $idEntity1,
            'idEntity2' => $idEntity2,
            'idEntity3' => $idEntity3 ?? null,
            'idRelation' => $idRelation ?? null,
            'idUser' => $user ? $user->idUser : 0
        ]);
        return Criteria::function('relation_create(?)', [$data]);
    }

    static public function createMicroframe(string $microframeName, int $idEntityDomain, int $idEntityRange): ?int
    {
        $user = AppService::getCurrentUser();
        $microframe = Criteria::table("view_microframe as mf")
            ->where("mf.idLanguage", "=", AppService::getCurrentIdLanguage())
            ->where("mf.name", "=", $microframeName)
            ->first();
        $data = json_encode([
            'relationType' => 'rel_microframe',
            'idEntity1' => $idEntityDomain,
            'idEntity2' => $idEntityRange,
            'idEntityMicroframe' => $microframe->idEntity,
            'idRelation' => null,
            'idUser' => $user ? $user->idUser : 0
        ]);
        return Criteria::function('relation_create(?)', [$data]);
    }

    public static function listRelationsFrame(int $idFrame)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        //$config = config('webtool.relations');
        $result = [];
        $relations = Criteria::table("view_frame_relation")
            ->where("f1IdFrame", $idFrame)
            ->where("idLanguage", $idLanguage)
            ->orderBy("relationType")
            ->orderBy("f2Name")
            ->all();
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameDirect,
                'color' => $relation->color,
                'idFrameRelated' => $relation->f2IdFrame,
                'related' => $relation->f2Name,
                'direction' => 'direct'
            ];
        }
        $inverse = Criteria::table("view_frame_relation")
            ->where("f2IdFrame", $idFrame)
            ->where("idLanguage", $idLanguage)
            ->all();
        foreach ($inverse as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameInverse,
                'color' => $relation->color,
                'idFrameRelated' => $relation->f1IdFrame,
                'related' => $relation->f1Name,
                'direction' => 'inverse'
            ];
        }
        return $result;
    }

    public static function listRelationsFEInternal(int $idFrame)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        //$config = config('webtool.relations');
        $relations = Criteria::table("view_fe_internal_relation")
            ->where("fe1IdFrame", $idFrame)
            ->where("idLanguage", $idLanguage)
            ->all();
        $result = [];
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'feIdFrameElement' => $relation->fe1IdFrameElement,
                'feName' => $relation->fe1Name,
                'feIdColor' => $relation->fe1IdColor,
                'feCoreType' => $relation->fe1CoreType,
                'relatedFEIdFrameElement' => $relation->fe2IdFrameElement,
                'relatedFEName' => $relation->fe2Name,
                'relatedFEIdColor' => $relation->fe2IdColor,
                'relatedFECoreType' => $relation->fe2CoreType,
                'name' => $relation->nameDirect,
                'color' => $relation->color,
            ];
        }
        return $result;
    }

    public static function listRelationsFE(int $idEntityRelationBase)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        //$config = config('webtool.relations');
        $relations = Criteria::table("view_fe_relation")
            ->where("idRelation", $idEntityRelationBase)
            ->where("idLanguage", $idLanguage)
            ->all();
        $result = [];
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'entry' => $relation->relationType,
                'relationName' => $relation->nameDirect,
                'color' => $relation->color,
                'feName' => $relation->fe1Name,
                'feCoreType' => $relation->fe1CoreType,
                'feIdColor' => $relation->fe1IdColor,
                'feIdEntity' => $relation->fe1IdEntity,
                'relatedFEName' => $relation->fe2Name,
                'relatedFECoreType' => $relation->fe2CoreType,
                'relatedFEIdColor' => $relation->fe2IdColor,
                'relatedFEIdEntity' => $relation->fe2IdEntity,
            ];
        }
        return $result;
    }

    public static function listFrameChildren(int $idFrame)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $result = [];
        $relations = Criteria::table("view_frame_relation as fr")
            ->join("view_frame as f", "fr.f2IdFrame", "=", "f.idFrame")
            ->select("fr.idEntityRelation", "fr.relationType", "f.idFrame", "f.name", "f.description")
            ->where("fr.f1IdFrame", $idFrame)
            ->where("fr.idLanguage", $idLanguage)
            ->where("f.idLanguage", $idLanguage)
            ->orderBy("f.name")
            ->all();
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'idFrame' => $relation->idFrame,
                'name' => $relation->name,
                'description' => $relation->description,
            ];
        }
        return $result;
    }

    public static function listFEST(int $idFrame)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $relations = Criteria::table("view_relation as r")
            ->join("view_frameelement as fe", "r.idEntity1", "=", "fe.idEntity")
            ->join("view_semantictype as st", "r.idEntity2", "=", "st.idEntity")
            ->select("fe.idFrameElement", "fe.name", "st.name")
            ->where("fe.idFrame", $idFrame)
            ->where("fe.idLanguage", $idLanguage)
            ->where("st.idLanguage", $idLanguage)
            ->orderBy("fe.idFrameElement")
            ->keyBy("idFrameElement")
            ->all();
        return $relations;
    }

    public static function listFERestrictions(int $idFrame)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $relations = Criteria::table("view_relation as r")
            ->join("view_frameelement as fe", "r.idEntity2", "=", "fe.idEntity")
            ->join("view_frameelement as fet", "r.idEntity3", "=", "fet.idEntity")
            ->join("view_class as cl", "fet.idFrame", "=", "cl.idFrame")
            ->select("fe.idFrameElement", "fe.name", "cl.name")
            ->where("fe.idFrame", $idFrame)
            ->where("fe.idLanguage", $idLanguage)
            ->where("fet.idLanguage", $idLanguage)
            ->where("cl.idLanguage", $idLanguage)
            ->orderBy("fe.idFrameElement")
            ->keyBy("idFrameElement")
            ->all();
        return $relations;
    }

    public static function listRelationsClass(int $idFrame)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        //$config = config('webtool.relations');
        $result = [];
        $relations = Criteria::table("view_class_relation")
            ->where("c1IdFrame", $idFrame)
            ->where("idLanguage", $idLanguage)
            ->orderBy("relationType")
            ->orderBy("c2Name")
            ->all();
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameDirect,
                'color' => $relation->color,
                'idFrameRelated' => $relation->c2IdFrame,
                'related' => $relation->c2Name,
                'direction' => 'direct'
            ];
        }
        $inverse = Criteria::table("view_class_relation")
            ->where("c2IdFrame", $idFrame)
            ->where("idLanguage", $idLanguage)
            ->all();
        foreach ($inverse as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameDirect,
                'color' => $relation->color,
                'idFrameRelated' => $relation->c1IdFrame,
                'related' => $relation->c1Name,
                'direction' => 'inverse'
            ];
        }
        return $result;
    }

    public static function listClassAsRestriction(int $idFrame)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        //$config = config('webtool.relations');
        $result = [];
        $relations = Criteria::table("view_class_fe_relation")
            ->where("c2IdFrame", $idFrame)
            ->where("idLanguage", $idLanguage)
            ->where("fe1Name","<>", "Target") // classes relations
            ->orderBy("relationType")
            ->orderBy("c1Name")
            ->all();
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameDirect,
                'color' => $relation->color,
                'idFrameRelated' => $relation->c1IdFrame,
                'related' => $relation->c1Name,
                'direction' => 'direct'
            ];
        }
        return $result;
    }

    public static function listRelationsMicroframe(int $idFrame)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        //$config = config('webtool.relations');
        $result = [];
        $relations = Criteria::table("view_microframe_relation")
            ->where("c1IdFrame", $idFrame)
            ->where("idLanguage", $idLanguage)
            ->orderBy("relationType")
            ->orderBy("c2Name")
            ->all();
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameDirect,
                'color' => $relation->color,
                'idFrameRelated' => $relation->c2IdFrame,
                'related' => $relation->c2Name,
                'direction' => 'direct'
            ];
        }
        $inverse = Criteria::table("view_microframe_relation")
            ->where("c2IdFrame", $idFrame)
            ->where("idLanguage", $idLanguage)
            ->all();
        foreach ($inverse as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameDirect,
                'color' => $relation->color,
                'idFrameRelated' => $relation->c1IdFrame,
                'related' => $relation->c1Name,
                'direction' => 'inverse'
            ];
        }
        return $result;
    }

    public static function updateFramalDomain(UpdateClassificationData $data)
    {
        $frame = Frame::byId($data->idFrame);
        $relationType = Criteria::byId("relationtype", "entry", "rel_framal_domain");
        try {
            Criteria::table("entityrelation")
                ->where("idEntity1", $frame->idEntity)
                ->where("idRelationType", $relationType->idRelationType)
                ->delete();
            foreach ($data->framalDomain as $idSemanticType) {
                $st = SemanticType::byId($idSemanticType);
                self::create("rel_framal_domain", $frame->idEntity, $st->idEntity);
            }
        } catch (\Exception $e) {
            throw new \Exception("Error updating relations. " . $e);
        }
    }

    public static function updateFramalType(UpdateClassificationData $data)
    {
        $frame = Frame::byId($data->idFrame);
        $relationType = Criteria::byId("relationtype", "entry", "rel_framal_type");
        try {
            Criteria::table("entityrelation")
                ->where("idEntity1", $frame->idEntity)
                ->where("idRelationType", $relationType->idRelationType)
                ->delete();
            foreach ($data->framalType as $idSemanticType) {
                $st = SemanticType::byId($idSemanticType);
                self::create("rel_framal_type", $frame->idEntity, $st->idEntity);
            }
        } catch (\Exception $e) {
            throw new \Exception("Error updating relations. " . $e);
        }
    }

    public static function updateFramalNamespace(UpdateClassificationData $data)
    {
        $frame = Frame::byId($data->idFrame);
        $relationType = Criteria::byId("relationtype", "entry", "rel_namespace");
        try {
            Criteria::table("entityrelation")
                ->where("idEntity1", $frame->idEntity)
                ->where("idRelationType", $relationType->idRelationType)
                ->delete();
            foreach ($data->namespace as $idSemanticType) {
                $st = SemanticType::byId($idSemanticType);
                self::create("rel_namespace", $frame->idEntity, $st->idEntity);
            }
        } catch (\Exception $e) {
            throw new \Exception("Error updating relations. " . $e);
        }
    }

    /*
     * Cxn
     */
    public static function listRelationsCxn(int $idConstruction)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $result = [];
        $relations = Criteria::table("view_construction_relation")
            ->where("c1IdConstruction", $idConstruction)
            ->where("idLanguage", $idLanguage)
            ->orderBy("relationType")
            ->orderBy("c2Name")
            ->all();
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameDirect,
                'color' => $relation->color,
                'idCxnRelated' => $relation->c2IdConstruction,
                'related' => $relation->c2Name,
                'direction' => 'direct'
            ];
        }
        $inverse = Criteria::table("view_construction_relation")
            ->where("c2IdConstruction", $idConstruction)
            ->where("idLanguage", $idLanguage)
            ->all();
        foreach ($inverse as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameInverse,
                'color' => $relation->color,
                'idCxnRelated' => $relation->c1IdConstruction,
                'related' => $relation->c1Name,
                'direction' => 'inverse'
            ];
        }
        return $result;
    }

    public static function listRelationsCE(int $idEntityRelationBase)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $relations = Criteria::table("view_constructionelement_relation")
            ->where("idRelation", $idEntityRelationBase)
            ->where("idLanguage", $idLanguage)
            ->all();
        $result = [];
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'entry' => $relation->relationType,
                'relationName' => $relation->nameDirect,
                'color' => $relation->color,
                'ceName' => $relation->ce1Name,
                'ceIdColor' => $relation->ce1IdColor,
                'ceIdEntity' => $relation->ce1IdEntity,
                'relatedCEName' => $relation->ce2Name,
                'relatedCEIdColor' => $relation->ce2IdColor,
                'relatedCEIdEntity' => $relation->ce2IdEntity,
            ];
        }
        return $result;
    }

    /*
 * Graph
 */

    public static function listFrameRelationsForGraph(array $idArray, array $idRelationType)
    {
        $nodes = [];
        $links = [];
        debug($idRelationType);
        $idLanguage = AppService::getCurrentIdLanguage();
        foreach ($idArray as $idEntity) {
            $partial = Criteria::table("view_relation as r")
                ->join("view_frame as f1", "r.idEntity1", "=", "f1.idEntity")
                ->join("view_frame as f2", "r.idEntity2", "=", "f2.idEntity")
                ->select('r.idEntityRelation', 'r.idRelationType', 'r.relationType', 'r.entity1Type', 'r.entity2Type', 'r.idEntity1', 'r.idEntity2',
                    'f1.nsName as frame1Name',
                    'f2.nsName as frame2Name',
                    'f1.idColor as frame1IdColor',
                    'f2.idColor as frame2IdColor',
                )->where('f1.idLanguage', '=', $idLanguage)
                ->where('f2.idLanguage', '=', $idLanguage)
                ->whereRaw("((r.idEntity1 = {$idEntity}) or (r.idEntity2 = {$idEntity}))")
                ->all();
            foreach ($partial as $r) {
                if (in_array($r->idRelationType, $idRelationType)) {
                    $nodes[$r->idEntity1] = [
                        'type' => 'frame',
                        'name' => $r->frame1Name,
                        'idColor' => $r->frame1IdColor,
                    ];
                    $nodes[$r->idEntity2] = [
                        'type' => 'frame',
                        'name' => $r->frame2Name,
                        'idColor' => $r->frame2IdColor,
                    ];
                    $links[$r->idEntity1][$r->idEntity2] = [
                        'type' => 'ff',
                        'idEntityRelation' => $r->idEntityRelation,
                        'relationEntry' => $r->relationType,
                    ];
                }
            }
        }
        return [
            'nodes' => $nodes,
            'links' => $links
        ];
    }

    public static function listFrameFERelationsForGraph(int $idEntityRelation)
    {
        $nodes = [];
        $links = [];
        $baseRelation = Relation::byId($idEntityRelation);
        $icon = config('webtool.fe.icon');
        $relations = self::listRelationsFE($idEntityRelation);
        foreach ($relations as $relation) {
            $nodes[$relation->feIdEntity] = [
                'type' => 'fe',
                'name' => $relation->feName,
                'icon' => $icon[$relation->feCoreType],
                'idColor' => $relation->feIdColor
            ];
            $nodes[$relation->relatedFEIdEntity] = [
                'type' => 'fe',
                'name' => $relation->relatedFEName,
                'icon' => $icon[$relation->relatedFECoreType],
                'idColor' => $relation->relatedFEIdColor
            ];
            $links[$baseRelation->idEntity1][$relation->feIdEntity] = [
                'type' => 'ffe',
                'idEntityRelation' => $idEntityRelation,
                'relationEntry' => 'rel_has_element',
            ];
            $links[$relation->relatedFEIdEntity][$baseRelation->idEntity2] = [
                'type' => 'ffe',
                'idEntityRelation' => $idEntityRelation,
                'relationEntry' => 'rel_has_element',
            ];
            $links[$relation->feIdEntity][$relation->relatedFEIdEntity] = [
                'type' => 'fefe',
                'idEntityRelation' => $relation->idEntityRelation,
                'relationEntry' => $relation->entry,
            ];
        }
        return [
            'nodes' => $nodes,
            'links' => $links
        ];
    }

    public static function listDomainForGraph(int $idSemanticType, array $frameRelation): array
    {
        $nodes = [];
        $links = [];
        $idLanguage = AppService::getCurrentIdLanguage();
        if ($idSemanticType > 0) {
            $semanticType = SemanticType::byId($idSemanticType);
            $frames = Criteria::table("view_relation as r")
                ->join("view_frame as f", "r.idEntity1", "=", "f.idEntity")
                ->select("r.idEntity1 as idEntity", "f.name")
                ->where("r.relationType", "=", "rel_framal_domain")
                ->where("r.idEntity2", "=", $semanticType->idEntity)
                ->where("f.idLanguage", "=", $idLanguage)
                ->orderBy('f.name')
                ->all();
            $list = [];
            foreach ($frames as $frame) {
                $list[$frame->idEntity] = $frame->idEntity;
            }
            foreach ($frames as $frame) {
                $idEntity = $frame->idEntity;
                $partial = Criteria::table("view_relation as r")
                    ->join("view_frame as f1", "r.idEntity1", "=", "f1.idEntity")
                    ->join("view_frame as f2", "r.idEntity2", "=", "f2.idEntity")
                    ->select('r.idEntityRelation', 'r.idRelationType', 'r.relationType', 'r.entity1Type', 'r.entity2Type', 'r.idEntity1', 'r.idEntity2',
                        'f1.name as frame1Name',
                        'f2.name as frame2Name',
                    )->where('f1.idLanguage', '=', $idLanguage)
                    ->where('f2.idLanguage', '=', $idLanguage)
                    ->whereRaw("((r.idEntity1 = {$idEntity}) or (r.idEntity2 = {$idEntity}))")
                    ->all();
                foreach ($partial as $r) {
                    $ok = isset($list[$r->idEntity1]) && isset($list[$r->idEntity2]);
                    if ($ok) {
                        if (in_array($r->idRelationType, $frameRelation)) {
                            $nodes[$r->idEntity1] = [
                                'type' => 'frame',
                                'name' => $r->frame1Name
                            ];
                            $nodes[$r->idEntity2] = [
                                'type' => 'frame',
                                'name' => $r->frame2Name
                            ];
                            $links[$r->idEntity1][$r->idEntity2] = [
                                'type' => 'ff',
                                'idEntityRelation' => $r->idEntityRelation,
                                'relationEntry' => $r->relationType,
                            ];
                        }
                    }
                }
            }
        }
        return [
            'nodes' => $nodes,
            'links' => $links
        ];
    }

    private static function listRecursiveDirectFrameRelations(int $idFrame, array $frameRelation, &$relations = [], &$handled = [], int $level = 0): void
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        if ($level > 3) {
            $frameRelation = [1,2,12];
        }
        $r = Criteria::table("view_frame_relation")
            ->where("f1IdFrame", $idFrame)
            ->where("idLanguage", $idLanguage)
            ->whereIn("idRelationType", $frameRelation)
            ->orderBy("relationType")
            ->orderBy("f2Name")
            ->all();
        foreach ($r as $relation) {
            $relations[] = $relation;
            if (!isset($handled[$relation->f2IdFrame])) {
                $handled[$relation->f2IdFrame] = true;
                self::listRecursiveDirectFrameRelations($relation->f2IdFrame, $frameRelation, $relations, $handled, $level + 1);
            }
        }
    }

    public static function listScenarioForGraph(int $idFrame, array $frameRelation): array
    {
        $nodes = [];
        $links = [];
        $relations = [];
        self::listRecursiveDirectFrameRelations($idFrame, $frameRelation, $relations);
        foreach ($relations as $relation) {
            if (in_array($relation->idRelationType, $frameRelation)) {
                $nodes[$relation->f1IdEntity] = [
                    'type' => 'frame',
                    'name' => $relation->f1Name
                ];
                $nodes[$relation->f2IdEntity] = [
                    'type' => 'frame',
                    'name' => $relation->f2Name
                ];
                $links[$relation->f1IdEntity][$relation->f2IdEntity] = [
                    'type' => 'ff',
                    'idEntityRelation' => $relation->idEntityRelation,
                    'relationEntry' => $relation->relationType,
                ];
            }
        }
        return [
            'nodes' => $nodes,
            'links' => $links
        ];
    }

    public static function listRelationsConcept(int $idConcept)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        //$config = config('webtool.relations');
        $result = [];
        $relations = Criteria::table("view_concept_relation")
            ->where("c1IdConcept", $idConcept)
            ->where("idLanguage", $idLanguage)
            ->orderBy("relationType")
            ->orderBy("c2Name")
            ->all();
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameDirect,
                'color' => $relation->color,
                'idConceptRelated' => $relation->c2IdConcept,
                'related' => $relation->c2Name,
                'type' => $relation->c2Type,
                'direction' => 'direct'
            ];
        }
        $inverse = Criteria::table("view_concept_relation")
            ->where("c2IdConcept", $idConcept)
            ->where("idLanguage", $idLanguage)
            ->all();
        foreach ($inverse as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameInverse,
                'color' => $relation->color,
                'idConceptRelated' => $relation->c1IdConcept,
                'related' => $relation->c1Name,
                'type' => $relation->c1Type,
                'direction' => 'inverse'
            ];
        }
        return $result;
    }

    public static function listRelationsSemanticType(int $idSemanticType)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        //$config = config('webtool.relations');
        $result = [];
        $relations = Criteria::table("view_semantictype_relation")
            ->where("st1IdSemanticType", $idSemanticType)
            ->where("idLanguage", $idLanguage)
            ->orderBy("relationType")
            ->orderBy("st2Name")
            ->all();
        foreach ($relations as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameDirect,
                'color' => $relation->color,
                'idSTRelated' => $relation->st2IdSemanticType,
                'related' => $relation->st2Name,
                'direction' => 'direct'
            ];
        }
        $inverse = Criteria::table("view_semantictype_relation")
            ->where("st2IdSemanticType", $idSemanticType)
            ->where("idLanguage", $idLanguage)
            ->all();
        foreach ($inverse as $relation) {
            $result[] = (object)[
                'idEntityRelation' => $relation->idEntityRelation,
                'relationType' => $relation->relationType,
                'name' => $relation->nameInverse,
                'color' => $relation->color,
                'idSTRelated' => $relation->st1IdSemanticType,
                'related' => $relation->st1Name,
                'direction' => 'inverse'
            ];
        }
        return $result;
    }


}
